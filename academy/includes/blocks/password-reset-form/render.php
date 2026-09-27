<?php
/**
 * Password Reset Form block.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$academy_html = \Academy\Blocks::render_shortcode_block(
	'academy_password_reset_form',
	[
		'username_label'     => isset( $attributes['usernameLabel'] ) ? $attributes['usernameLabel'] : '',
		'form_title'         => isset( $attributes['formTitle'] ) ? $attributes['formTitle'] : '',
		'reset_button_label' => isset( $attributes['buttonLabel'] ) ? $attributes['buttonLabel'] : '',
	],
	$block
);

echo $academy_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the shortcode templates and core.
