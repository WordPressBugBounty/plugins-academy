<?php
/**
 * Learn page top bar.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks.
 * @var WP_Block $block      Block.
 *
 * @package Academy
 */

use Academy\Helper;
use Academy\LearnPage\Data;
use Academy\LearnPage\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

\Academy\LearnPage\State::ensure();
$academy_learn    = Data::current();
$academy_settings = Settings::get();
$academy_course   = $academy_learn['courseId'];
$academy_ring     = 2 * M_PI * 15;

$academy_has_announcements = $academy_course && get_post_meta( $academy_course, 'academy_is_enabled_course_announcements', true );
$academy_has_qa            = $academy_course && $academy_learn['loggedIn'] && get_post_meta( $academy_course, 'academy_is_enabled_course_qa', true );
$academy_has_notes         = Helper::get_addon_active_status( 'notes' );
$academy_favorited         = $academy_course && $academy_learn['loggedIn'] ? (bool) Helper::is_favorite_course( $academy_course ) : false;

$academy_wrapper = get_block_wrapper_attributes(
	[
		'class'               => 'academy-learn-topbar',
		// Its own interactive root too, so it renders the same on its own.
		'data-wp-interactive' => 'academy/learn',
	]
);
?>
<header <?php echo $academy_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="academy-learn-topbar__start">
		<?php // Collapses / expands the course content panel. ?>
		<button
			type="button"
			class="academy-learn-icon-button academy-learn-topbar__toggle"
			data-wp-on--click="actions.toggleSidebar"
			data-wp-bind--aria-expanded="state.sidebarOpen"
			aria-controls="academy-learn-curriculum"
			aria-label="<?php esc_attr_e( 'Course content', 'academy' ); ?>"
			title="<?php esc_attr_e( 'Course content', 'academy' ); ?>"
		>
			<svg class="academy-learn-topbar__toggle-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2.5"></rect><path d="M9 4v16"></path><path class="academy-learn-topbar__toggle-chevron" d="M15 10l-2 2 2 2"></path></svg>
		</button>
		<span class="academy-learn-topbar__divider" aria-hidden="true"></span>
		<?php // Back to the course: the chevron and the course title are one link. ?>
		<a class="academy-learn-topbar__back" href="<?php echo esc_url( $academy_learn['courseUrl'] ); ?>" title="<?php esc_attr_e( 'Back to course details', 'academy' ); ?>">
			<svg class="academy-learn-topbar__back-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"></path></svg>
			<span class="screen-reader-text"><?php esc_html_e( 'Back to course:', 'academy' ); ?></span>
			<span class="academy-learn-topbar__title"><?php echo esc_html( $academy_learn['courseTitle'] ); ?></span>
		</a>
	</div>

	<div class="academy-learn-topbar__end">
		<?php
		foreach ( $academy_settings['topbarItems'] as $academy_item ) :
			if ( empty( $academy_item['enabled'] ) ) {
				continue;
			}
			switch ( $academy_item['key'] ) :
				case 'progress':
					if ( ! $academy_learn['total'] ) {
						break;
					}
					?>
					<?php
					// The ring is driven from here, not from the <circle>:
					// interactivity directives inside an SVG are skipped by the
					// server-side processor (and warn about it). stroke-dashoffset
					// is an inherited SVG property, so setting it on this wrapper
					// reaches the circle below. The track has no dash pattern, so
					// inheriting an offset leaves it alone.
					?>
					<span
						class="academy-learn-progress"
						role="img"
						style="stroke-dashoffset:<?php echo esc_attr( round( $academy_ring * ( 1 - $academy_learn['percent'] / 100 ), 2 ) ); ?>"
						data-wp-style--stroke-dashoffset="state.progressOffset"
						data-wp-bind--aria-label="state.progressLabel"
					>
						<svg width="36" height="36" viewBox="0 0 36 36" aria-hidden="true">
							<circle class="academy-learn-progress__track" cx="18" cy="18" r="15"></circle>
							<circle
								class="academy-learn-progress__fill"
								cx="18"
								cy="18"
								r="15"
								stroke-dasharray="<?php echo esc_attr( round( $academy_ring, 2 ) ); ?>"
							></circle>
						</svg>
						<span class="academy-learn-progress__text">
							<b data-wp-text="state.percentText"><?php echo esc_html( $academy_learn['percent'] . '%' ); ?></b>
							<small data-wp-text="state.progressCount">
								<?php
								echo esc_html(
									sprintf(
										/* translators: 1: completed topics, 2: all topics. */
										__( '%1$d of %2$d done', 'academy' ),
										$academy_learn['completed'],
										$academy_learn['total']
									)
								);
								?>
							</small>
						</span>
					</span>
					<?php
					break;
				case 'review':
					if ( $academy_learn['loggedIn'] && $academy_course ) :
						?>
						<button
							type="button"
							class="academy-learn-icon-button"
							hidden
							data-wp-bind--hidden="!state.canReview"
							data-wp-on--click="actions.openReview"
							aria-label="<?php esc_attr_e( 'Review this course', 'academy' ); ?>"
							title="<?php esc_attr_e( 'Review this course', 'academy' ); ?>"
						>
							<span class="academy-icon academy-icon--star-alt" aria-hidden="true"></span>
						</button>
						<?php
					endif;
					break;
				case 'notes':
					if ( $academy_has_notes && $academy_course ) :
						?>
						<div id="academy-notebook-mount" class="academy-learn-topbar__notes" data-course-id="<?php echo esc_attr( $academy_course ); ?>"></div>
						<?php
					endif;
					break;
				case 'announcements':
					if ( $academy_has_announcements ) :
						?>
						<button
							type="button"
							class="academy-learn-icon-button"
							<?php echo wp_interactivity_data_wp_context( [ 'drawer' => 'announcements' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							data-wp-on--click="actions.toggleDrawer"
							data-wp-bind--aria-expanded="state.isDrawerOpen"
							aria-controls="academy-learn-panel-announcements"
							aria-label="<?php esc_attr_e( 'Announcements', 'academy' ); ?>"
							title="<?php esc_attr_e( 'Announcements', 'academy' ); ?>"
						>
							<span class="academy-icon academy-icon--announcement" aria-hidden="true"></span>
						</button>
						<?php
					endif;
					break;
				case 'qa':
					if ( $academy_has_qa ) :
						?>
						<button
							type="button"
							class="academy-learn-icon-button"
							<?php echo wp_interactivity_data_wp_context( [ 'drawer' => 'qa' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							data-wp-on--click="actions.toggleDrawer"
							data-wp-bind--aria-expanded="state.isDrawerOpen"
							aria-controls="academy-learn-panel-qa"
							aria-label="<?php esc_attr_e( 'Questions & answers', 'academy' ); ?>"
							title="<?php esc_attr_e( 'Questions & answers', 'academy' ); ?>"
						>
							<span class="academy-icon academy-icon--qa" aria-hidden="true"></span>
						</button>
						<?php
					endif;
					break;
			endswitch;
		endforeach;
		?>

		<?php if ( $academy_settings['learnerWidth'] ) : ?>
			<button
				type="button"
				class="academy-learn-icon-button academy-learn-topbar__width"
				data-wp-on--click="actions.cycleWidth"
				data-wp-bind--title="state.widthLabel"
				data-wp-bind--aria-label="state.widthLabel"
				aria-label="<?php esc_attr_e( 'Content width', 'academy' ); ?>"
			>
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 6 2 12l6 6M16 6l6 6-6 6"></path></svg>
			</button>
		<?php endif; ?>

		<?php if ( $academy_settings['learnerTheme'] ) : ?>
			<button
				type="button"
				class="academy-learn-icon-button"
				data-wp-on--click="actions.toggleTheme"
				data-wp-bind--aria-pressed="state.isDark"
				aria-label="<?php esc_attr_e( 'Dark mode', 'academy' ); ?>"
				title="<?php esc_attr_e( 'Dark mode', 'academy' ); ?>"
			>
				<svg class="academy-learn-theme-icon academy-learn-theme-icon--moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"></path></svg>
				<svg class="academy-learn-theme-icon academy-learn-theme-icon--sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"></path></svg>
			</button>
		<?php endif; ?>

		<?php
		$academy_menu = array_filter(
			$academy_settings['menuItems'],
			static function ( $item ) use ( $academy_learn ) {
				return ! empty( $item['enabled'] ) && ( 'favorite' !== $item['key'] || $academy_learn['loggedIn'] );
			}
		);
		if ( $academy_menu && $academy_course ) :
			?>
			<div class="academy-learn-menu" data-wp-class--is-open="state.menuOpen">
				<button
					type="button"
					class="academy-learn-icon-button"
					data-wp-on--click="actions.toggleMenu"
					data-wp-bind--aria-expanded="state.menuOpen"
					aria-haspopup="menu"
					aria-label="<?php esc_attr_e( 'More options', 'academy' ); ?>"
				>
					<span class="academy-icon academy-icon--three-dots-menu" aria-hidden="true"></span>
				</button>
				<div class="academy-learn-menu__list" role="menu">
					<?php
					foreach ( $academy_menu as $academy_item ) :
						if ( 'favorite' === $academy_item['key'] ) :
							?>
							<button
								type="button"
								role="menuitemcheckbox"
								class="academy-learn-menu__item"
								<?php echo wp_interactivity_data_wp_context( [ 'favorited' => $academy_favorited ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								aria-checked="<?php echo $academy_favorited ? 'true' : 'false'; ?>"
								data-wp-bind--aria-checked="context.favorited"
								data-wp-on--click="actions.toggleFavorite"
							>
								<span class="academy-icon academy-icon--empty-star" aria-hidden="true" data-wp-class--academy-icon--star="context.favorited"></span>
								<span data-wp-text="state.favoriteLabel"><?php echo esc_html( $academy_favorited ? __( 'Remove from favorites', 'academy' ) : __( 'Add to favorites', 'academy' ) ); ?></span>
							</button>
							<?php
						elseif ( 'share' === $academy_item['key'] ) :
							?>
							<div id="academy-lesson-share-btn" class="academy-learn-menu__item academy-learn-menu__item--share" data-course-permalink="<?php echo esc_url( $academy_learn['courseUrl'] ); ?>"></div>
							<?php
						elseif ( 'exit' === $academy_item['key'] ) :
							?>
							<a role="menuitem" class="academy-learn-menu__item" href="<?php echo esc_url( $academy_learn['courseUrl'] ); ?>">
								<span class="academy-icon academy-icon--close" aria-hidden="true"></span>
								<?php esc_html_e( 'Close course', 'academy' ); ?>
							</a>
							<?php
						endif;
					endforeach;
					?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</header>
