<?php
namespace Academy\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Classes\CachePluginAbstract;
use Academy\Helper;

class HummingBirdCache extends CachePluginAbstract {

	protected $plugin = 'hummingbird-performance/wp-hummingbird.php';

	public function do_not_cache() {
		Helper::maybe_define_constant( 'DONOTCACHEPAGE', true );
	}
}
