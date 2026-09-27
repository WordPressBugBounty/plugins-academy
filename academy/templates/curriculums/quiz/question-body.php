<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

$msg = $is_required ? esc_html__( 'required', 'academy' ) : esc_html__( 'optional', 'academy' );
$question_heading = sprintf(
	'%d. %s (%s)',
	$question_count,
	$question_with_option['question']->question_title,
	$msg
);
?>

<div class="academy-lesson-quiz__body question-no-<?php echo esc_html(
	$question_count
); ?>">
	<h3><?php echo wp_kses_post( $question_heading ); ?></h3>
	<?php
	$question_audio_id = $question_with_option['question']->question_audio_id ?? 0;
	if ( $question_audio_id && '0' !== (string) $question_audio_id ) :
		$audio_url = wp_get_attachment_url( $question_audio_id );
		if ( $audio_url ) :
			?>
			<div class="academy-quiz-audio" data-audio-src="<?php echo esc_url( $audio_url ); ?>"></div>
			<?php
		endif;
	endif;
	$question_content = $question_with_option['question']->question_content ?? '';
	if ( '' !== trim( wp_strip_all_tags( (string) $question_content ) ) ) :
		?>
		<span class="academy-quiz-content"><?php echo wp_kses_post( $question_content ); ?></span>
		<?php
	endif;
	$question_image_id = $question_with_option['question']->question_image_id ?? 0;
	if ( $question_image_id && '0' !== (string) $question_image_id ) :
		$question_image_url = wp_get_attachment_url( $question_image_id );
		if ( $question_image_url ) :
			$image_settings = json_decode( $question_with_option['question']->question_settings ?? '' )->image ?? null;
			$allowed_sizes = array( 'small', 'medium', 'large', 'full' );
			$allowed_aligns = array( 'none', 'left', 'center', 'right' );
			$image_size = in_array( $image_settings->size ?? '', $allowed_sizes, true ) ? $image_settings->size : 'full';
			$image_alignment = in_array( $image_settings->alignment ?? '', $allowed_aligns, true ) ? $image_settings->alignment : 'none';
			$image_alt = $image_settings->alt ?? '';
			$image_caption = $image_settings->caption ?? '';
			?>
			<div class="academy-quiz-question-image-wrap academy-quiz-question-image--size-<?php echo esc_attr( $image_size ); ?> academy-quiz-question-image--align-<?php echo esc_attr( $image_alignment ); ?>">
				<img class="academy-quiz-question-image" src="<?php echo esc_url( $question_image_url ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>" />
				<?php if ( '' !== trim( (string) $image_caption ) ) : ?>
					<div class="academy-quiz-question-image-caption"><?php echo esc_html( $image_caption ); ?></div>
				<?php endif; ?>
			</div>
			<?php
		endif;
	endif;
	// load the short-answer if the question type is short-ans
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
