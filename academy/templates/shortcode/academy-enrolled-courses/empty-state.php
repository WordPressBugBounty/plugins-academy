<?php
/**
 * Empty state for the [academy_enrolled_courses] shortcode — the visitor is
 * logged in but has no enrolled courses, or none matching the status filter.
 *
 * This template can be overridden by copying it to
 * yourtheme/academy/shortcode/academy-enrolled-courses/empty-state.php
 *
 * @var string $message    Explanation of why the grid is empty.
 * @var string $browse_url Link to the course archive.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="academy-empty-state">
	<p class="academy-empty-state__message"><?php echo esc_html( $message ); ?></p>
	<?php if ( $browse_url ) : ?>
		<a href="<?php echo esc_url( $browse_url ); ?>" class="academy-button academy-button--primary">
			<?php esc_html_e( 'Browse Courses', 'academy' ); ?>
		</a>
	<?php endif; ?>
</div>
