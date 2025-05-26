<?php
namespace Academy\Lesson\LessonApi\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Exception;
class PostLesson extends Base\Lesson {

	protected function set_default() : void {
		$this->data = wp_parse_args( $this->data, [
			'ID'                => $this->id,
			'lesson_type'         => 'academy_lessons',
			'lesson_author'       => get_current_user_id(),
			'lesson_date'         => '',
			'lesson_date_gmt'     => '',
			'lesson_title'        => '',
			'lesson_name'         => '',
			'lesson_content'      => '',
			'lesson_excerpt'      => '',
			'lesson_status'       => 'draft',
			'comment_status'    => 'close',
			'comment_count'     => 0,
			'lesson_password'     => '',
			'lesson_modified'     => current_time( 'mysql' ),
			'lesson_modified_gmt' => current_time( 'mysql' ),
		]);
	}

	public function is_slug_available() : bool {
		$id = absint( $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT ID
					FROM {$this->wpdb->posts} 
					WHERE post_name = %s",
				$this->data['post_name'] ?? sanitize_title( $this->data['post_title'] ?? '' )
			),
			ARRAY_A
		)['ID'] ?? 0 );

		if ( $id === 0 || $id === $this->id ) {
			return true;
		}
		return false;
	}


	public static function by_id( int $id, bool $skip_meta = false ) : self {
		return self::get_lesson( get_post( $id, ARRAY_A ), new self(), $skip_meta );
	}
	public static function by_slug( string $slug, bool $skip_meta = false ) : self {
		return self::get_lesson( get_page_by_path( $slug, ARRAY_A, 'academy_lessons' ), new self(), $skip_meta );
	}
	public static function by_title( string $title, bool $skip_meta = false ) : self {
		$ins = new self();
		return self::get_lesson( $ins->wpdb->get_row(
			$ins->wpdb->prepare(
				"SELECT * FROM {$ins->wpdb->posts} WHERE post_title = %s",
				$title
			),
			ARRAY_A
		), $ins, $skip_meta );
	}

	public static function get_lesson( ?array $data, self $ins, bool $skip_meta = false ) : self {
		if ( is_array( $data ) && isset( $data['ID'] ) && $data['post_type'] === 'academy_lessons' ) {
			$meta_data = $skip_meta ? [] : $ins->wpdb->get_results(
				$ins->wpdb->prepare(
					"SELECT meta_key, meta_value FROM {$ins->wpdb->postmeta} WHERE post_id = %d",
					$data['ID']
				),
				ARRAY_A
			);
			$ins->set_data( array_intersect_key( $data, $ins->data ) );
			$ins->set_meta_data( is_array( $meta_data ) ? array_column( $meta_data, 'meta_value', 'meta_key' ) : [] );
			return $ins;
		}
		throw new Exception( __( 'Invalid Lesson ID.', 'academy-pro' ) );
	}

	public static function get_total_number_of_lessons( string $status = 'any', int $user_id = 0 ) : int {
		$ins = new self();
		$query = $ins->wpdb->prepare( "SELECT COUNT(*) FROM {$ins->wpdb->posts} WHERE post_type = %s ", 'academy_lessons' );
		if ( 'any' !== $status ) {
			$query .= $ins->wpdb->prepare( ' AND post_status = %s', $status );
		}
		if ( $user_id ) {
			$query .= $ins->wpdb->prepare( ' AND post_author = %d', $user_id );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $ins->wpdb->get_var( $query );
	}
	public static function get_slug_by_id( int $id ) : ?string {
		$ins = new self();
		return $ins->wpdb->get_row(
			$ins->wpdb->prepare(
				"SELECT post_name FROM {$ins->wpdb->posts} WHERE ID = %d",
				$id
			),
			ARRAY_A
		)['post_name'] ?? null;
	}
	public static function get_lesson_meta_data( int $id ) : array {
		$ins = new self();
		return $ins->set_meta_data( array_column( $ins->wpdb->get_results(
			$ins->wpdb->prepare(
				"SELECT meta_key, meta_value FROM {$ins->wpdb->postmeta} WHERE post_id = %d",
				$id
			),
			ARRAY_A
		) ?? [], 'meta_value', 'meta_key' ) )->get_data()['meta'] ?? [];

	}
	public static function get_lesson_meta( int $id, string $key ) {
		return get_post_meta( $id, $key, true );
	}

	protected function inspect_key( string $key, bool $is_meta = false ) : string {
		return $is_meta ? $key : preg_replace( '|^lesson_|i', 'post_', $key );
	}

	public function get_data() : array {
		$output = parent::get_data();
		foreach ( $output as $key => $value ) {
			unset( $output[ $key ] );
			$output[ preg_replace( '|^post_|i', 'lesson_', $key ) ] = $value;
		}
		return $output;
	}

	public function save_data() : void {
		if ( array_key_exists( 'ID', $this->data ) && absint( $this->data['ID'] ) === 0 ) {
			unset( $this->data['ID'] );
			$this->data['post_date']     = current_time( 'mysql' );
			$this->data['post_date_gmt'] = current_time( 'mysql' );
		}

		if ( ! array_key_exists( 'ID', $this->data ) ) {
			$this->is_insert = true;
		}

		if ( empty( $this->data['post_name'] ?? '' ) ) {
			unset( $this->data['post_name'] );
		} else {
			$this->data['post_name'] = sanitize_title( $this->data['post_name'] );
		}

		if ( $this->ignore_slug_check === false && ! $this->is_slug_available() ) {
			throw new Exception( __( 'Slug is not available.', 'academy-pro' ) );
		}

		$id = wp_insert_post( $this->data );

		if ( is_wp_error( $id ) ) {
			throw new Exception( ( $this->data['ID'] ?? false ) > 0 ? __( 'Lesson update failed. An unexpected error occurred.', 'academy-pro' ) : __( 'Failed to create Lesson.', 'academy-pro' ) );
		}
		$this->id = $id;
	}

	public function save_meta_data() : void {
		if ( $this->is_insert && ! empty( $meta = apply_filters( 'academy/lesson/set_meta_data', [] ) ) ) {
			$this->set_meta_data( $meta );
		}
		if ( ! empty( $this->id ) && is_array( $this->meta ) && count( $this->meta ) > 0 ) {
			foreach ( $this->meta as $key => $value ) {
				if ( is_array( $value ) || is_object( $value ) ) {
					$value = wp_json_encode( $value );
				}
				update_post_meta( $this->id, $key, $value );
			}
		}
	}
	public function delete() : void {
		if ( empty( wp_delete_post( $this->id, true ) ) ) {
			throw new Exception( __( 'Lesson deletion failed. Please try again.', 'academy-pro' ) );
		}
	}
}
