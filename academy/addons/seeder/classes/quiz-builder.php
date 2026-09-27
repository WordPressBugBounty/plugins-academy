<?php
/**
 * Builds a sample quiz (post) with one question of every supported type and
 * their answer rows, recording every created id in the run context.
 *
 * Mirrors the field layout of the built-in course importer
 * (includes/ajax/course-import/importers/quiz*.php), extended with image
 * answers and answer ordering.
 *
 * @package AcademySeeder\Classes
 */

namespace AcademySeeder\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QuizBuilder {

	/**
	 * Default quiz meta, mirroring the importer.
	 *
	 * @var array<string, mixed>
	 */
	private static $meta_defaults = [
		'academy_quiz_time'                          => 0,
		'academy_quiz_time_unit'                     => 'minutes',
		'academy_quiz_hide_quiz_time'                => '',
		'academy_quiz_feedback_mode'                 => 'default',
		'academy_quiz_passing_grade'                 => 80,
		'academy_quiz_max_questions_for_answer'      => 0,
		'academy_quiz_max_attempts_allowed'          => 0,
		'academy_quiz_auto_start'                    => false,
		'academy_quiz_questions_order'               => 'rand',
		'academy_quiz_hide_question_number'          => '',
		'academy_quiz_short_answer_characters_limit' => 500,
		'academy_quiz_explanation_enabled'           => false,
		'academy_quiz_skip_question_showing'         => false,
		'academy_quiz_show_full_answer_content'      => false,
		'academy_quiz_questions_layout'              => 'single',
		'academy_quiz_questions'                     => [],
	];

	/**
	 * Create a quiz and its questions/answers.
	 *
	 * @param SeederContext $context Run context (records created ids).
	 * @param string        $title   Quiz title.
	 *
	 * @return int Quiz post id, or 0 on failure.
	 */
	public static function build( SeederContext $context, $title ) {
		$quiz_id = wp_insert_post( [
			'post_title'   => $title,
			'post_type'    => 'academy_quiz',
			'post_content' => SeederData::paragraph(),
			'post_status'  => 'publish',
		] );

		if ( is_wp_error( $quiz_id ) || ! $quiz_id ) {
			return 0;
		}

		update_post_meta( $quiz_id, Manager::MARKER_META, 1 );
		$context->record( 'quiz', $quiz_id );

		$meta          = self::$meta_defaults;
		$question_list = [];
		$order         = 0;

		foreach ( SeederData::question_definitions() as $definition ) {
			$question_id = self::insert_question( $context, $quiz_id, $definition, $order );
			if ( $question_id ) {
				$question_list[] = [
					'id'    => $question_id,
					'title' => $definition['question'] ?? '',
				];
				++$order;
			}
		}

		$meta['academy_quiz_questions'] = $question_list;

		foreach ( $meta as $key => $value ) {
			add_post_meta( $quiz_id, $key, $value, true );
		}

		return (int) $quiz_id;
	}

	/**
	 * Insert one question row + its answers.
	 *
	 * @param SeederContext $context    Run context.
	 * @param int           $quiz_id    Owning quiz id.
	 * @param array         $definition Question blueprint from SeederData.
	 * @param int           $order      Zero-based question order.
	 *
	 * @return int Question id, or 0.
	 */
	private static function insert_question( SeederContext $context, $quiz_id, array $definition, $order ) {
		global $wpdb;

		$type  = $definition['slug'] ?? 'singleChoice';
		$title = $definition['question'] ?? '';

		$settings = [
			'display_points'  => true,
			'answer_required' => true,
			'randomize'       => 'ordering' !== $type,
		];

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$inserted = $wpdb->insert( $wpdb->prefix . 'academy_quiz_questions', [
			'quiz_id'                 => $quiz_id,
			'question_title'          => str_replace( '{dash}', '______', $title ),
			'question_name'           => '',
			'question_content'        => '',
			'question_explanation'    => '',
			'question_status'         => 'publish',
			'question_level'          => '',
			'question_type'           => $type,
			'question_score'          => 1.0,
			'question_negative_score' => 0,
			'question_image_id'       => 0,
			'question_settings'       => wp_json_encode( $settings ),
			'question_order'          => $order,
			'question_created_at'     => current_time( 'mysql' ),
			'question_updated_at'     => current_time( 'mysql' ),
		] );
		// phpcs:enable

		if ( false === $inserted ) {
			return 0;
		}

		$question_id = (int) $wpdb->insert_id;
		self::insert_answers( $context, $quiz_id, $question_id, $definition );

		return $question_id;
	}

	/**
	 * Insert the answer rows for a question, branching on type the same way the
	 * importer + quiz addon expect.
	 *
	 * @param SeederContext $context     Run context.
	 * @param int           $quiz_id     Owning quiz id.
	 * @param int           $question_id Owning question id.
	 * @param array         $definition  Question blueprint.
	 *
	 * @return void
	 */
	private static function insert_answers( SeederContext $context, $quiz_id, $question_id, array $definition ) {
		$type    = $definition['slug'] ?? 'singleChoice';
		$title   = $definition['question'] ?? '';
		$options = $definition['options'] ?? [];
		$correct = $definition['correctAnswer'] ?? '';
		$correct = is_array( $correct ) ? $correct : [ $correct ];

		// Open-ended answers carry no options — nothing to record.
		if ( 'shortAnswer' === $type ) {
			return;
		}

		if ( 'fillInTheBlanks' === $type ) {
			$answer_title = preg_match( '/\{dash\}/', $title ) ? $title : $title . ' {dash}';
			self::insert_answer_row( $quiz_id, $question_id, $type, [
				'answer_title'   => $answer_title,
				'answer_content' => implode( '|', array_map( 'strval', $correct ) ),
				'is_correct'     => 0,
				'view_format'    => 'text',
				'answer_order'   => 0,
			] );
			return;
		}

		$position = 0;
		foreach ( $options as $option ) {
			$slug     = $option['slug'] ?? '';
			$is_image = ! empty( $option['image'] );
			$image_id = 0;

			if ( $is_image ) {
				$image_id = SeederData::create_placeholder_image( $option['text'] ?? 'Answer', $position );
				if ( $image_id ) {
					update_post_meta( $image_id, Manager::MARKER_META, 1 );
					$context->record( 'attachment', $image_id );
				}
			}

			// For ordering, every option is part of the correct sequence and the
			// position is what matters. For the rest, correctness is by slug.
			$is_correct = ( 'ordering' === $type ) ? 1 : ( in_array( $slug, $correct, true ) ? 1 : 0 );

			self::insert_answer_row( $quiz_id, $question_id, $type, [
				'answer_title'   => $option['text'] ?? '',
				'answer_content' => '',
				'is_correct'     => $is_correct,
				'image_id'       => $image_id,
				'view_format'    => $is_image ? 'image' : 'text',
				'answer_order'   => $position,
			] );

			++$position;
		}//end foreach
	}

	/**
	 * Low-level answer insert.
	 *
	 * @param int    $quiz_id     Owning quiz id.
	 * @param int    $question_id Owning question id.
	 * @param string $type        Question type.
	 * @param array  $fields      Column overrides.
	 *
	 * @return void
	 */
	private static function insert_answer_row( $quiz_id, $question_id, $type, array $fields ) {
		global $wpdb;

		$row = wp_parse_args( $fields, [
			'answer_title'   => '',
			'answer_content' => '',
			'is_correct'     => 0,
			'image_id'       => 0,
			'view_format'    => 'text',
			'answer_order'   => 0,
		] );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert( $wpdb->prefix . 'academy_quiz_answers', [
			'quiz_id'           => $quiz_id,
			'question_id'       => $question_id,
			'question_type'     => $type,
			'answer_title'      => $row['answer_title'],
			'answer_content'    => $row['answer_content'],
			'is_correct'        => $row['is_correct'],
			'image_id'          => $row['image_id'],
			'view_format'       => $row['view_format'],
			'answer_order'      => $row['answer_order'],
			'answer_created_at' => current_time( 'mysql' ),
			'answer_updated_at' => current_time( 'mysql' ),
		] );
		// phpcs:enable
	}
}
