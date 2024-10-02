<?php

namespace Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Integration {
	public static function init() {
		$self = new self();
		$self->add_cache_plugin_integration();
	}

	public function add_cache_plugin_integration() {
		Integration\HummingBirdCache::init();
		Integration\LiteSpeedCache::init();
		Integration\W3totalCache::init();
		Integration\WpFastestCache::init();
		Integration\WpOptimizeCache::init();
		Integration\WpRocket::init();
		Integration\WpSuperCache::init();
		Integration\SiteGroundCache::init();
	}

}
