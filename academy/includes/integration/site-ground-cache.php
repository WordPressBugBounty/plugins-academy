<?php
namespace Academy\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Classes\CachePluginAbstract;
use Academy\Helper;

class SiteGroundCache extends CachePluginAbstract {

	protected $plugin = 'sg-cachepress/sg-cachepress.php';

	public function do_not_cache() {
		Helper::maybe_define_constant( 'DONOTCACHEPAGE', true );
	}
}
