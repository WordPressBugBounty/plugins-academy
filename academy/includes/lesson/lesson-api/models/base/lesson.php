<?php
namespace Academy\Lesson\LessonApi\Models\Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Academy\Lesson\LessonApi\Common\Db;
use Academy\Lesson\LessonApi\Models\Traits\Sanitizer;
abstract class Lesson extends Db {
	use Sanitizer;
	protected ?int $id;
	protected bool $is_insert = false;
	protected bool $ignore_slug_check = false;
	protected array $data = [];
	protected array $meta = [];
	public function __construct( array $lesson = [], array $meta = [], bool $ignore_slug_check = false ) {
		parent::__construct();
		$this->id = $lesson['ID'] ?? null;
		$this->set_default();
		$this->data = $this->sanitize_data( array_merge( $this->data, $lesson ) );
		$this->meta = $this->sanitize_data( array_merge( $this->meta, $meta ), true );
		$this->ignore_slug_check = $ignore_slug_check;
	}

	protected function sanitize_data( array $data, bool $is_meta = false ) : array {
		$sanitized_data = [];
		foreach ( $data as $key => $value ) {
			$key_ = $this->inspect_key( $key );
			$cb = strtolower( "sanitize_{$key}" );
			if ( method_exists( $this, $cb ) ) {
				$sanitized_data[ $key_ ] = $this->{$cb}( $data[ $key ] );
			} elseif ( is_numeric( $data[ $key ] ) ) {
				$sanitized_data[ $key_ ] = (int) $data[ $key ];
			} elseif ( is_array( $data[ $key ] ) || is_object( $data[ $key ] ) ) {
				$sanitized_data[ $key_ ] = $data[ $key ];
			} else {
				$sanitized_data[ $key_ ] = sanitize_text_field( $data[ $key ] );
			}
		}
		return $sanitized_data;
	}

	protected function inspect_key( string $key, bool $is_meta = false ) : string {
		return $key;
	}

	public function set_data( array $data ) : self {
		$this->data = array_merge( $this->data, $this->sanitize_data( $data ) );
		$this->id = $this->data['ID'] ?? null;
		return $this;
	}

	public function set_meta_data( array $meta ) : self {
		$this->meta = array_merge( $this->meta, $this->sanitize_data( $meta, true ) );
		return $this;
	}

	public function get_data() : array {
		$output = $this->data;
		$output['meta'] = $this->meta;
		return apply_filters( 'academy/lesson', $output, $this->id );
	}
	public function save() : self {
		$this->save_data();
		$this->save_meta_data();
		$ins = static::by_id( $this->id );
		$data = $ins->get_data();
		$meta_data = $data['meta'];
		unset( $data['meta'] );
		$this->set_data( $data );
		$this->set_meta_data( $meta_data );
		return $this;
	}

	public function id() : ?int {
		return $this->id;
	}
	abstract public static function by_id( int $id ) : self;
	abstract public static function by_slug( string $slug ) : self;
	abstract public static function by_title( string $title ) : self;
	abstract protected function set_default() : void;
	abstract public function is_slug_available() : bool;
	abstract public function save_data() : void;
	abstract public function save_meta_data() : void;
	abstract public function delete() : void;
}
