<?php
/**
 * Teaching courses: an instructor's courses, in the Teaching view.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks.
 * @var WP_Block $block      Block.
 *
 * @package Academy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_user_logged_in() ) {
	return;
}

$academy_home = academy_frontend_dashboard_home_data();
if ( empty( $academy_home['is_teaching'] ) ) {
	return;
}
?>
<div <?php echo get_block_wrapper_attributes( [ 'class' => 'academy-dash-teaching' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( ! empty( $attributes['title'] ) ) : ?>
		<h3 class="academy-dash-teaching__title"><?php echo esc_html( $attributes['title'] ); ?></h3>
	<?php endif; ?>
	<?php \Academy\Helper::get_template( 'frontend-dashboard/partials/teaching-courses.php', [ 'course_ids' => $academy_home['course_ids'] ] ); ?>
</div>
