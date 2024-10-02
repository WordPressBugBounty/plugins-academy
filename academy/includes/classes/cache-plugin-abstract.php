<?php
namespace Academy\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Interfaces\InitInterface;
use Academy\Helper;

abstract class CachePluginAbstract implements InitInterface {
	protected $plugin = '';

	public static function init() {
		$self = new static();
		add_action( 'wp', array( $self, 'do_not_cache_page' ) );
	}

	public function do_not_cache_page() {
		if ( ! $this->is_plugin_active() ) {
			return;
		}

		// Check Admin Pages
		if ( Helper::is_academy_admin_page() ) {
			$this->do_not_cache();
		}

		// Check frontend Dashboard Pages
		$ScriptsBase = new ScriptsBase();

		if ( $ScriptsBase->is_academy_common_pages() ) {
			$this->do_not_cache();
		}

		if ( $ScriptsBase->is_frontend_dashboard_page() ) {
			$this->do_not_cache();
		}

		if ( $ScriptsBase->is_course_lesson_page() ) {
			$this->do_not_cache();
		}
	}

	protected function is_plugin_active() {
		return Helper::is_plugin_active( $this->plugin );
	}

	abstract public function do_not_cache();

}
