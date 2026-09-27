<?php
/**
 * Shared state passed through a single seeder run.
 *
 * Providers record what they create here (for cleanup + downstream lookup) and
 * read ids produced by their dependencies. There is one context per run.
 *
 * @package AcademySeeder\Classes
 */

namespace AcademySeeder\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SeederContext {

	/**
	 * Key of the provider currently running (set by the manager).
	 *
	 * @var string
	 */
	private $provider_key = '';

	/**
	 * Flat manifest of everything created this run.
	 *
	 * @var array<int, array{provider:string,type:string,id:int,extra:array}>
	 */
	private $records = [];

	/**
	 * Free-form run arguments (passed through from the caller).
	 *
	 * @var array<string, mixed>
	 */
	private $args;

	/**
	 * @param array<string, mixed> $args
	 */
	public function __construct( array $args = [] ) {
		$this->args = $args;
	}

	/**
	 * Set the active provider. Called by the manager before each provider runs.
	 *
	 * @param string $key Provider key.
	 *
	 * @return void
	 */
	public function set_provider( $key ) {
		$this->provider_key = $key;
	}

	/**
	 * Record a persisted object so it can be cleaned up and looked up.
	 *
	 * @param string $type  Object type understood by the cleanup dispatcher
	 *                      (course|lesson|quiz|attachment|course_category|
	 *                      course_tag|user or a type handled via the delete filter).
	 * @param int    $id    The object id.
	 * @param array  $extra Optional data the cleanup step may need (e.g. the
	 *                      owning course id for completion meta).
	 *
	 * @return void
	 */
	public function record( $type, $id, array $extra = [] ) {
		$id = (int) $id;
		if ( $id <= 0 ) {
			return;
		}

		$this->records[] = [
			'provider' => $this->provider_key,
			'type'     => $type,
			'id'       => $id,
			'extra'    => $extra,
		];
	}

	/**
	 * Ids created earlier this run.
	 *
	 * @param string      $provider Provider key to read from.
	 * @param string|null $type     Optional object-type filter.
	 *
	 * @return int[]
	 */
	public function ids( $provider, $type = null ) {
		$ids = [];
		foreach ( $this->records as $record ) {
			if ( $record['provider'] !== $provider ) {
				continue;
			}
			if ( null !== $type && $record['type'] !== $type ) {
				continue;
			}
			$ids[] = $record['id'];
		}

		return $ids;
	}

	/**
	 * The full run manifest.
	 *
	 * @return array<int, array{provider:string,type:string,id:int,extra:array}>
	 */
	public function get_records() {
		return $this->records;
	}

	/**
	 * Read a pass-through run argument.
	 *
	 * @param string $key     Argument key.
	 * @param mixed  $default Fallback when absent.
	 *
	 * @return mixed
	 */
	public function get_arg( $key, $default = null ) {
		return $this->args[ $key ] ?? $default;
	}
}
