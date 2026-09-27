<?php
namespace AcademyQuizpress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Frontend {
	public static function init() {
		$self = new self();
		add_action( 'wp_enqueue_scripts', array( $self, 'maybe_enqueue_quizpress_player' ), 20 );
		add_action( 'academy/templates/curriculum/quizpress_quiz_content', array( $self, 'render_curriculum_content' ), 10, 2 );
	}

	/**
	 * Enqueues QuizPress's own frontend assets (already registered by
	 * QuizPress itself — this addon never bundles or duplicates it) on the
	 * Academy curriculum player (SPA) page, but only when the course being
	 * viewed actually has a quizpress_quiz topic attached, so the
	 * `quizpress.question-answer-widget.content` /
	 * `quizpress.submit-question-answer` hooks it registers are available to
	 * the player's QuizPressQuizContent component. Not needed for PHP-render
	 * mode — there, QuizPress's own [quizpress_quiz] shortcode (used by
	 * render_curriculum_content() below) enqueues its bundle itself.
	 */
	public function maybe_enqueue_quizpress_player() {
		global $post;

		if ( ! $post || 'academy_courses' !== get_post_type( $post->ID ) ) {
			return;
		}

		if ( ! API::course_has_linked_quiz( $post->ID ) ) {
			return;
		}

		wp_enqueue_style( 'quizpress-frontend-icon' );
		wp_enqueue_style( 'quizpress-frontend-style' );
		wp_enqueue_script( 'quizpress-frontend-scripts' );
		// Premium question types. Not registered on the free build or without a
		// licence, and enqueueing an unregistered handle does nothing.
		wp_enqueue_script( 'quizpress-pro-frontend-scripts' );
	}

	/**
	 * PHP-render Learn Page content for a quizpress_quiz topic. Embeds
	 * QuizPress's own [quizpress_quiz] shortcode — its real quiz-taking and
	 * result UI — rather than reimplementing any of it here, mirroring the
	 * "QuizPress owns quizpress_quiz rendering/data" boundary the SPA-mode
	 * integration already follows. Mirrors
	 * AcademyQuizzes's academy_quizzes_curriculum_quiz_content().
	 *
	 * @param int $course_id Academy course ID.
	 * @param int $quiz_id   QuizPress quiz ID.
	 */
	public function render_curriculum_content( $course_id, $quiz_id ) {
		$quiz = get_post( $quiz_id );

		$has_permission = \Academy\Helper::has_permission_to_access_curriculum( $course_id );

		if ( ! apply_filters( 'academy/templates/curriculums/has_access_quiz_content', $has_permission ) ) {
			return;
		}

		if ( $quiz ) {
			\Academy\Helper::get_template(
				'curriculums/quizpress-quiz.php',
				array(
					'course_id' => $course_id,
					'quiz_id'   => $quiz_id,
				)
			);
		} else {
			\Academy\Helper::get_template( 'curriculums/not-found.php' );
		}
	}
}
