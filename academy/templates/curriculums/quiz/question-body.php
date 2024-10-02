<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
} ?>
<div class="academy-lesson-quiz__body question-no-<?php echo esc_html(
	$question_count
); ?>">
	<h1><?php echo esc_html(
		$question_with_option['question']->question_title
	); ?></h1>
	<?php // load the short-answer if the question type is short-ans
	if ( 'shortAnswer' === $question_with_option['question']->question_type ) {
		\Academy\Helper::get_template(
			'curriculums/quiz/questions/short-answer.php',
			[
				'attempt_id' => $last_attempt->attempt_id,
				'question_id' => $question_with_option['question']->question_id,
			]
		);
	} ?>
	<div class="academy-lesson-quiz-answer">

	<?php foreach ( $question_with_option['options'] as $option ) {
		$question_type = $question_with_option['question']->question_type;
		$question_id = $question_with_option['question']->question_id;
		$attempt_id = $last_attempt->attempt_id;
		$ans_title = $option->answer_title;
		$ans_id = $option->answer_id;
		$view_format = $option->view_format;

		// handle others type of questions
		$default_template_args = [
			'attempt_id' => $attempt_id,
			'question_id' => $question_id,
			'ans_id' => $ans_id,
			'ans_title' => $ans_title,
			'question_type' => $question_type,
		];
		$default_template_path = '';

		switch ( $view_format ) {
			case 'text':
				$default_template_path = 'curriculums/quiz/questions/text-only.php';
				break;

			case 'textAndImage':
				$url = wp_get_attachment_image_url( $option->image_id );
				$default_template_args = wp_parse_args(
					[ 'url' => $url ],
					$default_template_args
				);
				$default_template_path = 'curriculums/quiz/questions/text-and-image.php';
				break;

			case 'image':
				$url = wp_get_attachment_image_url( $option->image_id );
				$default_template_args = wp_parse_args(
					[ 'url' => $url ],
					$default_template_args
				);
				$default_template_path = 'curriculums/quiz/questions/image-only.php';
				break;

			default:
		}//end switch

		if ( 'fillInTheBlanks' === $question_type ) {
			$process_question = \AcademyQuizzes\Helper::process_the_fill_in_the_blanks_question_title(
				$ans_title,
				$question_id,
				$attempt_id
			);
			$default_template_path =
				'curriculums/quiz/questions/fill-in-the-blanks.php';
			$default_template_args = [ 'processQuestion' => $process_question ];
		} elseif ( 'imageAnswer' === $question_type ) {
			$url = wp_get_attachment_url( $option->image_id );
			$default_template_args = wp_parse_args(
				[ 'url' => $url ],
				$default_template_args
			);
			$default_template_path = 'curriculums/quiz/questions/image-ans.php';
		}

		\Academy\Helper::get_template(
			$default_template_path,
			$default_template_args
		);
	}//end foreach
	// end foreach
	?>

	</div>
</div>
