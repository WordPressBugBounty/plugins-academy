<?php
/**
 * Learn page: the frame around the top bar, curriculum, content and footer,
 * and the store they share.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks.
 * @var WP_Block $block      Block.
 *
 * @package Academy
 */

use Academy\LearnPage\Data;
use Academy\LearnPage\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$academy_learn    = Data::current();
$academy_settings = Settings::get();
$academy_sidebar  = ! empty( $attributes['sidebar'] ) ? $attributes['sidebar'] : $academy_settings['sidebar'];
$academy_width    = ! empty( $attributes['width'] ) ? $attributes['width'] : $academy_settings['width'];

\Academy\LearnPage\State::ensure();

// Colours set on Customize → Learn Page, for light and dark mode.
$academy_color_vars = static function ( array $colors ) {
	$vars = '';
	foreach ( $colors as $key => $value ) {
		if ( '' !== $value ) {
			$vars .= sprintf( '--academy-learn-%s-color:%s;', $key, $value );
		}
	}
	return $vars;
};
$academy_light_vars = $academy_color_vars( $academy_settings['colors']['light'] );
$academy_dark_vars  = $academy_color_vars( $academy_settings['colors']['dark'] );

$academy_wrapper = get_block_wrapper_attributes(
	[
		'class'                         => 'academy-learn',
		'data-wp-interactive'           => 'academy/learn',
		'data-wp-init'                  => 'callbacks.init',
		'data-wp-watch'                 => 'callbacks.syncServerState',
		'data-sidebar'                  => $academy_sidebar,
		'data-width'                    => $academy_width,
		'data-academy-theme'            => $academy_settings['theme'],
		'data-wp-bind--data-width'      => 'state.width',
		'data-wp-bind--data-academy-user-theme' => 'state.userTheme',
		'data-wp-class--is-sidebar-open' => 'state.sidebarOpen',
		'data-wp-class--has-drawer'     => 'state.drawer',
		'data-wp-on-document--keydown'  => 'actions.onKeydown',
		'style'                         => sprintf( '--academy-learn-sidebar:%dpx;', $academy_settings['sidebarWidth'] ),
	]
);
?>
<div <?php echo $academy_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $academy_light_vars || $academy_dark_vars ) : ?>
		<style>
			<?php
			// Only hex colours reach here (Settings::colors()).
			if ( $academy_light_vars ) {
				echo '.academy-learn{' . $academy_light_vars . '}'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			if ( $academy_dark_vars ) {
				echo '.academy-learn[data-academy-user-theme="dark"]{' . $academy_dark_vars . '}'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</style>
	<?php endif; ?>
	<script>
		( function () {
			try {
				var saved = window.localStorage.getItem( 'academy_theme' );
				var fallback = <?php echo wp_json_encode( $academy_settings['theme'] ); ?>;
				var theme = 'dark' === saved || 'light' === saved ? saved : fallback;
				if ( 'system' === theme ) {
					theme = window.matchMedia && window.matchMedia( '(prefers-color-scheme: dark)' ).matches ? 'dark' : 'light';
				}
				document.body.setAttribute( 'data-academy-user-theme', theme );
				document.currentScript.parentNode.setAttribute( 'data-academy-user-theme', theme );
				var width = window.localStorage.getItem( 'academy_learn_width' );
				if ( width ) {
					document.currentScript.parentNode.setAttribute( 'data-width', width );
				}
			} catch ( e ) {}
		} )();
	</script>
	<span class="academy-learn__loading" aria-hidden="true" data-wp-class--is-active="state.navigating"></span>
	<a class="academy-learn__skip" href="#academy-learn-main"><?php esc_html_e( 'Skip to the lesson', 'academy' ); ?></a>
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<button
		type="button"
		class="academy-learn__scrim"
		tabindex="-1"
		aria-hidden="true"
		data-wp-on--click="actions.closeOverlays"
	></button>

	<?php
	$academy_drawers = [];
	if ( $academy_learn['courseId'] && get_post_meta( $academy_learn['courseId'], 'academy_is_enabled_course_announcements', true ) ) {
		$academy_drawers['announcements'] = [ __( 'Announcements', 'academy' ), do_shortcode( '[academy_course_announcements]' ) ];
	}
	if ( $academy_learn['courseId'] && $academy_learn['loggedIn'] && get_post_meta( $academy_learn['courseId'], 'academy_is_enabled_course_qa', true ) ) {
		$academy_drawers['qa'] = [ __( 'Questions & Answers', 'academy' ), do_shortcode( '[academy_course_questions_answers]' ) ];
	}
	foreach ( $academy_drawers as $academy_drawer => $academy_panel ) :
		?>
		<aside
			class="academy-learn-panel"
			id="<?php echo esc_attr( 'academy-learn-panel-' . $academy_drawer ); ?>"
			aria-label="<?php echo esc_attr( $academy_panel[0] ); ?>"
			<?php echo wp_interactivity_data_wp_context( [ 'drawer' => $academy_drawer ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			data-wp-class--is-open="state.isDrawerOpen"
			data-wp-bind--inert="!state.isDrawerOpen"
		>
			<header class="academy-learn-panel__head">
				<span class="academy-learn-panel__titles">
					<b><?php echo esc_html( $academy_panel[0] ); ?></b>
					<small><?php echo esc_html( $academy_learn['courseTitle'] ); ?></small>
				</span>
				<button type="button" class="academy-learn-icon-button" data-wp-on--click="actions.closeOverlays" aria-label="<?php esc_attr_e( 'Close', 'academy' ); ?>">
					<span class="academy-icon academy-icon--close" aria-hidden="true"></span>
				</button>
			</header>
			<div class="academy-learn-panel__body">
				<?php echo $academy_panel[1]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</aside>
	<?php endforeach; ?>

	<div class="academy-learn__notice" role="status" aria-live="polite" data-wp-class--is-visible="state.notice">
		<span data-wp-text="state.notice"></span>
		<button type="button" class="academy-learn-icon-button" data-wp-on--click="actions.dismissNotice" aria-label="<?php esc_attr_e( 'Dismiss', 'academy' ); ?>">
			<span class="academy-icon academy-icon--close" aria-hidden="true"></span>
		</button>
	</div>
</div>
