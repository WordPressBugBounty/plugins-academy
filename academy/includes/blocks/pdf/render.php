<?php
/**
 * PDF Viewer block.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $attributes['src'] ) ) {
	return;
}

$academy_height = preg_match( '/^\d+(\.\d+)?(px|vh|em|rem)$/', (string) $attributes['height'] ) ? $attributes['height'] : '600px';

$academy_width = \Academy\Blocks::css_width( $attributes['width'] ?? '' );

$academy_html = \Academy\Blocks::render_shortcode_block(
	'academy_pdf',
	[
		'src'    => esc_url_raw( $attributes['src'] ),
		'width'  => $academy_width ? $academy_width : '100%',
		'height' => $academy_height,
	],
	$block
);

echo $academy_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the shortcode templates and core.
