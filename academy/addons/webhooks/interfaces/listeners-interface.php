<?php
namespace AcademyWebhooks\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface ListenersInterface {
	public static function dispatch( $deliver_callback, $webhook);
}
