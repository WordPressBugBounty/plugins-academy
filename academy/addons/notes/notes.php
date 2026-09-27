<?php
namespace AcademyNotes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Interfaces\AddonInterface;

/**
 * Notes addon — per-lesson learner notes (text + video-timestamp) with a
 * course-wide dashboard. Replaces the legacy single-blob-per-course scratchpad;
 * enabling the addon migrates those blobs into the new `academy_notes` table.
 */
final class Notes implements AddonInterface {

	private $addon_name = 'notes';

	private function __construct() {
		$this->define_constants();
		$this->init_addon();
	}

	public static function init() {
		static $instance = false;
		if ( ! $instance ) {
			$instance = new self();
		}
		return $instance;
	}

	public function define_constants() {
		define( 'ACADEMY_NOTES_VERSION', '1.0.0' );
	}

	public function init_addon() {
		// Create the table + migrate legacy notes the moment the addon is enabled.
		add_action( "academy/addons/activated_{$this->addon_name}", array( $this, 'addon_activation_hook' ) );

		// Stop here when the addon is disabled.
		if ( ! \Academy\Helper::get_addon_active_status( $this->addon_name ) ) {
			return;
		}

		API::init();
		Frontend::init();
		LegacyMigration::init();
	}

	public function addon_activation_hook() {
		Installer::init();
	}
}
