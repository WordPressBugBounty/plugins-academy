<?php
/**
 * Certificate Verification block. Renders Academy Pro's verification form in
 * the layout chosen in the block settings.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! shortcode_exists( 'academy_pro_certificate_verification' ) ) {
	return;
}

\Academy\Blocks::use_course_assets();

$academy_form = (string) do_shortcode( '[academy_pro_certificate_verification]' );
if ( '' === trim( $academy_form ) ) {
	return;
}

$academy_layout  = in_array( $attributes['layout'] ?? 'stacked', [ 'stacked', 'split', 'inline', 'banner' ], true ) ? $attributes['layout'] : 'stacked';
$academy_classes = 'academy-cert-layout--' . $academy_layout;
if ( empty( $attributes['showImage'] ) ) {
	$academy_classes .= ' hide-image';
}
if ( empty( $attributes['showHeading'] ) ) {
	$academy_classes .= ' hide-heading';
}

printf(
	'<div %1$s>%2$s</div>',
	get_block_wrapper_attributes( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core.
		[
			'class' => $academy_classes,
			'style' => \Academy\Blocks::academy_colors_style( $attributes ),
		]
	),
	$academy_form // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the shortcode template.
);
