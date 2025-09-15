<?php
namespace Academy\Lesson\LessonApi\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Exception;
class HpLesson extends Base\Lesson {

	protected function set_default() : void {
		$this->data = wp_parse_args( $this->data, [
			'lesson_author'       => get_current_user_id(),
			'lesson_date'         => '',
			'lesson_date_gmt'     => '',
			'lesson_title'        => '',
			'lesson_name'        => '',
			'lesson_content'      => '',
			'lesson_excerpt'      => '',
			'lesson_status'       => 'draft',
			'comment_status'      => 'close',
			'comment_count'       => 0,
			'lesson_password'     => '',
			'lesson_modified'     => current_time( 'mysql' ),
			'lesson_modified_gmt' => current_time( 'mysql' ),
		] );
	}

	public function is_slug_available() : bool {
		$id = absint( $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT ID
					FROM {$this->table} 
					WHERE lesson_name = %s",
				$this->data['lesson_name']
			),
			ARRAY_A
		)['ID'] ?? 0 );

		if ( $id === 0 || $id === $this->id ) {
			return true;
		}
		return false;
	}
	public static function by_id( int $id, bool $skip_meta = false, ?int $author = null, ?string $status = null ) : self {
		$ins = new self();

		$sql = "SELECT * FROM {$ins->table} WHERE ID = %d";
		$params = [ $id ];

		if ( $author !== null ) {
			$sql .= ' AND lesson_author = %d';
			$params[] = $author;
		}

		if ( null !== $status ) {
			$sql .= ' AND lesson_status = %s';
			$params[] = $status;
		}

		$row = $ins->wpdb->get_row( $ins->wpdb->prepare( $sql, ...$params ), ARRAY_A );
		return self::get_lesson( $row, $ins, $skip_meta );
	}

	public static function by_slug( string $slug, bool $skip_meta = false, int $author = null ) : self {
		$ins = new self();

		$sql = "SELECT * FROM {$ins->table} WHERE lesson_name = %s";
		$params = [ $slug ];

		if ( $author !== null ) {
			$sql .= ' AND lesson_author = %d';
			$params[] = $author;
		}

		return self::get_lesson(
			$ins->wpdb->get_row(
				$ins->wpdb->prepare( $sql, ...$params ),
				ARRAY_A
			),
			$ins,
			$skip_meta
		);
	}

	public static function by_title( string $title, bool $skip_meta = false, int $author = null ) : self {
		$ins = new self();

		$sql = "SELECT * FROM {$ins->table} WHERE lesson_title = %s";
		$params = [ $title ];

		if ( $author !== null ) {
			$sql .= ' AND lesson_author = %d';
			$params[] = $author;
		}

		return self::get_lesson(
			$ins->wpdb->get_row(
				$ins->wpdb->prepare( $sql, ...$params ),
				ARRAY_A
			),
			$ins,
			$skip_meta
		);
	}

	protected static function get_lesson( ?array $data, self $ins, bool $skip_meta = false ) : self {
		if ( is_array( $data ) && isset( $data['ID'] ) ) {
			$meta_data = $skip_meta ? [] : $ins->wpdb->get_results(
				$ins->wpdb->prepare(
					"SELECT meta_key, meta_value FROM {$ins->meta_table} WHERE lesson_id = %d",
					$data['ID']
				),
				ARRAY_A
			);
			$ins->set_data( $data );
			$ins->set_meta_data( is_array( $meta_data ) ? array_column( $meta_data, 'meta_value', 'meta_key' ) : [] );
			return $ins;
		}
		throw new Exception( __( 'Invalid Lesson ID.', 'academy-pro' ) );
	}
	public static function get_total_number_of_lessons( string $status = 'any', int $user_id = 0 ) : int {
		$ins = new self();
		$query = "SELECT COUNT(*) FROM {$ins->table}";
		if ( 'any' !== $status ) {
			$query .= $ins->wpdb->prepare( ' WHERE lesson_status = %s', $status );
		}
		if ( $user_id ) {
			$query .= 'any' === $status ? ' WHERE' : ' AND';
			$query .= $ins->wpdb->prepare( ' lesson_author = %d', $user_id );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $ins->wpdb->get_var( $query );
	}

	public static function get_slug_by_id( int $id ) : ?string {
		$ins = new self();
		return $ins->wpdb->get_row(
			$ins->wpdb->prepare(
				"SELECT lesson_name FROM {$ins->table} WHERE ID = %d",
				$id
			),
			ARRAY_A
		)['lesson_name'] ?? null;
	}
	public static function get_lesson_meta_data( int $id ) : array {
		$ins = new self();
		return $ins->set_meta_data( array_column( $ins->wpdb->get_results(
			$ins->wpdb->prepare(
				"SELECT meta_key, meta_value FROM {$ins->meta_table} WHERE lesson_id = %d",
				$id
			),
			ARRAY_A
		) ?? [], 'meta_value', 'meta_key' ) )->get_data()['meta'] ?? [];
	}
	public static function get_lesson_meta( int $id, string $key ) {
		$ins = new self();
		$value = $ins->wpdb->get_row(
			$ins->wpdb->prepare(
				"SELECT meta_value FROM {$ins->meta_table} WHERE lesson_id = %d AND meta_key = %s ",
				$id,
				$key
			),
			ARRAY_A
		)['meta_value'] ?? null;
		if ( ! is_null( $value ) ) {
			$ins->set_meta_data( [
				$key => $value
			] );
			return $ins->get_data()['meta'][ $key ] ?? null;
		}
		return null;
	}

	public function save_data() : void {
		$this->data['lesson_name'] = sanitize_title( empty( $this->data['lesson_name'] ?? '' ) ? $this->data['lesson_title'] : $this->data['lesson_name'] );

		if ( $this->ignore_slug_check === false && ! $this->is_slug_available() ) {
			throw new Exception( __( 'Slug is not available.', 'academy-pro' ) );
		}

		if ( ! empty( $this->id ) ) {
			$this->data['lesson_modified']     = current_time( 'mysql' );
			$this->data['lesson_modified_gmt'] = current_time( 'mysql' );
			$saved = $this->wpdb->update(
				$this->table,
				$this->data,
				[ 'ID' => $this->id ]
			);
			if ( $saved === false ) {
				throw new Exception( __( 'Lesson update failed. An unexpected error occurred.', 'academy-pro' ) );
			}
		} else {
			$this->data['lesson_date']     = current_time( 'mysql' );
			$this->data['lesson_date_gmt'] = current_time( 'mysql' );
			if ( array_key_exists( 'lesson_type', $this->data ) ) {
				unset( $this->data['lesson_type'] );
			}
			$saved = $this->wpdb->insert(
				$this->table,
				$this->data
			);
			if ( $saved === false ) {
				throw new Exception( __( 'Failed to create Lesson.', 'academy-pro' ) );
			}
			$this->is_insert = true;
			$this->id = $this->wpdb->insert_id;
		}//end if
	}

	public function save_meta_data() : void {
		if ( $this->is_insert && ! empty( $meta = apply_filters( 'academy/lesson/set_meta_data', [] ) ) ) {
			$this->set_meta_data( $meta );
		}
		if ( ! empty( $this->id ) && is_array( $this->meta ) && count( $this->meta ) > 0 ) {
			$meta_keys = $this->wpdb->get_col(
				$this->wpdb->prepare(
					"SELECT meta_key FROM {$this->meta_table} WHERE lesson_id = %d",
					$this->id
				)
			);
			foreach ( $this->meta as $key => $value ) {
				if ( is_array( $value ) || is_object( $value ) ) {
					$value = json_encode( $value, JSON_UNESCAPED_SLASHES );
				}

				if ( in_array( $key, $meta_keys ) ) {
					$this->wpdb->update(
						$this->meta_table,
						[
							'meta_value'    => $value,
						],
						[
							'lesson_id' => $this->id,
							'meta_key'  => $key,
						]
					);
				} else {
					$this->wpdb->insert(
						$this->meta_table,
						[
							'lesson_id'     => $this->id,
							'meta_key'      => $key,
							'meta_value'    => $value,
						]
					);
				}//end if
			}//end foreach
		}//end if
	}

	public function delete() : void {

		$is_lesson_delete = $this->wpdb->delete(
			$this->table,
			[ 'ID' => $this->id ]
		);
		$is_lesson_meta_delete = $this->wpdb->delete(
			$this->meta_table,
			[ 'lesson_id' => $this->id ]
		);

		if ( $is_lesson_delete === false || $is_lesson_meta_delete === false ) {
			throw new Exception( __( 'Lesson deletion failed. Please try again.', 'academy-pro' ) );
		}
	}
}
