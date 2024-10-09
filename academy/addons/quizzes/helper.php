<?php
namespace AcademyQuizzes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helper {
	public static function prepare_given_answer( $question_type, $attempt_item ) {
		if ( 'imageAnswer' === $question_type ) {
			$response = array();
			$answers = json_decode( $attempt_item->given_answer, true );
			foreach ( $answers as $id => $answer ) {
				$quiz_answer = Classes\Query::get_quiz_answer( $id );
				$image = wp_get_attachment_image_src( $quiz_answer->image_id );
				$response[] = array(
					'id'            => $id,
					'image_url'     => $image[0],
					'answer_title' => $answer,
				);
			}
			return $response;
		} elseif ( 'fillInTheBlanks' === $question_type ) {
			$replacement = explode( ',', $attempt_item->given_answer );
			return array(
				array(
					'answer_title' => preg_replace_callback('/\{dash\}/', function( $match ) use ( $replacement ) {
						static $index = 0;
						$value = '{' . trim( $replacement[ $index ] ) . '}';
						$index++;
						return $value;
					}, $attempt_item->correct_answer),
				)
			);
		} elseif ( 'shortAnswer' === $question_type ) {
			return array(
				array(
					'answer_title' => $attempt_item->given_answer,
				)
			);
		}//end if
		$answers = \AcademyQuizzes\Classes\Query::get_quiz_all_answer_title_by_ids( explode( ',', $attempt_item->given_answer ) );
		// convert image id to image url
		foreach ( $answers as $answer ) {
			if ( $answer->image_id ) {
				$image = wp_get_attachment_image_src( $answer->image_id );
				$answer->image_url = $image[0];
			}
			unset( $answer->image_id );
		}

		return $answers;
	}
	public static function prepare_correct_answer( $question_type, $attempt_item ) {
		if ( 'imageAnswer' === $question_type ) {
			$image_answers = \AcademyQuizzes\Classes\Query::get_quiz_answers_by_question_id( $attempt_item->question_id, 'imageAnswer' );
			$response = [];
			foreach ( $image_answers as $image_answer ) {
				$image = wp_get_attachment_image_src( $image_answer->image_id );
				$response[] = array(
					'id'            => $image_answer->answer_id,
					'image_url'     => $image[0],
					'answer_title' => $image_answer->answer_title,
				);
			}
			return $response;
		} elseif ( 'fillInTheBlanks' === $question_type ) {
			$replacement = explode( '|', $attempt_item->answer_content );
			return array(
				'answer_title' => preg_replace_callback('/\{dash\}/', function( $match ) use ( $replacement ) {
					static $index = 0;
					$value = '{' . trim( $replacement[ $index ] ) . '}';
					$index++;
					return $value;
				}, $attempt_item->correct_answer),
			);
		} elseif ( 'shortAnswer' === $question_type ) {
			return array(
				'answer_title' => __( 'Manually Reviewed Required.', 'academy' ),
			);
		}//end if
		$answers = \AcademyQuizzes\Classes\Query::get_quiz_correct_answers( $attempt_item->question_id, $question_type );

		// convert image id to image url
		foreach ( $answers as $answer ) {
			if ( $answer->image_id ) {
				$image = wp_get_attachment_image_src( $answer->image_id );
				$answer->image_url = $image[0];
			}
			unset( $answer->image_id );
		}

		return $answers;
	}

	public static function render_quiz_by_course_and_quiz_id( $course_id, $quiz_id ) {
		$has_permission = \Academy\Helper::has_permission_to_access_curriculum( $course_id );

		if ( $has_permission ) {
			$question_order = get_post_meta( $quiz_id, 'academy_quiz_questions_order', true );
			$questions = \AcademyQuizzes\Classes\Query::get_questions_by_quid_id( $quiz_id, $question_order );
			$order = get_post_meta( $quiz_id, 'academy_quiz_questions_order', true );
			if ( count( $questions ) && $order ) {
				do_action( 'academy_quizzes/frontend/before_render_quiz', $course_id, $quiz_id );
				$settings = \AcademyQuizzes\Classes\Query::get_question_settings_by_quiz_id( $quiz_id, $order );
				return( [
					'questions' => $questions,
					'settings' => $settings,
					'title' => get_the_title( $quiz_id ),
				] );
			}
			return( esc_html__( 'Sorry, something went wrong!', 'academy' ) );
		}//end if
		return( esc_html__( 'Access Denied', 'academy' ) );
	}

	public static function get_quiz_attempt_answer_details_by_attempt_id( $attempt_id ) {
		$user_id = get_current_user_id();
		$prepare_attempt_details = [];
		$attempt_details = \AcademyQuizzes\Classes\Query::get_quiz_attempt_details( $attempt_id, $user_id );
		foreach ( $attempt_details as $attempt_item ) {
			$attempt_item->given_answer = self::prepare_given_answer( $attempt_item->question_type, $attempt_item );
			$attempt_item->is_correct = (bool) $attempt_item->is_correct;
			$attempt_item->correct_answer = self::prepare_correct_answer( $attempt_item->question_type, $attempt_item );
			$prepare_attempt_details[ $attempt_item->attempt_answer_id ] = $attempt_item;
		}

		return $prepare_attempt_details;
	}

	public static function process_the_fill_in_the_blanks_question_title( $question, $question_id, $attempt_id ) {
		$total_dash = substr_count( $question, '{dash}' );
		while ( $total_dash-- ) {
			$replace = '<input type = "text" class="academy-blanks-input" name = "attempt[' . $attempt_id . '][quiz_question][' . $question_id . '][fillInTheBlanks][' . $total_dash . ']" >';
			$question = preg_replace( '/\{dash\}/', $replace, $question, 1 );
		}
		return $question;
	}

	public static function get_successful_attempts_from_attempts( $attempts, $quiz_id ): array {
		$successful_attempts = [];

		foreach ( $attempts as $attempt ) {
			if ( 'pending' === $attempt->attempt_status && \AcademyQuizzes\Classes\Query::is_required_manually_reviewed( $quiz_id ) ) {
				// if short answer
				$successful_attempts[] = $attempt;
			} elseif ( 'pending' !== $attempt->attempt_status ) {
				// if correct or wrong
				$successful_attempts[] = $attempt;
			}
		}

		return $successful_attempts;
	}

	public static function get_questions_with_options_from_quiz_array( $quiz ) {
		$questions_with_options = [];

		foreach ( $quiz['questions'] as $question ) {
			$question_with_options = array(
				'question' => $question,
				'options' => \AcademyQuizzes\Classes\Query::get_quiz_answers_by_question_id( $question->question_id, $question->question_type ),
			);

			$questions_with_options[] = $question_with_options;
		}

		return $questions_with_options;
	}

	public static function check_if_quiz_time_is_expired_by_quiz_id_and_attempt_id( $course_id, $quiz_id, $attempt_id ) {
		$quiz = self::render_quiz_by_course_and_quiz_id( $course_id, $quiz_id );
		$attempt = \AcademyQuizzes\Classes\Query::get_quiz_attempt( $attempt_id );

		$quiz_time = $quiz['settings']['quiz_time'];
		$unit = strtoupper( $quiz['settings']['quiz_time_unit'][0] );

		$current_time = new \DateTime();
		$start_time   = new \DateTime( $attempt->attempt_started_at );
		$interval     = ( 'H' === $unit || 'M' === $unit || 'S' === $unit ) ? "PT{$quiz_time}{$unit}" : "P{$quiz_time}{$unit}";
		$end_time     = $start_time->add( new \DateInterval( $interval ) );

		if ( $end_time < $current_time && $quiz_time ) {
			return true;
		}

		return false;
	}

	public static function has_attempt_quiz( $course_id, $quiz_id, $user_id = '' ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		return \AcademyQuizzes\Classes\Query::get_quiz_attempt_details_by_quiz_id( array(
			'course_id' => $course_id,
			'quiz_id' => $quiz_id,
			'user_id' => $user_id
		) );
	}
}
