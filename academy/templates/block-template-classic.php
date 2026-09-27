<?php
/**
 * A course page built from an Academy block template, inside a classic
 * theme's own header and footer. See Academy\Frontend\Template\ClassicBlockTemplate.
 *
 * The Design screen's preview asks for the page without the theme's header and
 * footer, so the course area is all there is to look at.
 *
 * @package Academy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( \Academy\Design\Preview::is_bare() ) {
	?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<?php wp_head(); ?>
	</head>
	<body <?php body_class( 'academy-design-preview-body' ); ?>>
	<?php
	echo \Academy\Frontend\Template\ClassicBlockTemplate::content(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered blocks.
	wp_footer();
	?>
	</body>
	</html>
	<?php
	return;
}

get_header( 'course' );

echo \Academy\Frontend\Template\ClassicBlockTemplate::content(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered blocks.

get_footer( 'course' );
