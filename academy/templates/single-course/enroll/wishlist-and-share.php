<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

	$share_config = array(
		'title' => get_the_title(),
		'text'  => get_the_excerpt(),
		'image' => \Academy\Helper::get_the_course_thumbnail_url( 'post-thumbnail' ),
	);
	?>

<div class="academy-widget-enroll__wishlist-and-share">
	<?php
	if ( $is_show_wishlist ) :
		?>
	<button class="academy-btn academy-btn--bg-white-border academy-add-to-wishlist-btn academy-btn--lg" data-course-id="<?php the_ID(); ?>" data-show-label="true">
		<?php if ( $is_already_in_wishlist ) : ?>
			<i class="academy-icon academy-icon--heart" aria-hidden="true"></i>
				<?php esc_html_e( 'WishListed', 'academy' ); ?>
		<?php else : ?>
			<i class="academy-icon academy-icon--heart-o" aria-hidden="true"></i>
			<?php esc_html_e( 'WishList', 'academy' ); ?>
		<?php endif; ?>
	</button>
		<?php
		endif;

	if ( $is_show_course_share ) :
		?>

	<button class="academy-btn academy-btn--bg-white-border academy-share-button academy-btn--lg"><i class="academy-icon academy-icon--share"></i><?php esc_html_e( 'Share', 'academy' ); ?></button>
	<div class="academy-share-wrap" data-social-share-config="<?php echo esc_attr( wp_json_encode( $share_config ) ); ?>">
		<button class="academy-social-share academy_facebook"><i class="academy-icon academy-icon--facebook" aria-hidden="true"></i></button>
		<button class="academy-social-share academy_linkedin"><i class="academy-icon academy-icon--linkedIn" aria-hidden="true"></i></button>
		<button class="academy-social-share academy_twitter"><i class="academy-icon academy-icon--twitter" aria-hidden="true"></i></button>
		<button class="academy-social-share academy_pinterest"><i class="academy-icon academy-icon--pinterest" aria-hidden="true"></i></button>
		<button class="academy-social-share academy_gmail"><i class="academy-icon academy-icon--mail" aria-hidden="true"></i></button>
	</div>
		<?php
		endif;
	?>
</div>
