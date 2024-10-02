<?php
namespace Academy\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Controller {
	protected $namespace = ACADEMY_PLUGIN_SLUG . '/v1';
	abstract protected function get_value( $request);
	abstract protected function create_value( $request);
	abstract protected function update_value( $request);
	abstract protected function delete_value( $request);
	abstract protected function permissions_check( $request);
}
