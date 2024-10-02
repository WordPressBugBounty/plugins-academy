<?php
namespace Academy\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Classes\CachePluginAbstract;
use Academy\Helper;

class WpRocket extends CachePluginAbstract {

	protected $plugin = 'wp-rocket/wp-rocket.php';

	public function do_not_cache() {
		Helper::maybe_define_constant( 'DONOTCACHEPAGE', true );
	}
}
