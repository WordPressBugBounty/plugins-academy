<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$card_style = Academy\Helper::get_settings( 'course_card_style' );
$wishlist_class = 'layout_two' === $card_style || 'layout_four' === $card_style ? 'academy-wishlist-dynamic' : '';
$image_class = 'layout_two' === $card_style || 'layout_four' === $card_style ? 'academy-course-thumbnail-width' : '';
?>
<?php
	$level = get_post_meta( get_the_ID(), 'academy_course_difficulty_level', true );
?>
<div class="academy-course__header">
	<?php
		do_action( 'academy/templates/before_course_loop_header_inner' );
	?>
	<?php
	if ( $wishlists_status ) :
		?>
	<div class="academy-course-header-meta">
		<?php if ( $is_already_in_wishlist ) : ?>
			<a class="academy-course__wishlist academy-add-wishlist-btn <?php echo esc_html( $wishlist_class ); ?>" data-course-id="<?php echo esc_attr( get_the_ID() ); ?>"><i class="academy-icon academy-icon--heart" aria-hidden="true"></i></a>
		<?php else : ?>
			<a class="academy-course__wishlist academy-add-wishlist-btn <?php echo esc_html( $wishlist_class ); ?>" data-course-id="<?php echo esc_attr( get_the_ID() ); ?>"><i class="academy-icon academy-icon--heart-o" aria-hidden="true"></i></a>
		<?php endif; ?>
	</div>
		<?php
		endif;
	?>
	<div class="academy-course__thumbnail">
		<a href="<?php echo esc_url( get_the_permalink() ); ?>">
			<img class="academy-course__thumbnail-image <?php echo esc_html( $image_class ); ?>" src="<?php echo esc_url( Academy\Helper::get_the_course_thumbnail_url( 'academy_thumbnail' ) ); ?>" alt="<?php esc_html_e( 'thumbnail', 'academy' ); ?>">
		</a>
	</div>
	<?php
		do_action( 'academy/templates/after_course_loop_header_inner' );
	?>
</div>
