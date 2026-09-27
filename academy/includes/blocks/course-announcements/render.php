<?php
/**
 * Course Announcements block. Announcements are for the people taking the
 * course, so they show to enrolled students and to those who manage it.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$academy_course_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : (int) get_the_ID();
if ( ! $academy_course_id || 'academy_courses' !== get_post_type( $academy_course_id ) ) {
	return;
}

$academy_user_id  = get_current_user_id();
$academy_can_read = current_user_can( 'edit_post', $academy_course_id )
	|| ( $academy_user_id && \Academy\Helper::is_enrolled( $academy_course_id, $academy_user_id ) );
if ( ! $academy_can_read ) {
	return;
}

$academy_announcements = \Academy\Helper::get_course_announcements_by_course_id( $academy_course_id );
if ( empty( $academy_announcements ) || ! is_array( $academy_announcements ) ) {
	return;
}
$academy_announcements = array_slice( $academy_announcements, 0, max( 1, min( 50, (int) $attributes['max'] ) ) );
?>
<div <?php echo get_block_wrapper_attributes( [ 'style' => \Academy\Blocks::academy_colors_style( $attributes ) ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core. ?>>
	<?php foreach ( $academy_announcements as $academy_announcement ) : ?>
		<article class="academy-course-announcements__item">
			<header class="academy-course-announcements__head">
				<h3 class="academy-course-announcements__title"><?php echo esc_html( get_the_title( $academy_announcement ) ); ?></h3>
				<time class="academy-course-announcements__date" datetime="<?php echo esc_attr( get_the_date( 'c', $academy_announcement ) ); ?>"><?php echo esc_html( get_the_date( '', $academy_announcement ) ); ?></time>
			</header>
			<div class="academy-course-announcements__content"><?php echo wp_kses_post( wpautop( $academy_announcement->post_content ) ); ?></div>
		</article>
	<?php endforeach; ?>
</div>
