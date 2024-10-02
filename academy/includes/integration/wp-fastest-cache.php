<?php
namespace Academy\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Classes\CachePluginAbstract;

class WpFastestCache extends CachePluginAbstract {

	protected $plugin = 'wp-fastest-cache/wpFastestCache.php';

	public function do_not_cache() {
		function_exists( 'wpfc_exclude_current_page' ) && wpfc_exclude_current_page();
	}
}
