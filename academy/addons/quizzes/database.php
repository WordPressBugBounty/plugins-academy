<?php
namespace AcademyQuizzes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class Database {

	/**
	 * Bump whenever a quiz table definition in database/ changes. Existing
	 * sites then pick the change up the next time the Quizzes addon loads —
	 * including a site that enables the addon long after upgrading Academy.
	 */
	const SCHEMA_VERSION = '4.0.0';

	const SCHEMA_VERSION_KEY = 'quizzes_schema';

	public static function init() {
		$self = new self();
		add_action( 'init', [ $self, 'create_academy_quiz_post_type' ], 5 );
		add_action( 'rest_api_init', [ $self, 'register_academy_quiz_meta' ] );
		add_filter( 'rest_pre_dispatch', [ $self, 'normalize_empty_integer_meta' ], 10, 3 );
		add_action( 'init', [ __CLASS__, 'maybe_sync_schema' ], 1 );
	}

	/**
	 * A cleared numeric quiz setting arrives as an empty string, which REST rejects as
	 * "not of type integer" before it ever reaches a callback. Treat it as 0.
	 *
	 * @param mixed           $result  Response to replace the requested version with.
	 * @param WP_REST_Server  $server  Server instance.
	 * @param WP_REST_Request $request Request used to generate the response.
	 * @return mixed
	 */
	public function normalize_empty_integer_meta( $result, $server, $request ) {
		if ( ! in_array( $request->get_method(), [ 'POST', 'PUT', 'PATCH' ], true ) ) {
			return $result;
		}
		if ( ! preg_match( '#^/' . preg_quote( ACADEMY_PLUGIN_SLUG, '#' ) . '/v1/academy_quiz(?:/\d+)?$#', $request->get_route() ) ) {
			return $result;
		}
		$meta = $request->get_param( 'meta' );
		if ( ! is_array( $meta ) ) {
			return $result;
		}
		$integer_keys = [
			'academy_quiz_time',
			'academy_quiz_passing_grade',
			'academy_quiz_max_questions_for_answer',
			'academy_quiz_max_attempts_allowed',
			'academy_quiz_short_answer_characters_limit',
		];
		foreach ( $integer_keys as $key ) {
			if ( array_key_exists( $key, $meta ) && ( '' === $meta[ $key ] || null === $meta[ $key ] ) ) {
				$meta[ $key ] = 0;
			}
		}
		$request->set_param( 'meta', $meta );
		return $result;
	}

	public static function maybe_sync_schema() {
		if ( self::SCHEMA_VERSION === \Academy\Options::get( \Academy\Options::DB_VERSIONS, self::SCHEMA_VERSION_KEY ) ) {
			return;
		}
		self::sync_schema();
	}

	/**
	 * Bring the quiz tables in line with their definitions.
	 *
	 * WordPress's dbDelta() can't do this on its own: the definitions use
	 * `CREATE TABLE IF NOT EXISTS`, which dbDelta reads as a table named "IF",
	 * so it only ever creates missing tables and never alters existing ones.
	 * Columns (and the attempt/question unique key) are therefore added here,
	 * idempotently, from the same definitions.
	 */
	public static function sync_schema() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		global $wpdb;
		$prefix          = $wpdb->prefix;
		$charset_collate = $wpdb->get_charset_collate();

		self::create_initial_custom_table();

		$tables = array(
			Database\CreateQuizQuestionsTable::class,
			Database\CreateQuizAnswersTable::class,
			Database\CreateQuizAttemptsTable::class,
			Database\CreateQuizAttemptAnswersTable::class,
		);
		$ok = true;
		foreach ( $tables as $table ) {
			$ok = self::add_missing_columns( $table::table_name( $prefix ), $table::schema( $prefix, $charset_collate ) ) && $ok;
		}

		$ok = self::add_attempt_question_unique_key( Database\CreateQuizAttemptAnswersTable::table_name( $prefix ) ) && $ok;

		if ( $ok ) {
			self::extract_embedded_question_audio( Database\CreateQuizQuestionsTable::table_name( $prefix ) );
			\Academy\Options::set( \Academy\Options::DB_VERSIONS, self::SCHEMA_VERSION_KEY, self::SCHEMA_VERSION );
		}
	}

	/**
	 * Column definitions (name => SQL) from a CREATE TABLE statement, in order.
	 *
	 * @param string $create_sql CREATE TABLE statement.
	 * @return array<string,string>
	 */
	private static function parse_columns( $create_sql ) {
		$columns = array();
		$body    = substr( $create_sql, strpos( $create_sql, '(' ) + 1 );
		$body    = substr( $body, 0, strrpos( $body, ')' ) );
		foreach ( preg_split( '/\R/', $body ) as $line ) {
			$line = trim( rtrim( trim( $line ), ',' ) );
			if ( '' === $line || preg_match( '/^(PRIMARY|UNIQUE|KEY|INDEX|FULLTEXT|CONSTRAINT)\b/i', $line ) ) {
				continue;
			}
			$name             = strtok( $line, " \t" );
			$columns[ $name ] = $line;
		}
		return $columns;
	}

	/**
	 * Add every column the definition has and the table lacks, each placed
	 * after its predecessor in the definition.
	 *
	 * @param string $table_name
	 * @param mixed  $create_sql
	 *
	 * @return bool False if the table is missing or an ALTER failed.
	 */
	private static function add_missing_columns( $table_name, $create_sql ) {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- versioned schema sync; only the $wpdb-prefixed table name and column definitions from our own CREATE TABLE are interpolated.
		$existing = $wpdb->get_col( "SHOW COLUMNS FROM `{$table_name}`" );
		if ( empty( $existing ) ) {
			return false;
		}

		$ok       = true;
		$previous = null;
		foreach ( self::parse_columns( $create_sql ) as $name => $definition ) {
			if ( ! in_array( $name, $existing, true ) ) {
				$after  = $previous ? " AFTER `{$previous}`" : ' FIRST';
				$result = $wpdb->query( "ALTER TABLE `{$table_name}` ADD COLUMN {$definition}{$after}" );
				if ( false === $result ) {
					$ok = false;
					continue;
				}
				$existing[] = $name;
			}
			$previous = $name;
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		return $ok;
	}

	/**
	 * One answer row per attempt + question. An older final-submit bug could
	 * write duplicates, which would make the key fail — so those collapse to
	 * the newest row first.
	 *
	 * @param string $table_name
	 *
	 * @return bool
	 */
	private static function add_attempt_question_unique_key( $table_name ) {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- versioned schema sync; only the $wpdb-prefixed table name is interpolated.
		$has_key = $wpdb->get_var( "SHOW INDEX FROM `{$table_name}` WHERE Key_name = 'attempt_question'" );
		if ( $has_key ) {
			return true;
		}
		$wpdb->query(
			"DELETE t1 FROM `{$table_name}` t1
			INNER JOIN `{$table_name}` t2
				ON t1.attempt_id = t2.attempt_id
				AND t1.question_id = t2.question_id
				AND t1.attempt_answer_id < t2.attempt_answer_id"
		);
		$result = $wpdb->query( "ALTER TABLE `{$table_name}` ADD UNIQUE KEY attempt_question (attempt_id, question_id)" );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		return false !== $result;
	}

	/**
	 * The Description field used to let admins embed an <audio> tag in
	 * question_content. That now lives in question_audio_id, so move any
	 * embedded `<audio src="...">` into the column and strip it from the content.
	 *
	 * @param string $table_name
	 */
	private static function extract_embedded_question_audio( $table_name ) {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- versioned schema sync; only the $wpdb-prefixed table name is interpolated.
		$rows = $wpdb->get_results(
			"SELECT question_id, question_content FROM `{$table_name}`
			WHERE question_content LIKE '%<audio%'
			AND ( question_audio_id IS NULL OR question_audio_id = 0 )"
		);

		foreach ( (array) $rows as $row ) {
			if ( ! preg_match( '/<audio\b[^>]*\ssrc="([^"]+)"/i', $row->question_content, $src_match ) ) {
				continue;
			}

			$audio_id = \Academy\Helper::attachment_url_to_postid( html_entity_decode( $src_match[1] ) );
			if ( ! $audio_id ) {
				continue;
			}

			$wpdb->update(
				$table_name,
				array(
					'question_audio_id' => $audio_id,
					'question_content'  => trim( preg_replace( '/<audio\b[^>]*>.*?<\/audio>|<audio\b[^>]*\/>/is', '', $row->question_content ) ),
				),
				array( 'question_id' => $row->question_id ),
				array( '%d', '%s' ),
				array( '%d' )
			);
		}//end foreach
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public function create_academy_quiz_post_type() {
		$post_type = 'academy_quiz';
		register_post_type(
			$post_type,
			array(
				'labels'                => array(
					'name'                  => esc_html__( 'Quizzes', 'academy' ),
					'singular_name'         => esc_html__( 'quiz', 'academy' ),
					'search_items'          => esc_html__( 'Search quizzes', 'academy' ),
					'parent_item_colon'     => esc_html__( 'Parent quizzes:', 'academy' ),
					'not_found'             => esc_html__( 'No quizzes found.', 'academy' ),
					'not_found_in_trash'    => esc_html__( 'No quizzes found in Trash.', 'academy' ),
					'archives'              => esc_html__( 'quiz archives', 'academy' ),
				),
				'public'                => true,
				'publicly_queryable'    => true,
				'show_ui'               => false,
				'show_in_menu'          => false,
				'hierarchical'          => false,
				'rewrite'               => array( 'slug' => 'quiz' ),
				'query_var'             => true,
				'has_archive'           => true,
				'delete_with_user'      => false,
				'supports'              => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'comments', 'post-formats' ),
				'show_in_rest'          => true,
				'rest_base'             => $post_type,
				'rest_namespace'        => ACADEMY_PLUGIN_SLUG . '/v1',
				'rest_controller_class' => Authorization\QuizController::class,
				'capability_type'           => 'post',
				'capabilities'              => array(
					'edit_post'             => 'edit_academy_quiz',
					'read_post'             => 'read_academy_quiz',
					'delete_post'           => 'delete_academy_quiz',
					'delete_posts'          => 'delete_academy_quizzes',
					'edit_posts'            => 'edit_academy_quizzes',
					'edit_others_posts'     => 'edit_others_academy_quizzes',
					'publish_posts'         => 'publish_academy_quizzes',
					'read_private_posts'    => 'read_private_academy_quizzes',
					'create_posts'          => 'edit_academy_quizzes',
				),
			)
		);
	}

	public function register_academy_quiz_meta() {
		$course_meta = [
			'academy_quiz_time'                         => 'integer',
			'academy_quiz_time_unit'                    => 'string',
			'academy_quiz_hide_quiz_time'               => 'boolean',
			'academy_quiz_feedback_mode'                => 'string',
			'academy_quiz_passing_grade'                => 'integer',
			'academy_quiz_max_questions_for_answer'     => 'integer',
			'academy_quiz_max_attempts_allowed'         => 'integer',
			'academy_quiz_auto_start'                   => 'boolean',
			'academy_quiz_questions_order'              => 'string',
			'academy_quiz_hide_question_number'         => 'boolean',
			'academy_quiz_short_answer_characters_limit' => 'integer',
			'academy_quiz_explanation_enabled'          => 'boolean',
			'academy_quiz_skip_question_showing'        => 'boolean',
			'academy_quiz_show_full_answer_content'         => 'boolean',
		];

		foreach ( $course_meta as $meta_key => $meta_value_type ) {
			register_meta(
				'post',
				$meta_key,
				array(
					'object_subtype' => 'academy_quiz',
					'type'           => $meta_value_type,
					'single'         => true,
					'show_in_rest'   => true,
				)
			);
		}
		register_meta(
			'post',
			'academy_quiz_questions',
			array(
				'object_subtype' => 'academy_quiz',
				'type'           => 'array',
				'single'         => true,
				'show_in_rest'   => [
					'schema' => array(
						'items' => array(
							'type'       => 'object',
							'properties' => [
								'id'   => array(
									'type' => 'integer',
								),
								'title' => array(
									'type' => 'string',
								),
							],
						),
					),
				],
			)
		);
		register_meta(
			'post',
			'academy_quiz_questions_layout',
			array(
				'object_subtype'    => 'academy_quiz',
				'type'              => 'string',
				'single'            => true,
				'default'           => 'single',
				// Coerce any value outside the allowed set back to 'single'. Imported/
				// migrated quizzes can carry a layout value the source LMS used (or an
				// empty string), and a strict `enum` schema would make WP null it out on
				// read and then reject the whole quiz save with
				// "...has an invalid stored value, and cannot be updated to null." Keep
				// the schema a plain string and normalise the value here instead so bad
				// data self-heals on the next save rather than bricking it.
				'sanitize_callback' => array( __CLASS__, 'sanitize_questions_layout' ),
				'show_in_rest'      => array(
					'schema' => array(
						'type' => 'string',
					),
				),
			)
		);
	}

	/**
	 * Keep academy_quiz_questions_layout within its allowed set.
	 *
	 * Anything other than 'all' falls back to the 'single' default, so legacy or
	 * imported values never persist as an invalid stored value.
	 *
	 * @param mixed $value Raw meta value.
	 * @return string
	 */
	public static function sanitize_questions_layout( $value ) {
		return 'all' === $value ? 'all' : 'single';
	}

	public static function create_initial_custom_table() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		global $wpdb;
		$prefix          = $wpdb->prefix;
		$charset_collate = $wpdb->get_charset_collate();
		Database\CreateQuizQuestionsTable::up( $prefix, $charset_collate );
		Database\CreateQuizAnswersTable::up( $prefix, $charset_collate );
		Database\CreateQuizAttemptsTable::up( $prefix, $charset_collate );
		Database\CreateQuizAttemptAnswersTable::up( $prefix, $charset_collate );
	}

	public function permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden_context',
				esc_html__( 'Sorry, you are not allowed to get quiz attempt answers.', 'academy' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
	}
}
