<?php
/**
 * Learn-page slide-in drawer shell (PHP render path).
 *
 * Reusable shell for the curriculum / progress / Q&A / announcements drawers so
 * the markup lives in one overridable place. A child theme can override this
 * partial (or the whole learn page) without touching the plugin.
 *
 * Expected args (passed via Helper::get_template):
 *
 * @var string $drawer   Drawer name — matches the top-bar trigger's
 *                        data-academy-drawer-open value (e.g. "curriculum").
 * @var string $side     'left' | 'right'. Which edge it slides from. Default 'right'.
 * @var string $mode     Optional. 'overlay' | 'push' (curriculum drawer only).
 * @var string $title    Drawer heading.
 * @var string $subtitle Optional sub-heading (usually the course title).
 * @var string $body     Pre-rendered inner HTML (shortcode output / panel markup).
 *
 * @package Academy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$side       = isset( $side ) && 'left' === $side ? 'left' : ( isset( $side ) && 'right' === $side ? 'right' : 'right' );
$side_class = 'right' === $side ? 'academy-learn-drawer--right' : 'academy-learn-drawer--left';
$drawer_title = isset( $title ) ? $title : '';
$subtitle   = isset( $subtitle ) ? $subtitle : '';
$body       = isset( $body ) ? $body : '';
?>
<aside class="academy-learn-drawer <?php echo esc_attr( $side_class ); ?>" data-academy-drawer="<?php echo esc_attr( $drawer ); ?>"<?php if ( ! empty( $mode ) ) :
	?> data-academy-drawer-mode="<?php echo esc_attr( $mode ); ?>"<?php endif; ?>>
	<div class="academy-learn-drawer__head">
		<div class="academy-learn-drawer__titles">
			<b><?php echo esc_html( $drawer_title ); ?></b>
			<?php if ( ! empty( $subtitle ) ) : ?>
				<small><?php echo esc_html( $subtitle ); ?></small>
			<?php endif; ?>
		</div>
		<button type="button" class="academy-learn-drawer__close" data-academy-drawer-close aria-label="<?php esc_attr_e( 'Close', 'academy' ); ?>">
			<span class="academy-icon academy-icon--close"></span>
		</button>
	</div>
	<div class="academy-learn-drawer__body">
		<?php
		// $body is already-rendered shortcode / panel markup from the plugin.
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>
</aside>
