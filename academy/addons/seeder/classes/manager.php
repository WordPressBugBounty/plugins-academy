<?php
/**
 * Seeder manager — registry + runner.
 *
 * Collects providers (via the `academy/seeder/providers` filter), runs them in
 * dependency order, persists a manifest of everything created, and can delete
 * it all again. Driven by the CLI command and the Dummy Data admin tab.
 *
 * @package AcademySeeder\Classes
 */

namespace AcademySeeder\Classes;

use Academy\Lesson\LessonApi\Lesson as LessonApi;
use Throwable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Manager {

	/**
	 * Option holding the manifest of seeded object ids (for cleanup).
	 */
	const MANIFEST_OPTION = 'academy_seeder_manifest';

	/**
	 * Post/term/user meta flag stamped on every seeded object, so stragglers can
	 * be spotted even if the manifest is lost.
	 */
	const MARKER_META = '_academy_seeded';

	/**
	 * @var array<string, AbstractSeederProvider>
	 */
	private $providers = [];

	/**
	 * @var bool
	 */
	private $loaded = false;

	/**
	 * @var Manager|null
	 */
	private static $instance = null;

	/**
	 * @return Manager
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Build the provider registry once. Providers are contributed entirely
	 * through the filter — core providers by the seeder addon, others by any
	 * addon that hooks in.
	 *
	 * @return array<string, AbstractSeederProvider>
	 */
	public function load_providers() {
		if ( $this->loaded ) {
			return $this->providers;
		}

		/**
		 * Register dummy-data seeder providers.
		 *
		 * @param AbstractSeederProvider[] $providers
		 */
		$providers = apply_filters( 'academy/seeder/providers', [] );

		foreach ( $providers as $provider ) {
			if ( $provider instanceof AbstractSeederProvider ) {
				$this->providers[ $provider->get_key() ] = $provider;
			}
		}

		$this->loaded = true;

		return $this->providers;
	}

	/**
	 * @return array<string, AbstractSeederProvider>
	 */
	public function get_providers() {
		return $this->load_providers();
	}

	/**
	 * @param string $key Provider key.
	 *
	 * @return AbstractSeederProvider|null
	 */
	public function get_provider( $key ) {
		$this->load_providers();

		return $this->providers[ $key ] ?? null;
	}

	/**
	 * Keys of the providers ticked by default — the set a bare run seeds.
	 *
	 * @return string[]
	 */
	public function default_selected_keys() {
		$keys = [];
		foreach ( $this->load_providers() as $key => $provider ) {
			if ( $provider->is_default_selected() ) {
				$keys[] = $key;
			}
		}

		return $keys;
	}

	/**
	 * Resolve providers into dependency order (depth-first topological sort).
	 *
	 * @param string[] $keys Subset to include; empty means all providers.
	 *
	 * @return AbstractSeederProvider[]
	 */
	public function resolve_order( array $keys = [] ) {
		$all = $this->load_providers();

		if ( empty( $keys ) ) {
			$keys = array_keys( $all );
		}

		$ordered  = [];
		$visiting = [];

		$visit = function ( $key ) use ( &$visit, &$ordered, &$visiting, $all ) {
			if ( isset( $ordered[ $key ] ) || ! isset( $all[ $key ] ) || isset( $visiting[ $key ] ) ) {
				// Already added, unknown, or a dependency cycle — skip.
				return;
			}

			$visiting[ $key ] = true;
			foreach ( $all[ $key ]->get_dependencies() as $dependency ) {
				$visit( $dependency );
			}
			unset( $visiting[ $key ] );

			$ordered[ $key ] = $all[ $key ];
		};

		foreach ( $keys as $key ) {
			$visit( $key );
		}

		return array_values( $ordered );
	}

	/**
	 * Run a seed.
	 *
	 * @param array{only?:string[],counts?:array<string,int>,default_count?:?int,logger?:?callable,args?:array} $opts
	 *
	 * @return SeederContext
	 */
	public function run( array $opts = [] ) {
		$this->load_providers();

		$only    = $opts['only'] ?? [];
		$counts  = $opts['counts'] ?? [];
		$default = $opts['default_count'] ?? null;
		$logger  = $opts['logger'] ?? null;

		// A bare run (no explicit selection) seeds only the default-selected
		// sets; an explicit selection is honoured verbatim.
		if ( empty( $only ) ) {
			$only = $this->default_selected_keys();
		}

		$context   = new SeederContext( $opts['args'] ?? [] );
		$providers = $this->resolve_order( $only );

		foreach ( $providers as $provider ) {
			$key   = $provider->get_key();
			$count = $counts[ $key ] ?? ( null !== $default ? $default : $provider->get_default_count() );
			$count = max( 0, (int) $count );

			$context->set_provider( $key );

			if ( is_callable( $logger ) ) {
				$logger( 'before', $provider, $count );
			}

			try {
				$provider->seed( $context, $count );
			} catch ( \Throwable $e ) {
				if ( is_callable( $logger ) ) {
					$logger( 'error', $provider, $e->getMessage() );
				}
			}

			if ( is_callable( $logger ) ) {
				$logger( 'after', $provider, $count );
			}
		}//end foreach

		// Append to any existing manifest so successive runs all stay cleanable.
		$manifest = $this->get_manifest();
		$manifest = array_merge( $manifest, $context->get_records() );
		update_option( self::MANIFEST_OPTION, $manifest, false );

		return $context;
	}

	/**
	 * Delete everything recorded in the manifest.
	 *
	 * @param callable|null $logger Receives (string $type, int $id, bool $ok).
	 *
	 * @return array<string,int> Count of deleted objects by type.
	 */
	public function reset( $logger = null ) {
		$manifest = $this->get_manifest();
		if ( empty( $manifest ) ) {
			return [];
		}

		$deleted = [];

		// Reverse the manifest so dependents are removed before the objects they
		// hang off (lessons/quizzes before their course, etc.).
		foreach ( array_reverse( $manifest ) as $record ) {
			$type  = $record['type'] ?? '';
			$id    = (int) ( $record['id'] ?? 0 );
			$extra = $record['extra'] ?? [];
			if ( ! $type || ! $id ) {
				continue;
			}

			// Never let one bad record abort the whole cleanup — a manifest can
			// outlive the rows it points at.
			try {
				$ok = $this->delete_object( $type, $id, $extra );
			} catch ( \Throwable $e ) {
				$ok = false;
			}
			if ( $ok ) {
				$deleted[ $type ] = ( $deleted[ $type ] ?? 0 ) + 1;
			}

			if ( is_callable( $logger ) ) {
				$logger( $type, $id, $ok );
			}
		}//end foreach

		delete_option( self::MANIFEST_OPTION );

		return $deleted;
	}

	/**
	 * Central deletion dispatcher keyed by object type, so providers only have
	 * to record `{ type, id }` and the framework knows how to remove it.
	 *
	 * @param string $type  Object type.
	 * @param int    $id    Object id.
	 * @param array  $extra Optional context recorded alongside the object.
	 *
	 * @return bool
	 */
	public function delete_object( $type, $id, array $extra = [] ) {
		global $wpdb;

		switch ( $type ) {
			case 'course':
				return (bool) wp_delete_post( $id, true );

			case 'quiz':
				// Deleting the quiz post; drop its question/answer rows too since
				// they live in custom tables that WP won't cascade.
				$deleted = wp_delete_post( $id, true );
				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->delete( $wpdb->prefix . 'academy_quiz_questions', [ 'quiz_id' => $id ], [ '%d' ] );
				$wpdb->delete( $wpdb->prefix . 'academy_quiz_answers', [ 'quiz_id' => $id ], [ '%d' ] );
				// phpcs:enable
				return (bool) $deleted;

			case 'lesson':
				// Lessons may live in either backend ("HP" custom tables or the
				// `academy_lessons` post type), whichever the site is configured
				// to use, so delete through the abstraction rather than a table.
				try {
					LessonApi::get_by_id( $id )->delete();
					return true;
				} catch ( Throwable $e ) {
					return false;
				}

			case 'attachment':
				return (bool) wp_delete_attachment( $id, true );

			case 'course_category':
				return ! is_wp_error( wp_delete_term( $id, 'academy_courses_category' ) );

			case 'course_tag':
				return ! is_wp_error( wp_delete_term( $id, 'academy_courses_tag' ) );

			case 'user':
				if ( ! function_exists( 'wp_delete_user' ) ) {
					require_once ABSPATH . 'wp-admin/includes/user.php';
				}

				return (bool) wp_delete_user( $id );

			default:
				/**
				 * Delete a custom seeded object type registered by another addon.
				 *
				 * Return true once handled.
				 *
				 * @param bool   $handled
				 * @param string $type
				 * @param int    $id
				 * @param array  $extra
				 */
				return (bool) apply_filters( 'academy/seeder/delete_object', false, $type, $id, $extra );
		}//end switch
	}

	/**
	 * @return array<int, array{provider:string,type:string,id:int,extra:array}>
	 */
	public function get_manifest() {
		$manifest = get_option( self::MANIFEST_OPTION, [] );

		return is_array( $manifest ) ? $manifest : [];
	}

	/**
	 * @return bool
	 */
	public function has_manifest() {
		return ! empty( $this->get_manifest() );
	}
}
