<?php
namespace AcademyChatgpt\CourseImport\Importers;

use Exception;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class Quiz implements Interfaces\Insertable {
	public ?int $id = null;
	protected string $title;
	protected string $content;
	protected string $status = 'publish';
	protected array $quizzes;
	protected array $meta = [
		'academy_quiz_time' => 0,
		'academy_quiz_time_unit' => '',
		'academy_quiz_hide_quiz_time' => '',
		'academy_quiz_feedback_mode' => 'default',
		'academy_quiz_passing_grade' => 0,
		'academy_quiz_max_questions_for_answer' => 0,
		'academy_quiz_max_attempts_allowed' => 0,
		'academy_quiz_auto_start' => false,
		'academy_quiz_questions_order' => 'rand',
		'academy_quiz_hide_question_number' => '',
		'academy_quiz_short_answer_characters_limit' => 0,
		'academy_quiz_questions' => []
	];

	public function __construct( string $title, string $content, array $quizzes, ?int $id = null ) {
		$this->id    = $id;
		$this->title = $title;
		$this->content = $content;
		$this->quizzes = $quizzes;
	}

	public function insert() : int {
		$data = [
			'post_title' => $this->title,
			'post_type' => 'academy_quiz',
			'post_content' => $this->content,
			'post_status' => $this->status
		];

		if ( ! empty( $this->id ) ) {
			$data['ID'] = $this->id;
			$this->delete_question( $this->id );
		}

		$id = wp_insert_post( $data );

		if ( is_wp_error( $id ) ) {
			throw new Exception( __( 'Error.', 'academy' ) );
		}

		$this->id = $id;
		$this->insert_quizzes();
		$this->insert_meta();

		return $this->id;
	}

	protected function insert_quizzes() : void {
		foreach ( $this->quizzes as $quiz ) {
			$question_id = ( new QuizQuestion( $quiz, $this ) )->insert();
			$this->meta['academy_quiz_questions'][] = [
				'id' => $question_id,
				'title' => $quiz['question'] ?? '',
			];
		}
	}
	protected function insert_meta() : void {
		foreach ( $this->meta as $key => $value ) {
			add_post_meta( $this->id, $key, $value, true );
		}
	}

	public static function delete( int $id ) : bool {
		if ( ! empty( wp_delete_post( $id, true ) ) ) {
			return $this->delete_question( $id );
		}
		return false;
	}

	public static function delete_question( int $id ) : bool {
		$GLOBALS['wpdb']->delete( $GLOBALS['wpdb']->prefix . 'academy_quiz_questions', [ 'quiz_id' => $id ] );
		$GLOBALS['wpdb']->delete( $GLOBALS['wpdb']->prefix . 'academy_quiz_answers', [ 'quiz_id' => $id ] );
		return true;
	}
}
