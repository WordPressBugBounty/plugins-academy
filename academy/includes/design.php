<?php
namespace Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The course design: the Design screen's settings, and the templates and styles
 * built from them.
 */
class Design {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		Design\Generator::init();
		Design\Preview::init();
		Design\Palette::init();
	}
}
