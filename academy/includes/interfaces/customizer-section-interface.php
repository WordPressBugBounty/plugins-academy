<?php
namespace Academy\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface CustomizerSectionInterface {

	public function register_section( $wp_customize);
	public function dispatch_settings( $wp_customize);
}
