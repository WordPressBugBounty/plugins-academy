<?php

use AcademyQuizzes\Classes\Query;

if ( ! function_exists( 'academy_quizzes_added_frontend_dashboard_menu' ) ) {
	function academy_quizzes_added_frontend_dashboard_menu( $menu ) {
		if ( current_user_can( 'manage_academy_instructor' ) ) {
			$menu['quizzes'] = array(
				'label' => __( 'Quizzes', 'academy' ),
				'icon'  => 'academy-icon academy-icon--quiz-alt',
				'public' => true,
				'priority' => 36,
				'child_items' => [
					'attempts'           => array(
						'label'         => __( 'Quiz Attempts', 'academy' ),
						'public' => true,
						'priority' => 36,
					),
				]
			);
		}
		return $menu;
	}
}

if ( ! function_exists( 'academy_quizzes_frontend_dashboard_quizzes_page' ) ) {
	function academy_quizzes_frontend_dashboard_quizzes_page() {
		\Academy\Helper::get_template(
			'frontend-dashboard/pages/quizzes.php',
		);
	}
}

if ( ! function_exists( 'academy_quizzes_curriculum_quiz_content' ) ) {
	function academy_quizzes_curriculum_quiz_content( $course_id, $quiz_id ) {

		$quiz = get_post( $quiz_id );

		do_action( 'academy/templates/curriculums/before_render_quiz_content', $quiz, $course_id, $quiz_id );

		$has_permission = \Academy\Helper::has_permission_to_access_curriculum( $course_id );

		if ( ! apply_filters( 'academy/templates/curriculums/has_access_quiz_content', $has_permission ) ) {
			return;
		}

		if ( $quiz ) {
			\Academy\Helper::get_template(
				'curriculums/quiz.php',
				array(
					'course_id' => $course_id,
					'quiz_id' => $quiz_id,
				)
			);
		} else {
			\Academy\Helper::get_template( 'curriculums/not-found.php' );
		}

		do_action( 'academy/templates/curriculums/after_render_quiz_content', $quiz, $course_id, $quiz_id );
	}
}//end if

if ( ! function_exists( 'academy_quizzes_start_quiz' ) ) {
	function academy_quizzes_start_quiz() {
		if ( ! wp_verify_nonce( $_POST['security'], 'academy_nonce' ) ) {
			wp_die( 'Nonce verification failed.' );
		}

		$course_id = isset( $_POST['course_id'] ) ? sanitize_text_field( $_POST['course_id'] ) : '';
		$quiz_id = isset( $_POST['quiz_id'] ) ? sanitize_text_field( $_POST['quiz_id'] ) : '';
		$referer_url = \Academy\Helper::sanitize_referer_url( wp_get_referer() );

		$quiz_attempt = array(
			'course_id' => $course_id,
			'quiz_id' => $quiz_id,
		);

		\AcademyQuizzes\Classes\Query::quiz_attempt_insert( $quiz_attempt );

		wp_safe_redirect( $referer_url );
		exit;
	}
}//end if

if ( ! function_exists( 'academy_quizzes_submit_quiz' ) ) {
	function academy_quizzes_submit_quiz() {
		if ( ! wp_verify_nonce( $_POST['security'], 'academy_nonce' ) ) {
			wp_die( 'Nonce verification failed.' );
		}

		$course_id = isset( $_POST['course_id'] ) ? sanitize_text_field( $_POST['course_id'] ) : '';
		$attempt_id = isset( $_POST['attempt'] ) ? sanitize_text_field( array_key_first( $_POST['attempt'] ) ) : 0;
		$referer_url = \Academy\Helper::sanitize_referer_url( wp_get_referer() );
		$answers = isset( $_POST['attempt'] ) ? $_POST['attempt'][ $attempt_id ]['quiz_question'] : 0;
		$quiz_id = isset( $_POST['quiz_id'] ) ? sanitize_text_field( $_POST['quiz_id'] ) : '';

		if ( ! $attempt_id ) {
			$args = array(
				'course_id' => $course_id,
				'quiz_id' => $quiz_id,
				'attempt_id' => $attempt_id,
				'total_questions' => count( \AcademyQuizzes\Classes\Query::get_questions_by_quid_id( $quiz_id ) ),
				'total_answered_questions' => 0,
				'total_marks' => Query::get_total_questions_marks_by_quiz_id( $quiz_id ),
				'earned_marks' => 0,
				'attempt_status' => 'failed',
			);
			\AcademyQuizzes\Classes\Query::quiz_attempt_insert( $args );
			wp_safe_redirect( $referer_url );
			exit();
		}

		if ( ! \AcademyQuizzes\Helper::check_if_quiz_time_is_expired_by_quiz_id_and_attempt_id( $course_id, $quiz_id, $attempt_id ) ) {
			foreach ( $answers as $question_id => $answer_id ) {
				$question_details = Query::get_question_details_by_question_id( $question_id );
				$question_details = ( ! empty( $question_details ) ) ? current( $question_details ) : '';
				$quiz_id = $question_details->quiz_id;
				$question_type = $question_details->question_type;
				$is_correct = Query::is_quiz_correct_answer( $answer_id, $question_id );

				if ( 'multipleChoice' === $question_type ) {
					$IDs = ( is_array( $answer_id ) ? $answer_id : explode( ',', $answer_id ) );
					$answer_id = implode( ',', $IDs );
					$is_correct = (int) \AcademyQuizzes\Classes\Query::is_quiz_correct_answer( $IDs, $question_id );
				} elseif ( 'imageAnswer' === $question_type ) {
					$given_ans = [];
					foreach ( $answer_id['imageAnswer'] as $id => $image_answer ) {
						if ( ! empty( $image_answer ) ) {
							$given_ans[ $id ] = $image_answer;
						}
					}
					$is_correct = (int) \AcademyQuizzes\Classes\Query::is_image_answer_quiz_correct_answer( $given_ans, $question_id );
					$answer_id  = wp_json_encode( $given_ans );
				} elseif ( 'shortAnswer' === $question_type ) {
					$answer_id = $answer_id['shortAnswer'];
					$is_correct = (int) \AcademyQuizzes\Classes\Query::is_quiz_correct_answer( $answer_id, $question_id );
				} elseif ( 'fillInTheBlanks' === $question_type ) {
					$is_correct = (int) \AcademyQuizzes\Classes\Query::is_fill_in_the_blanks_quiz_correct_answer( $answer_id['fillInTheBlanks'], $question_id );
					$answer_id = implode( ',', $answer_id['fillInTheBlanks'] );
					$is_correct = ( $answer_id ) ? $is_correct : 0;
				}//end if

				$attempt_answer = array(
					'user_id' => get_current_user_id(),
					'quiz_id' => $question_details->quiz_id,
					'question_id' => $question_id,
					'attempt_id' => $attempt_id,
					'answer' => $answer_id,
					'question_mark' => $question_details->question_score,
					'achieved_mark' => $question_details->question_score,
					'minus_mark' => 0,
					'is_correct' => 1,
				);

				if ( ! $is_correct ) {
					$attempt_answer['achieved_mark'] = 0;
					$attempt_answer['minus_mark'] = 0;
					$attempt_answer['is_correct'] = 0;
				}

				Query::quiz_attempt_answer_insert( $attempt_answer );
			}//end foreach

			$args = array(
				'course_id' => $course_id,
				'quiz_id' => $quiz_id,
				'attempt_id' => $attempt_id,
				'total_questions' => count( \AcademyQuizzes\Classes\Query::get_questions_by_quid_id( $quiz_id ) ),
				'total_answered_questions' => count( $answers ),
			);
			$total_questions_marks = Query::get_total_questions_marks_by_quiz_id( $quiz_id );
			$total_earned_marks = Query::get_quiz_attempt_answers_earned_marks( get_current_user_id(), $attempt_id );
			$args['total_marks'] = $total_questions_marks;
			$args['earned_marks'] = $total_earned_marks;
			$passing_grade = (int) get_post_meta( $quiz_id, 'academy_quiz_passing_grade', true );
			$earned_percentage  = \Academy\Helper::calculate_percentage( $total_questions_marks, $total_earned_marks );
			$args['attempt_status'] = ( $earned_percentage >= $passing_grade ? 'passed' : 'failed' );
			if ( 'failed' === $args['attempt_status'] && Query::is_required_manually_reviewed( $quiz_id ) ) {
				$args['attempt_status'] = 'pending';
			}
			$args['attempt_info'] = wp_json_encode(array(
				'total_correct_answers' => Query::get_total_quiz_attempt_correct_answers( $attempt_id )
			));

			$quiz_data = (object) array(
				'user_id'           => get_current_user_id(),
				'course_id'         => $course_id,
				'quiz_id'           => $quiz_id,
				'assignment_id'     => null,
				'result_for'        => 'quiz',
				'earned_percentage' => ( $args['earned_marks'] / $args['total_marks'] ) * 100,
			);

			do_action( 'academy_quizzes/after_quiz_insert', $quiz_data );

			Query::quiz_attempt_insert( $args );

			wp_safe_redirect( $referer_url );
			exit;
		}//end if

		$args = array(
			'course_id' => $course_id,
			'quiz_id' => $quiz_id,
			'attempt_id' => $attempt_id,
			'total_questions' => 0,
			'total_answered_questions' => 0,
			'total_marks' => 0,
			'earned_marks' => 0,
			'attempt_status' => 'time expired',
		);
		\AcademyQuizzes\Classes\Query::quiz_attempt_insert( $args );
		wp_safe_redirect( $referer_url );
		exit;
	}
}//end if
