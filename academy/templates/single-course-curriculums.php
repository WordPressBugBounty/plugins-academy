<?php
/**
 * The Template for displaying all single courses
 *
 * This template can be overridden by copying it to yourtheme/academy/single-course.php.
 *
 * the readme will list any important changes.
 *
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$is_enabled_lessons_theme_header_footer = \Academy\Helper::get_settings( 'is_enabled_lessons_theme_header_footer', false );
if ( $is_enabled_lessons_theme_header_footer ) :
	academy_get_header( 'course' );
else :
	?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<?php
	endif;
?>

	<?php
		/**
		 * @hook - academy/templates/before_main_content
		 */
	do_action( 'academy/templates/before_main_content', 'single-course-curriculums.php' );


	if ( \Academy\Helper::get_settings( 'is_enabled_lessons_php_render' ) ) {
		\Academy\Helper::get_template( 'curriculums/php-render.php' );
	} else {
		\Academy\Helper::get_template( 'curriculums/js-render.php' );
	}

		/**
		 * @hook - academy/templates/after_main_content
		 */
		do_action( 'academy/templates/after_main_content', 'single-course-curriculums.php' );


	if ( $is_enabled_lessons_theme_header_footer ) :
		academy_get_footer( 'course' );
else :
	?>

	<?php wp_footer(); ?>

</body>
</html>
	<?php
endif;
