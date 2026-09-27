<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'dependencies' => array( '@wordpress/interactivity' ),
	'version'      => filemtime( __DIR__ . '/view.js' ),
);
