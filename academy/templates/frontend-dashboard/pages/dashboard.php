<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php do_action( 'academy/templates/frontend_dashboard/before_analytics_cards' ); ?>
<div class="academy-analytics-cards">
	<?php foreach ( $data as $key => $item ) : ?>
		<a class="academy-analytics-cards--card" href="<?php echo isset( $item['link'] ) ? esc_url( $item['link'] ) : ''; ?>">
			<div class="academy-analytics-card--icon icon-<?php echo esc_attr( $item['color'] ); ?>"><span class="<?php echo esc_attr( $item['icon'] ); ?>"></span></div>
			<div class="academy-analytics-card--data">
				<h2 class="academy-analytics-card--value"><?php echo esc_html( $item['value'] ); ?></h2>
				<p class="academy-analytics-card--label"><?php echo esc_html( $item['label'] ); ?><span></span></p>
			</div>
		</a>
	<?php endforeach; ?>
	<?php do_action( 'academy/templates/frontend_dashboard/after_analytics_card_item' ); ?>
</div>

<?php do_action( 'academy/templates/frontend_dashboard/after_analytics_cards' ); ?>

<?php
// The "my courses" table belongs to the teaching view; a learner standing here
// was being shown an empty instructor table.
if ( ! empty( $is_teaching ) ) {
	\Academy\Helper::get_template( 'frontend-dashboard/partials/teaching-courses.php', [ 'course_ids' => $course_ids ] );
}
