<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'dependencies' => array(
		'@wordpress/interactivity',
		array(
			'id'     => '@wordpress/interactivity-router',
			'import' => 'dynamic',
		),
	),
	'version'      => filemtime( __DIR__ . '/view.js' ),
);
