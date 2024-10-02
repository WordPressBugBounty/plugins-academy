<?php
namespace  AcademyQuizzes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Miscellaneous {
	public static function init() {
		$self = new self();
		add_action( 'rest_delete_academy_quiz', array( $self, 'delete_quiz_data' ) );
		add_filter( 'academy/get_analytics', array( $self, 'add_total_quizzes' ) );
	}

	public function delete_quiz_data( $post ) {
		global $wpdb;
		$quiz_id = $post->ID;
		$wpdb->delete( $wpdb->prefix . 'academy_quiz_questions', array( 'quiz_id' => $quiz_id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'academy_quiz_answers', array( 'quiz_id' => $quiz_id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'academy_quiz_attempts', array( 'quiz_id' => $quiz_id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'academy_quiz_attempt_answers', array( 'quiz_id' => $quiz_id ), array( '%d' ) );
	}

	public function add_total_quizzes( $analytics ) {
		$analytics['total_quizzes'] = Classes\Query::get_total_number_of_quizzes();
		return $analytics;
	}
}
