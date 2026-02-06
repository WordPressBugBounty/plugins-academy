<?php
namespace AcademyChatgpt\CourseImport\Importers;

use Exception;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class QuizQuestion implements Interfaces\Insertable {
	public int $id;
	public string $title;
	protected string $name = '';
	protected string $content = '';
	protected string $status = 'publish';
	protected string $level = '';
	protected string $type;
	protected float $score = 1.0;
	protected array $settings = [
		'display_points' => true,
		'answer_required' => true,
		'randomize' => true,
	];
	protected int $order = 0;
	public Quiz $quiz;

	protected array $options;
	protected array $answer;

	protected object $wpdb;

	public function __construct( array $quiz_data, Quiz $quiz ) {
		$ans = $quiz_data['correctAnswer'];
		$this->title = $quiz_data['question'] ?? '';
		$this->type  = $quiz_data['slug'] ?? '';
		$this->quiz  = $quiz;
		$this->options = $quiz_data['options'] ?? [];
		$this->answer  = is_array( $ans ?? '' ) ? $ans : [ $ans ];
		$this->wpdb    = $GLOBALS['wpdb'];
	}

	public function insert() : int {

		$res = $this->wpdb->insert( $this->wpdb->prefix . 'academy_quiz_questions', [
			'quiz_id'             => $this->quiz->id,
			'question_title'      => str_replace( '{dash}', '______', $this->title ),
			'question_name'       => $this->name,
			'question_content'    => $this->content,
			'question_status'     => $this->status,
			'question_level'      => $this->level,
			'question_type'       => $this->type,
			'question_score'      => $this->score,
			'question_settings'   => wp_json_encode( $this->settings ),
			'question_order'      => $this->order,
			'question_created_at' => current_time( 'mysql' ),
			'question_updated_at' => current_time( 'mysql' ),
		] );

		if ( false === $res ) {
			throw new Exception( __( 'Error.', 'academy' ) );
		}

		$this->id = $this->wpdb->insert_id;
		$this->insert_answers();

		return $this->id;
	}

	protected function insert_answers() : void {
		if ( 'fillInTheBlanks' === $this->type ) {
			( new QuizAnswer( $this->type, [], $this->answer, $this ) )->insert();
			return;
		}

		foreach ( $this->options as $option ) {
			( new QuizAnswer( $this->type, $option, $this->answer, $this ) )->insert();
		}
	}
}
