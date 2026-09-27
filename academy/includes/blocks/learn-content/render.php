<?php
/**
 * Learn page content: the topic on screen. This is the part that changes when
 * a student moves to another topic.
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
$academy_types    = [
	'lesson'         => __( 'Lesson', 'academy' ),
	'quiz'           => __( 'Quiz', 'academy' ),
	'quizpress_quiz' => __( 'Quiz', 'academy' ),
	'assignment'     => __( 'Assignment', 'academy' ),
	'meeting'        => __( 'Live class', 'academy' ),
	'zoom'           => __( 'Live class', 'academy' ),
	'booking'        => __( 'Booking', 'academy' ),
];
$academy_position = 0;
foreach ( $academy_learn['topics'] as $academy_index => $academy_topic ) {
	if ( $academy_topic['key'] === $academy_learn['currentKey'] ) {
		$academy_position = $academy_index + 1;
		break;
	}
}
$academy_is_previewable = $academy_learn['topicId'] && 'lesson' === $academy_learn['type']
	? (bool) \Academy\Helper::get_lesson_meta( (int) $academy_learn['topicId'], 'is_previewable' )
	: false;

$academy_wrapper = get_block_wrapper_attributes(
	[
		'class'                 => 'academy-learn-content',
		'id'                    => 'academy-learn-main',
		'tabindex'              => '-1',
		// A region the router swaps must be an interactive root of its own.
		'data-wp-interactive'   => 'academy/learn',
		'data-wp-router-region' => 'academy-learn-content',
	]
);
?>
<main <?php echo $academy_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="academy-learn-content__inner">
		<?php if ( $academy_settings['contentHeader'] && $academy_current ) : ?>
			<header class="academy-learn-content__header">
				<span class="academy-learn-content__eyebrow">
					<span class="<?php echo esc_attr( 'academy-icon academy-icon--' . $academy_current['icon'] ); ?>" aria-hidden="true"></span>
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: topic type, e.g. Lesson. 2: topic number. 3: number of topics. */
							__( '%1$s %2$d of %3$d', 'academy' ),
							$academy_types[ $academy_current['type'] ] ?? __( 'Topic', 'academy' ),
							$academy_position,
							$academy_learn['total']
						)
					);
					?>
				</span>
				<h1 class="academy-learn-content__title"><?php echo esc_html( $academy_current['title'] ); ?></h1>
			</header>
		<?php endif; ?>

		<div class="academy-learn-content__body academy-lessons-content">
			<?php if ( ! is_user_logged_in() && ! $academy_is_previewable ) : ?>
				<?php $academy_paid = 'paid' === \Academy\Helper::get_course_type( $academy_learn['courseId'] ); ?>
				<div class="academy-learn-gate">
					<span class="academy-learn-gate__icon" aria-hidden="true"><span class="academy-icon academy-icon--lock"></span></span>
					<h2 class="academy-learn-gate__title">
						<?php echo $academy_paid ? esc_html__( 'Purchase or enroll to continue', 'academy' ) : esc_html__( 'Enroll to continue', 'academy' ); ?>
					</h2>
					<p class="academy-learn-gate__text"><?php esc_html_e( 'This topic is for enrolled students. Log in and enroll to start learning.', 'academy' ); ?></p>
					<div class="academy-learn-gate__actions">
						<a class="academy-btn academy-btn--bg-purple" href="<?php echo esc_url( $academy_learn['courseUrl'] ); ?>">
							<?php echo $academy_paid ? esc_html__( 'View pricing', 'academy' ) : esc_html__( 'Go to the course', 'academy' ); ?>
						</a>
						<button type="button" class="academy-btn academy-btn--border-purple academy-btn-popup-login"><?php esc_html_e( 'Log in', 'academy' ); ?></button>
					</div>
				</div>
				<div id="academy-btn-popup-login"></div>
			<?php elseif ( ! $academy_learn['type'] || ! $academy_learn['topicId'] || ! $academy_learn['courseId'] ) : ?>
				<?php \Academy\Helper::get_template( 'curriculums/not-found.php' ); ?>
			<?php else : ?>
				<?php if ( ! is_user_logged_in() ) : ?>
					<div class="academy-learn-gate academy-learn-gate--inline">
						<span class="academy-icon academy-icon--lock" aria-hidden="true"></span>
						<p class="academy-learn-gate__text"><?php esc_html_e( 'You’re previewing this lesson. Log in to track your progress.', 'academy' ); ?></p>
						<button type="button" class="academy-btn academy-btn--sm academy-btn--bg-purple academy-btn-popup-login"><?php esc_html_e( 'Log in', 'academy' ); ?></button>
					</div>
					<div id="academy-btn-popup-login"></div>
				<?php endif; ?>
				<?php
				// The topic itself, drawn by its own type: lesson, quiz, assignment…
				do_action( 'academy/templates/curriculum/' . $academy_learn['type'] . '_content', $academy_learn['courseId'], $academy_learn['topicId'] );
				?>
				<?php if ( 'lesson' === $academy_learn['type'] && is_user_logged_in() && \Academy\Helper::get_settings( 'is_enabled_academy_lessons_comment', false ) ) : ?>
					<section class="academy-learn-comments" aria-label="<?php esc_attr_e( 'Lesson discussion', 'academy' ); ?>">
						<h2 class="academy-learn-comments__title"><?php esc_html_e( 'Discussion', 'academy' ); ?></h2>
						<?php echo do_shortcode( '[academy_course_lesson_comments]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</section>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
</main>
