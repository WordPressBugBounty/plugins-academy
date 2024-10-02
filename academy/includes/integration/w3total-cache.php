<?php
namespace Academy\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Classes\CachePluginAbstract;
use Academy\Helper;

class W3TotalCache extends CachePluginAbstract {

	protected $plugin = 'w3-total-cache/w3-total-cache.php';

	public function do_not_cache() {
		Helper::maybe_define_constant( 'DONOTCACHEPAGE', true );
	}
}
