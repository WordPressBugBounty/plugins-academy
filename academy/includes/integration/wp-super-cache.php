<?php
namespace Academy\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Classes\CachePluginAbstract;
use Academy\Helper;

class WpSuperCache extends CachePluginAbstract {

	protected $plugin = 'wp-super-cache/wp-cache.php';

	public function do_not_cache() {
		Helper::maybe_define_constant( 'DONOTCACHEPAGE', 1 );
	}
}
