<?php
/**
 * Login Form block.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$academy_html = \Academy\Blocks::render_shortcode_block(
	'academy_login_form',
	[
		'username_label'       => isset( $attributes['usernameLabel'] ) ? $attributes['usernameLabel'] : '',
		'form_title'           => isset( $attributes['formTitle'] ) ? $attributes['formTitle'] : '',
		'login_button_label'   => isset( $attributes['buttonLabel'] ) ? $attributes['buttonLabel'] : '',
		'login_redirect_url'   => ! empty( $attributes['redirectUrl'] ) ? esc_url_raw( $attributes['redirectUrl'] ) : '',
		'student_register_url' => ! empty( $attributes['registerUrl'] ) ? esc_url_raw( $attributes['registerUrl'] ) : '',
	],
	$block
);

echo $academy_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the shortcode templates and core.
