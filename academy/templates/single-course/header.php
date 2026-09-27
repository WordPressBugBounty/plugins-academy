<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="academy-single-course__preview">
	<?php
	if ( $preview_video ) :
		echo $preview_video; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	else :
		?>
		<?php
			// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
			echo '<img class="academy-course__thumbnail-image" src="' . esc_url( Academy\Helper::get_the_course_thumbnail_url( 'academy_thumbnail' ) ) . '" alt="' . esc_attr__( 'thumbnail', 'academy' ) . '">'; ?>
		<?php
		endif;
	?>
</div>
<?php
	$categories = \Academy\Helper::get_the_course_category( get_the_ID() );
if ( ! empty( $categories ) ) {
	foreach ( $categories as $category ) {
		$category_link = get_term_link( $category );
		if ( is_wp_error( $category_link ) ) {
			continue;
		}
		echo '<span class="academy-single-course__categroy academy-single-course__categroy--with-image"><a href="' . esc_url( $category_link ) . '"><img class="academy-single-course__categroy-thumb" src="' . esc_url( \Academy\Helper::get_the_course_category_image_url( $category->term_id ) ) . '" alt="" />' . esc_html( $category->name ) . '</a></span>';
	}
}
?>
<h1 class="academy-single-course__title"><?php the_title(); ?></h1>
