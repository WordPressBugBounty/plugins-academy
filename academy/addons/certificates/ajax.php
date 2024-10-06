<?php

namespace AcademyCertificates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Ajax {
	public static function init() {
		Admin\Ajax::init();
	}
}
