<?php
namespace Academy\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Classes\CachePluginAbstract;
use Academy\Helper;

class LiteSpeedCache extends CachePluginAbstract {

	protected $plugin = 'litespeed-cache/litespeed-cache.php';

	public function do_not_cache() {
		Helper::maybe_define_constant( 'DONOTCACHEPAGE', true );
	}
}
