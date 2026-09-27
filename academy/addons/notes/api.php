<?php
namespace AcademyNotes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_REST_Server;
use WP_Error;

/**
 * REST API for learner notes — everything is scoped to the current user, since
 * notes are private. Namespace: academy/v1, base: /notes.
 */
class API {

	private $namespace = 'academy/v1';
	private $rest_base = 'notes';

	public static function init() {
		$self = new self();
		add_action( 'rest_api_init', array( $self, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'logged_in_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'logged_in_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'logged_in_check' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'logged_in_check' ),
				),
			)
		);
	}

	public function logged_in_check() {
		return is_user_logged_in();
	}

	private function table() {
		return Database::get_table_name();
	}

	private function prepare_note( $row ) {
		$course_id = (int) $row->course_id;
		$topic_id  = (int) $row->topic_id;

		// A ready-to-open learn-page URL (respects the PHP-render setting), so the
		// dashboard "My Notes" list can jump straight to the source item.
		$permalink = '';
		if ( $topic_id && $row->topic_type ) {
			$permalink = \Academy\Helper::get_topic_play_link(
				array(
					'type' => $row->topic_type,
					'id' => $topic_id
				),
				$course_id
			);
			// Video notes deep-link to the exact moment; the learn page reads
			// this and seeks the player once it loads.
			if ( 'video' === $row->note_type && null !== $row->video_time ) {
				$permalink = add_query_arg( 'academy_note_time', (int) $row->video_time, $permalink );
			}
		} elseif ( $course_id ) {
			$permalink = get_the_permalink( $course_id );
		}

		return array(
			'id'           => (int) $row->id,
			'course_id'    => $course_id,
			'course_title' => $course_id ? get_the_title( $course_id ) : '',
			'topic_id'     => $topic_id,
			'topic_type'   => $row->topic_type,
			'note_type'    => $row->note_type,
			'video_time'   => null === $row->video_time ? null : (int) $row->video_time,
			'content'      => $row->content,
			'permalink'    => $permalink,
			'created_at'   => $row->created_at,
			'updated_at'   => $row->updated_at,
		);
	}

	public function get_items( $request ) {
		global $wpdb;
		$user_id   = get_current_user_id();
		$table     = $this->table();
		$course_id = (int) $request->get_param( 'course_id' );
		$topic_id  = (int) $request->get_param( 'topic_id' );
		$note_type = sanitize_key( (string) $request->get_param( 'note_type' ) );
		$search    = sanitize_text_field( (string) $request->get_param( 'search' ) );
		$page      = max( 1, (int) $request->get_param( 'page' ) );
		$per_page  = min( 100, max( 1, (int) ( $request->get_param( 'per_page' ) ? $request->get_param( 'per_page' ) : 20 ) ) );
		$offset    = ( $page - 1 ) * $per_page;

		$where  = array( 'user_id = %d' );
		$params = array( $user_id );

		if ( $course_id ) {
			$where[]  = 'course_id = %d';
			$params[] = $course_id;
		}
		if ( $request->get_param( 'topic_id' ) !== null && '' !== $request->get_param( 'topic_id' ) ) {
			$where[]  = 'topic_id = %d';
			$params[] = $topic_id;
		}
		if ( 'text' === $note_type || 'video' === $note_type ) {
			$where[]  = 'note_type = %s';
			$params[] = $note_type;
		}
		if ( '' !== $search ) {
			$where[]  = 'content LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $search ) . '%';
		}

		$where_sql = implode( ' AND ', $where );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$total = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}", $params )
		);

		$query_params = array_merge( $params, array( $per_page, $offset ) );
		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- placeholders are generated to match the values
		$rows         = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$query_params
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		// phpcs:enable

		$data     = array_map( array( $this, 'prepare_note' ), $rows ? $rows : array() );
		$response = rest_ensure_response( $data );
		$response->header( 'X-WP-Total', (string) $total );
		return $response;
	}

	public function create_item( $request ) {
		global $wpdb;
		$content = wp_kses_post( (string) $request->get_param( 'content' ) );
		if ( '' === trim( wp_strip_all_tags( $content ) ) ) {
			return new WP_Error( 'academy_note_empty', __( 'Note cannot be empty.', 'academy' ), array( 'status' => 400 ) );
		}

		$note_type  = sanitize_key( (string) $request->get_param( 'note_type' ) );
		$note_type  = 'video' === $note_type ? 'video' : 'text';
		$video_time = $request->get_param( 'video_time' );
		$video_time = ( 'video' === $note_type && null !== $video_time && '' !== $video_time )
			? (int) $video_time
			: null;
		$now = current_time( 'mysql' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert(
			$this->table(),
			array(
				'user_id'    => get_current_user_id(),
				'course_id'  => (int) $request->get_param( 'course_id' ),
				'topic_id'   => (int) $request->get_param( 'topic_id' ),
				'topic_type' => sanitize_key( (string) $request->get_param( 'topic_type' ) ),
				'note_type'  => $note_type,
				'video_time' => $video_time,
				'content'    => $content,
				'created_at' => $now,
				'updated_at' => $now,
			),
			array( '%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
		);

		return rest_ensure_response( $this->get_owned_note( $wpdb->insert_id ) );
	}

	public function update_item( $request ) {
		global $wpdb;
		$id   = (int) $request['id'];
		$note = $this->get_owned_row( $id );
		if ( ! $note ) {
			return new WP_Error( 'academy_note_not_found', __( 'Note not found.', 'academy' ), array( 'status' => 404 ) );
		}

		$fields  = array( 'updated_at' => current_time( 'mysql' ) );
		$formats = array( '%s' );

		if ( null !== $request->get_param( 'content' ) ) {
			$content = wp_kses_post( (string) $request->get_param( 'content' ) );
			if ( '' === trim( wp_strip_all_tags( $content ) ) ) {
				return new WP_Error( 'academy_note_empty', __( 'Note cannot be empty.', 'academy' ), array( 'status' => 400 ) );
			}
			$fields['content'] = $content;
			$formats[]         = '%s';
		}
		if ( null !== $request->get_param( 'video_time' ) ) {
			$fields['video_time'] = (int) $request->get_param( 'video_time' );
			$formats[]            = '%d';
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update( $this->table(), $fields, array( 'id' => $id ), $formats, array( '%d' ) );

		return rest_ensure_response( $this->get_owned_note( $id ) );
	}

	public function delete_item( $request ) {
		global $wpdb;
		$id   = (int) $request['id'];
		$note = $this->get_owned_row( $id );
		if ( ! $note ) {
			return new WP_Error( 'academy_note_not_found', __( 'Note not found.', 'academy' ), array( 'status' => 404 ) );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $this->table(), array( 'id' => $id ), array( '%d' ) );
		return rest_ensure_response( array(
			'deleted' => true,
			'id' => $id
		) );
	}

	private function get_owned_row( $id ) {
		global $wpdb;
		$table = $this->table();
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery -- only $wpdb-prefixed table names / generated placeholders are interpolated; values are prepared
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d AND user_id = %d", $id, get_current_user_id() )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
	}

	private function get_owned_note( $id ) {
		$row = $this->get_owned_row( $id );
		return $row ? $this->prepare_note( $row ) : null;
	}
}
