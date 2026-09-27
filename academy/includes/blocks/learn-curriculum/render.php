<?php
/**
 * Learn page curriculum: sections and topics, with progress.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks.
 * @var WP_Block $block      Block.
 *
 * @package Academy
 */

use Academy\LearnPage\Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

\Academy\LearnPage\State::ensure();
$academy_learn = Data::current();

if ( ! function_exists( 'academy_learn_curriculum_topic' ) ) {
	/**
	 * One topic row.
	 *
	 * @param array $topic      Topic.
	 * @param array $attributes Block attributes.
	 * @param array $learn      Learn page data.
	 * @return void
	 */
	function academy_learn_curriculum_topic( array $topic, array $attributes, array $learn ) {
		$current = $topic['key'] === $learn['currentKey'];
		$classes = 'academy-learn-topic' . ( $current ? ' is-current' : '' ) . ( $topic['completed'] ? ' is-completed' : '' ) . ( $topic['accessible'] ? '' : ' is-locked' );
		?>
		<li
			class="<?php echo esc_attr( $classes ); ?>"
			<?php
			echo wp_interactivity_data_wp_context( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				[
					'key'  => $topic['key'],
					'id'   => $topic['id'],
					'type' => $topic['type'],
				]
			);
			?>
			data-wp-class--is-current="state.isCurrent"
			data-wp-class--is-completed="state.isCompleted"
		>
			<?php if ( $learn['loggedIn'] && $topic['accessible'] ) : ?>
				<button
					type="button"
					class="academy-learn-topic__check"
					data-wp-on--click="actions.toggleComplete"
					aria-pressed="<?php echo $topic['completed'] ? 'true' : 'false'; ?>"
					data-wp-bind--aria-pressed="state.isCompleted"
					aria-label="<?php echo esc_attr( sprintf( /* translators: %s: topic title. */ __( 'Completed: %s', 'academy' ), $topic['title'] ) ); ?>"
				>
					<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>
				</button>
			<?php else : ?>
				<span class="academy-learn-topic__check" aria-hidden="true">
					<?php if ( ! $topic['accessible'] ) : ?>
						<span class="academy-icon academy-icon--lock"></span>
					<?php endif; ?>
				</span>
			<?php endif; ?>
			<a
				class="academy-learn-topic__link"
				href="<?php echo esc_url( $topic['url'] ); ?>"
				<?php echo $current ? 'aria-current="page"' : ''; ?>
				data-wp-bind--aria-current="state.ariaCurrent"
				data-wp-on--click="actions.navigate"
				data-wp-on--mouseenter="actions.prefetch"
				data-wp-on--focus="actions.prefetch"
			>
				<span class="<?php echo esc_attr( 'academy-icon academy-icon--' . $topic['icon'] . ' academy-learn-topic__icon' ); ?>" aria-hidden="true"></span>
				<span class="academy-learn-topic__title"><?php echo esc_html( $topic['title'] ); ?></span>
				<?php if ( ! empty( $attributes['showDuration'] ) && '' !== $topic['duration'] && '00:00:00' !== $topic['duration'] ) : ?>
					<span class="academy-learn-topic__meta"><?php echo esc_html( $topic['duration'] ); ?></span>
				<?php endif; ?>
			</a>
		</li>
		<?php
	}
}//end if

$academy_wrapper = get_block_wrapper_attributes(
	[
		'class'           => 'academy-learn-curriculum',
		// Its own interactive root too, so it renders the same on its own.
		'data-wp-interactive' => 'academy/learn',
		'id'              => 'academy-learn-curriculum',
		'aria-label'      => __( 'Course content', 'academy' ),
		'data-wp-bind--inert' => 'state.sidebarInert',
	]
);
?>
<nav <?php echo $academy_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="academy-learn-curriculum__head">
		<b><?php esc_html_e( 'Course content', 'academy' ); ?></b>
		<button type="button" class="academy-learn-icon-button academy-learn-curriculum__close" data-wp-on--click="actions.toggleSidebar" aria-label="<?php esc_attr_e( 'Close course content', 'academy' ); ?>">
			<span class="academy-icon academy-icon--close" aria-hidden="true"></span>
		</button>
	</div>
	<?php if ( ! empty( $attributes['showProgress'] ) && $academy_learn['total'] ) : ?>
		<div class="academy-learn-curriculum__progress">
			<span class="academy-learn-bar" aria-hidden="true">
				<span class="academy-learn-bar__fill" style="<?php echo esc_attr( 'width:' . $academy_learn['percent'] . '%' ); ?>" data-wp-style--width="state.percentText"></span>
			</span>
			<span class="academy-learn-curriculum__count">
				<b data-wp-text="state.percentComplete"><?php echo esc_html( sprintf( /* translators: %d: percent. */ __( '%d%% complete', 'academy' ), $academy_learn['percent'] ) ); ?></b>
				<span data-wp-text="state.leftText"><?php echo esc_html( sprintf( /* translators: %d: topics left. */ _n( '%d topic left', '%d topics left', $academy_learn['total'] - $academy_learn['completed'], 'academy' ), $academy_learn['total'] - $academy_learn['completed'] ) ); ?></span>
			</span>
		</div>
	<?php endif; ?>

	<?php if ( ! $academy_learn['sections'] ) : ?>
		<p class="academy-learn-curriculum__empty"><?php esc_html_e( 'This course has no topics yet.', 'academy' ); ?></p>
	<?php endif; ?>

	<div class="academy-learn-curriculum__sections">
		<?php
		foreach ( $academy_learn['sections'] as $academy_section ) :
			$academy_has_current = false;
			$academy_done        = 0;
			$academy_count       = 0;
			$academy_keys        = [];
			foreach ( $academy_section['items'] as $academy_item ) {
				$academy_topics = 'group' === $academy_item['kind'] ? $academy_item['topics'] : [ $academy_item ];
				foreach ( $academy_topics as $academy_topic ) {
					++$academy_count;
					$academy_keys[] = $academy_topic['key'];
					$academy_done        += $academy_topic['completed'] ? 1 : 0;
					$academy_has_current  = $academy_has_current || $academy_topic['key'] === $academy_learn['currentKey'];
				}
			}
			$academy_open = $academy_has_current || 1 === count( $academy_learn['sections'] );
			?>
			<section
				class="academy-learn-section"
				<?php
				echo wp_interactivity_data_wp_context( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					[
						'open' => $academy_open,
						'keys' => $academy_keys,
					]
				);
				?>
				data-wp-class--is-open="context.open"
				data-wp-watch="callbacks.openCurrentSection"
			>
				<button
					type="button"
					class="academy-learn-section__toggle"
					data-wp-on--click="actions.toggleSection"
					aria-expanded="<?php echo $academy_open ? 'true' : 'false'; ?>"
					data-wp-bind--aria-expanded="context.open"
				>
					<span class="academy-learn-section__title"><?php echo esc_html( $academy_section['title'] ); ?></span>
					<span class="academy-learn-section__count"><?php echo esc_html( $academy_done . '/' . $academy_count ); ?></span>
					<span class="academy-icon academy-icon--angle-down academy-learn-section__caret" aria-hidden="true"></span>
				</button>
				<ul class="academy-learn-section__topics">
					<?php
					foreach ( $academy_section['items'] as $academy_item ) :
						if ( 'group' === $academy_item['kind'] ) :
							?>
							<li class="academy-learn-group">
								<span class="academy-learn-group__title"><?php echo esc_html( $academy_item['title'] ); ?></span>
								<ul>
									<?php
									foreach ( $academy_item['topics'] as $academy_topic ) {
										academy_learn_curriculum_topic( $academy_topic, $attributes, $academy_learn );
									}
									?>
								</ul>
							</li>
							<?php
						else :
							academy_learn_curriculum_topic( $academy_item, $attributes, $academy_learn );
						endif;
					endforeach;
					?>
				</ul>
			</section>
		<?php endforeach; ?>
	</div>
</nav>
