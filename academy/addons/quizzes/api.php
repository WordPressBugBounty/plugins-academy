<?php
namespace AcademyQuizzes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyQuizzes\Classes\Query;

class API {

	public static function init() {
		$self = new self();
		API\QuizQuestions::init();
		API\QuizAnswers::init();
		API\QuizAttempts::init();
		add_filter( 'academy/api/user/meta_values', array( $self, 'user_quiz_analytics' ) );
		add_filter( 'rest_prepare_academy_quiz', array( $self, 'add_author_name_to_rest_response' ), 10, 3 );
		add_filter( 'rest_prepare_academy_quiz', [ $self, 'decode_special_characters_from_title' ], 10, 3 );
	}
	public function user_quiz_analytics( $values ) {
		$values['total_quizzes'] = Query::get_total_number_of_quizzes_by_instructor_id( get_current_user_id() );
		return $values;
	}
	public function add_author_name_to_rest_response( $item, $post, $request ) {
		$author_data = get_userdata( $item->data['author'] );
		$item->data['author_name'] = $author_data->display_name;
		return $item;
	}
	public function decode_special_characters_from_title( $item, $post, $request ) {
		$item->data['title']['rendered'] = html_entity_decode( $item->data['title']['rendered'] );
		return $item;
	}
}
