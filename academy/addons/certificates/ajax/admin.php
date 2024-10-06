<?php

namespace AcademyCertificates\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Classes\Sanitizer;
use Academy\Classes\AbstractAjaxHandler;

class Admin extends AbstractAjaxHandler {
	protected $namespace = ACADEMY_PLUGIN_SLUG . '_certificate';
	public function __construct() {
		$this->actions = array();
	}
}
