<?php

namespace AcademyCoursePreview;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Academy\Interfaces\AddonInterface;

final class CoursePreview implements AddonInterface {
	private $addon_name = 'course-preview';
	private function __construct() {
		$this->define_constants();
		$this->init_addon();
	}

	public function define_constants() {
		/**
		 * Defines CONSTANTS for Whole Addon.
		 */
		define( 'ACADEMY_COURSE_PREVIEW_VERSION', '1.0' );
		define( 'ACADEMY_COURSE_PREVIEW_ADDON_NAME', $this->addon_name );
	}

	public function init_addon() {
		// fire addon activation hook
		add_action( "academy/addons/activated_{$this->addon_name}", array( $this, 'addon_activation_hook' ) );
		// if disable then stop running addon
		if ( ! \Academy\Helper::get_addon_active_status( $this->addon_name ) ) {
			return;
		}

		// integration starts
		Database::init();
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
