<?php
namespace Academy\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Classes\CachePluginAbstract;
use Academy\Helper;

class WpOptimizeCache extends CachePluginAbstract {

	protected $plugin = 'wp-optimize/wp-optimize.php';

	public function do_not_cache() {
		Helper::maybe_define_constant( 'DONOTCACHEPAGE', 1 );
	}
}
