<?php
/**
 * Learn page footer: previous topic, mark as complete, next topic.
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

\Academy\LearnPage\State::ensure();
$academy_learn    = Data::current();
$academy_settings = Settings::get();
$academy_current  = $academy_learn['current'];

$academy_nav = static function ( $topic, $direction ) {
	$is_next = 'next' === $direction;
	$label   = $is_next ? __( 'Next', 'academy' ) : __( 'Previous', 'academy' );
	$classes = 'academy-learn-footer__nav academy-learn-footer__nav--' . $direction;
	$arrow   = sprintf( '<span class="academy-icon academy-icon--arrow-%s" aria-hidden="true"></span>', $is_next ? 'right' : 'left' );

	if ( ! $topic || '' === $topic['url'] ) {
		printf(
			'<span class="%1$s is-disabled">%2$s<span class="academy-learn-footer__text"><small>%3$s</small><b>%4$s</b></span>%5$s</span>',
			esc_attr( $classes ),
			$is_next ? '' : $arrow, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html( $label ),
			esc_html( $is_next ? __( 'End of course', 'academy' ) : __( 'Start of course', 'academy' ) ),
			$is_next ? $arrow : '' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
		return;
	}

	printf(
		'<a class="%1$s" href="%2$s" rel="%3$s" %4$s data-wp-on--click="actions.navigate" data-wp-on--mouseenter="actions.prefetch">%5$s<span class="academy-learn-footer__text"><small>%6$s</small><b>%7$s</b></span>%8$s</a>',
		esc_attr( $classes ),
		esc_url( $topic['url'] ),
		$is_next ? 'next' : 'prev',
		wp_interactivity_data_wp_context( [ 'type' => $topic['type'] ] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$is_next ? '' : $arrow, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		esc_html( $label ),
		esc_html( $topic['title'] ),
		$is_next ? $arrow : '' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
};

$academy_wrapper = get_block_wrapper_attributes(
	[
		'class'                 => 'academy-learn-footer',
		// A region the router swaps must be an interactive root of its own.
		'data-wp-interactive'   => 'academy/learn',
		'data-wp-router-region' => 'academy-learn-footer',
	]
);
?>
<footer <?php echo $academy_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php $academy_nav( $academy_learn['previous'], 'previous' ); ?>

	<?php if ( $academy_settings['markComplete'] && $academy_current && $academy_learn['loggedIn'] && $academy_current['accessible'] ) : ?>
		<button
			type="button"
			class="academy-learn-footer__complete<?php echo $academy_current['completed'] ? ' is-completed' : ''; ?>"
			<?php
			echo wp_interactivity_data_wp_context( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				[
					'key'  => $academy_current['key'],
					'id'   => $academy_current['id'],
					'type' => $academy_current['type'],
				]
			);
			?>
			data-wp-on--click="actions.toggleComplete"
			data-wp-class--is-completed="state.isCompleted"
			data-wp-bind--aria-pressed="state.isCompleted"
			data-wp-bind--disabled="state.busy"
			aria-pressed="<?php echo $academy_current['completed'] ? 'true' : 'false'; ?>"
		>
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>
			<span data-wp-text="state.completeLabel"><?php echo $academy_current['completed'] ? esc_html__( 'Completed', 'academy' ) : esc_html__( 'Mark as complete', 'academy' ); ?></span>
		</button>
	<?php else : ?>
		<span class="academy-learn-footer__spacer"></span>
	<?php endif; ?>

	<?php $academy_nav( $academy_learn['next'], 'next' ); ?>
</footer>
