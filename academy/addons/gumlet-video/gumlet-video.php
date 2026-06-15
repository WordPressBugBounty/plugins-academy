<?php

namespace AcademyGumletVideo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Interfaces\AddonInterface;

final class GumletVideo implements AddonInterface {
	private $addon_name = 'gumlet-video';

	private function __construct() {
		$this->define_constants();
		$this->init_addon();
	}

	public function define_constants() {
		define( 'ACADEMY_GUMLET_VIDEO_VERSION', '1.0' );
		define( 'ACADEMY_GUMLET_VIDEO_ADDON_NAME', $this->addon_name );
	}

	public function init_addon() {
		add_action( "academy/addons/activated_{$this->addon_name}", array( $this, 'addon_activation_hook' ) );

		if ( ! \Academy\Helper::get_addon_active_status( $this->addon_name ) ) {
			return;
		}

		Bootstrap::init();
	}

	public static function init() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	public function addon_activation_hook() {
	}
}
