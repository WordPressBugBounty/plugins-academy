<?php
namespace AcademyChatgpt\CourseImport\Importers;

use Exception;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class Lesson implements Interfaces\Insertable {
	public int $id;
	protected string $title;
	protected string $name;
	protected string $content;
	protected array $duration;
	protected array $meta = [
		'featured_media' => 0,
		'attachment'     => 0,
		'video_duration' => [
			'hours'   => 1,
			'minutes' => 2,
			'seconds' => 3,
		],
		'video_source'   => [
			'type' => '',
			'url' => '',
		],
	];
	protected object $wpdb;

	public function __construct( string $title, string $content, array $duration ) {
		$this->title    = $title;
		$this->name     = sanitize_title( $title );
		$this->content  = $content;
		$this->duration = $duration;
		$this->meta['video_duration'] = $duration;
		$this->wpdb     = $GLOBALS['wpdb'];
	}

	public function insert() : int {

		$res = $this->wpdb->insert( $this->wpdb->prefix . 'academy_lessons', [
			'lesson_title'        => $this->title,
			'lesson_name'         => $this->name,
			'lesson_content'      => $this->content,
			'lesson_modified'     => current_time( 'mysql' ),
			'lesson_modified_gmt' => current_time( 'mysql' ),
			'lesson_date'         => current_time( 'mysql' ),
			'lesson_date_gmt'     => current_time( 'mysql' ),
		] );

		if ( false === $res ) {
			throw new Exception( __( 'Error.', 'academy' ) );
		}

		$this->id = $this->wpdb->insert_id;
		$this->insert_meta();

		return $this->id;
	}

	protected function insert_meta() : void {
		foreach ( $this->meta as $key => $value ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			$res = $this->wpdb->insert( $this->wpdb->prefix . 'academy_lessonmeta', [
				'lesson_id'  => $this->id,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_key'   => $key,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_value' => is_array( $value ) ? wp_json_encode( $value, JSON_UNESCAPED_SLASHES ) : $value,
			] );
			if ( false === $res ) {
				throw new Exception( __( 'Error.', 'academy' ) );
			}
		}
	}

	public static function delete( int $id ) : bool {
		return $GLOBALS['wpdb']->delete( $GLOBALS['wpdb']->prefix . 'academy_lessons', [ 'id' => $id ] ) === false ? false : true;
	}
}
