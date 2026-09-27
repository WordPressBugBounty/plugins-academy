<?php
namespace AcademyNotes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Moves the legacy notebook notes into the `academy_notes` table.
 *
 * Legacy notes are one rich-text blob per user per course, stored in user
 * meta `academy_{course_id}lesson_note_{user_id}` (course id 0 = the wp-admin
 * notebook). Each non-empty blob becomes one course-level text note.
 *
 * - Batched: walks user meta by `umeta_id`; the cursor is kept in an option,
 *   so each legacy note is read once. The next batch runs in the background
 *   (Action Scheduler), with the next admin page load as a fallback.
 * - Safe to repeat: a note is skipped when the user already has the same
 *   course note (e.g. a batch that stopped before saving its cursor), and
 *   notes an earlier migration copied over verbatim are converted in place.
 * - Non-destructive: the user meta is left in place.
 */
class LegacyMigration {

	const STATUS_KEY = 'notes_legacy';

	const CURSOR_KEY = 'notes_legacy_cursor';

	const BATCH_ACTION = 'academy_notes_migrate_legacy_batch';

	const BATCH_SIZE = 500;

	public static function init() {
		add_action( self::BATCH_ACTION, array( __CLASS__, 'run' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_run' ) );
	}

	public static function is_done() {
		return 'done' === \Academy\Options::get( \Academy\Options::MIGRATIONS, self::STATUS_KEY );
	}

	public static function maybe_run() {
		if ( ! self::is_done() ) {
			self::run();
		}
	}

	/**
	 * Migrate one batch; queue the next one if more remain.
	 *
	 * @param int $batch_size Rows per batch.
	 * @return int Rows read in this batch.
	 */
	public static function run( $batch_size = self::BATCH_SIZE ) {
		if ( self::is_done() ) {
			return 0;
		}

		global $wpdb;
		$table = Database::get_table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one-time data migration.
		if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) ) {
			return 0;
		}

		$cursor = (int) \Academy\Options::get( \Academy\Options::MIGRATIONS, self::CURSOR_KEY, 0 );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPressVIPMinimum.Variables.RestrictedVariables.user_meta__wpdb__users -- one-time data migration; only $wpdb table names are interpolated, values are prepared.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT umeta_id, user_id, meta_key, meta_value FROM {$wpdb->usermeta}
				WHERE umeta_id > %d AND meta_key LIKE %s
				ORDER BY umeta_id ASC
				LIMIT %d",
				$cursor,
				$wpdb->esc_like( 'academy_' ) . '%' . $wpdb->esc_like( 'lesson_note_' ) . '%',
				(int) $batch_size
			)
		);

		foreach ( (array) $rows as $row ) {
			$cursor = (int) $row->umeta_id;
			if ( ! preg_match( '/^academy_([0-9]*)lesson_note_([0-9]+)$/', $row->meta_key, $m ) ) {
				continue;
			}

			$raw     = (string) $row->meta_value;
			$content = self::to_note_text( $raw );
			if ( '' === $content ) {
				continue;
			}

			$user_id   = (int) $row->user_id;
			$course_id = (int) $m[1];
			$now       = current_time( 'mysql' );

			// Already there? Either this note was migrated (as text) before, or
			// an earlier migration copied the raw HTML — convert that in place.
			$existing = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT id, content FROM {$table}
					WHERE user_id = %d AND course_id = %d AND topic_id = 0 AND note_type = 'text'
					AND ( content = %s OR content = %s )
					LIMIT 1",
					$user_id,
					$course_id,
					$content,
					$raw
				)
			);

			if ( $existing ) {
				if ( $existing->content !== $content ) {
					$wpdb->update( $table, array( 'content' => $content ), array( 'id' => (int) $existing->id ), array( '%s' ), array( '%d' ) );
				}
				continue;
			}

			$wpdb->insert(
				$table,
				array(
					'user_id'    => $user_id,
					'course_id'  => $course_id,
					'topic_id'   => 0,
					'topic_type' => '',
					'note_type'  => 'text',
					'video_time' => null,
					'content'    => $content,
					'created_at' => $now,
					'updated_at' => $now,
				),
				array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
			);
		}//end foreach
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPressVIPMinimum.Variables.RestrictedVariables.user_meta__wpdb__users

		\Academy\Options::set( \Academy\Options::MIGRATIONS, self::CURSOR_KEY, $cursor );

		$count = is_array( $rows ) ? count( $rows ) : 0;
		if ( $count < $batch_size ) {
			\Academy\Options::set( \Academy\Options::MIGRATIONS, self::STATUS_KEY, 'done' );
		} elseif ( function_exists( 'as_enqueue_async_action' ) && ! as_has_scheduled_action( self::BATCH_ACTION ) ) {
			as_enqueue_async_action( self::BATCH_ACTION, array(), 'academy' );
		}

		return $count;
	}

	/**
	 * Legacy notes are HTML from a contenteditable box; notes are plain text
	 * shown with preserved line breaks. Turn block/line breaks into newlines,
	 * drop the rest of the markup, and store it the way the Notes API does.
	 *
	 * @param string $html Legacy note.
	 * @return string
	 */
	public static function to_note_text( $html ) {
		$text = preg_replace( '#<br\s*/?>#i', "\n", (string) $html );
		$text = preg_replace( '#<li\b[^>]*>#i', "\n- ", $text );
		$text = preg_replace( '#</li>#i', '', $text );
		$text = preg_replace( '#</(p|div|ul|ol|h[1-6]|blockquote|pre|tr)>#i', "\n", $text );
		$text = wp_strip_all_tags( $text );
		$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = str_replace( "\xC2\xA0", ' ', $text );
		$text = preg_replace( "/[ \t]+\n/", "\n", $text );
		$text = trim( preg_replace( "/\n{3,}/", "\n\n", $text ) );

		return '' === $text ? '' : wp_kses_post( $text );
	}
}
