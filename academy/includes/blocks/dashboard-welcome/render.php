<?php
/**
 * Dashboard welcome: a greeting with the person's name.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks.
 * @var WP_Block $block      Block.
 *
 * @package Academy
 */

use Academy\FrontendDashboard\Dashboard;
use Academy\FrontendDashboard\Settings;

// Switched off on Customize → Dashboard, for both dashboards.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_user_logged_in() || ! Settings::get()['welcome'] ) {
	return;
}

$academy_wrapper = get_block_wrapper_attributes( [ 'class' => 'academy-dashboard-welcome academy-dash-welcome' ] );
?>
<div <?php echo $academy_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( ! empty( $attributes['showAvatar'] ) ) : ?>
		<img class="academy-dash-welcome__avatar" src="<?php echo esc_url( get_avatar_url( get_current_user_id(), [ 'size' => 112 ] ) ); ?>" alt="" width="56" height="56" />
	<?php endif; ?>
	<div class="academy-dash-welcome__text">
		<?php Dashboard::render_welcome( (string) ( $attributes['text'] ?? '' ) ); ?>
		<?php if ( ! empty( $attributes['showDate'] ) ) : ?>
			<p class="academy-dash-welcome__date"><?php echo esc_html( wp_date( get_option( 'date_format' ) ) ); ?></p>
		<?php endif; ?>
	</div>
</div>
