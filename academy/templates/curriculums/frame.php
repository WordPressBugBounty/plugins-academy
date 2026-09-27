<?php
/**
 * Isolated full-page frame for a curriculum item's shortcode/block content.
 *
 * Loaded by Academy\Frontend::render_curriculum_frame() into an iframe on the
 * learn page. Running the normal wp_head -> content -> wp_footer pipeline lets
 * ANY shortcode/block enqueue and print its assets exactly as on a real page.
 *
 * Override by copying to yourtheme/academy/curriculums/frame.php.
 *
 * @var string $content Rendered (shortcode/block-processed) HTML.
 *
 * @package Academy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
<style>html,body{margin:0;background:transparent}body{padding:0}.academy-curriculum-frame__body{padding:0}</style>
</head>
<body <?php body_class( 'academy-curriculum-frame' ); ?>>
<div class="academy-curriculum-frame__body">
	<?php
	echo isset( $content ) ? $content : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted rendered content.
	?>
</div>
<?php wp_footer(); ?>
</body>
</html>
