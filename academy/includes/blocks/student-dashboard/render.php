<?php
/**
 * Student Dashboard block.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

\Academy\Blocks::use_dashboard_assets();

echo \Academy\Blocks::render_shortcode_block( 'academy_dashboard', [], $block ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the dashboard templates and core.
