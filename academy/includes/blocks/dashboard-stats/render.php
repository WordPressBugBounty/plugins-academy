<?php
/**
 * Dashboard numbers: cards for the view someone is in.
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

$academy_home    = academy_frontend_dashboard_home_data();
// Automatic (0): every card on one row, up to four. Never more columns than
// cards, so a row is not left half empty.
$academy_count   = max( 1, count( $academy_home['data'] ) );
$academy_columns = (int) ( $attributes['columns'] ?? 0 );
$academy_columns = $academy_columns ? min( 4, $academy_columns, $academy_count ) : ( $academy_count <= 4 ? $academy_count : 3 );
$academy_style   = in_array( $attributes['cardStyle'] ?? 'card', [ 'card', 'soft', 'minimal' ], true ) ? $attributes['cardStyle'] : 'card';

$academy_wrapper = get_block_wrapper_attributes(
	[
		'class' => 'academy-dash-stats is-style-' . $academy_style . ( empty( $attributes['showIcons'] ) ? ' has-no-icons' : '' ),
		'style' => '--academy-dash-stats-columns:' . $academy_columns . ';' . \Academy\Blocks::academy_colors_style( $attributes ),
	]
);
?>
<div <?php echo $academy_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="academy-analytics-cards academy-dash-stats__grid">
		<?php foreach ( $academy_home['data'] as $academy_item ) : ?>
			<a class="academy-analytics-cards--card academy-dash-stats__card" href="<?php echo esc_url( $academy_item['link'] ?? '' ); ?>">
				<?php if ( ! empty( $attributes['showIcons'] ) ) : ?>
					<span class="academy-analytics-card--icon icon-<?php echo esc_attr( $academy_item['color'] ); ?>"><span class="<?php echo esc_attr( $academy_item['icon'] ); ?>" aria-hidden="true"></span></span>
				<?php endif; ?>
				<span class="academy-analytics-card--data">
					<b class="academy-analytics-card--value"><?php echo esc_html( $academy_item['value'] ); ?></b>
					<span class="academy-analytics-card--label"><?php echo esc_html( $academy_item['label'] ); ?></span>
				</span>
			</a>
		<?php endforeach; ?>
		<?php do_action( 'academy/templates/frontend_dashboard/after_analytics_card_item' ); ?>
	</div>
	<?php
	// Add-ons put their home page panels here (meetings, points…).
	do_action( 'academy/templates/frontend_dashboard/after_analytics_cards' );
	?>
</div>
