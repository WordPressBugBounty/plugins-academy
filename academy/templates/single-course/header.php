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
		<img class="academy-course__thumbnail-image" src="<?php echo esc_url( Academy\Helper::get_the_course_thumbnail_url( 'academy_thumbnail' ) ); ?>" alt="<?php esc_html_e( 'thumbnail', 'academy' ); ?>">
		<?php
		endif;
	?>
</div>
<?php
	$categories = \Academy\Helper::get_the_course_category( get_the_ID() );
if ( ! empty( $categories ) ) {
	echo '<span class="academy-single-course__categroy"><a href="' . esc_url( get_term_link( $categories[0]->term_id ) ) . '">' . esc_html( $categories[0]->name ) . '</a></span>';
}
?>
<h1 class="academy-single-course__title"><?php the_title(); ?></h1>
