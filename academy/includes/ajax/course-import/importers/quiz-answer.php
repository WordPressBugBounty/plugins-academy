<?php
namespace Academy\Ajax\CourseImport\Importers;

use Exception;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class QuizAnswer implements Interfaces\Insertable {
	public int $id;
	protected string $type;
	protected string $title;
	protected string $slug;
	protected array $answer;
	public QuizQuestion $quiz_question;
	protected object $wpdb;

	public function __construct( string $type, array $option, array $answer, QuizQuestion $quiz_question ) {
		$this->type  = $type;
		$this->title = $option['text'] ?? '';
		$this->slug  = $option['slug'] ?? '';
		$this->wpdb  = $GLOBALS['wpdb'];
		$this->answer = $answer;
		$this->quiz_question = $quiz_question;
	}

	public function insert() : int {

		$res = $this->wpdb->insert( $this->wpdb->prefix . 'academy_quiz_answers', [
			'quiz_id'             => $this->quiz_question->quiz->id,
			'question_id'         => $this->quiz_question->id,
			'question_type'       => $this->type,
			'answer_title'        => $this->answer_title(),
			'answer_content'      => 'fillInTheBlanks' === $this->type ? $this->answer[0] : '',
			'is_correct'          => ( in_array( $this->slug, $this->answer ) && 'fillInTheBlanks' !== $this->type ) ? 1 : 0,
			'view_format'       => 'text',
			'answer_order'      => 0,
			'answer_created_at' => current_time( 'mysql' ),
			'answer_updated_at' => current_time( 'mysql' ),
		] );

		if ( false === $res ) {
			throw new Exception( __( 'Error.', 'academy' ) );
		}

		$this->id = $this->wpdb->insert_id;

		return $this->id;
	}
	protected function answer_title() : string {
		if ( 'fillInTheBlanks' !== $this->type ) {
			return $this->title;
		}
		if ( ! preg_match( '|\{dash\}|im', $this->quiz_question->title ) ) {
			return $this->quiz_question->title . '  {dash}';
		}
		return $this->quiz_question->title;
	}
}
