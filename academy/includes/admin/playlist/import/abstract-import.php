<?php
namespace Academy\Admin\Playlist\Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class AbstractImport {
	protected int $id;
	protected array $data;
	protected array $meta_data;

	public function __construct( array $data, array $meta_data ) {
		$this->data      = $data;
		$this->meta_data = $meta_data;
	}

	abstract public function save() : self;

	public function get_id() : ?int {
		return $this->id;
	}
}
