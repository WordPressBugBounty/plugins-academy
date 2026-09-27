<?php
/**
 * Social Login Buttons block. Renders Academy Pro's Google and Facebook buttons.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! shortcode_exists( 'academy_social_login' ) || ( is_user_logged_in() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) ) {
	return;
}

$academy_roles = [ 'academy_student', 'academy_instructor' ];

$academy_html = \Academy\Blocks::render_shortcode_block(
	'academy_social_login',
	[
		'is_register'  => ! empty( $attributes['isRegister'] ) ? 'true' : '',
		'role'         => in_array( $attributes['role'], $academy_roles, true ) ? $attributes['role'] : 'academy_student',
		'show_divider' => ! empty( $attributes['showDivider'] ) ? '1' : '0',
	],
	$block
);

echo $academy_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the shortcode template and core.
