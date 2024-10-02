<?php
namespace AcademyMigrationTool\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

use AcademyMigrationTool\Interfaces\MigrationInterface;

class Lifterlms extends Migration implements MigrationInterface {

	public $course;
	public $logs = [];
	public function __construct( $course_id ) {
		$this->course = get_post( $course_id );
	}

	public function run_migration() {
		if ( $this->course ) {
			// Migrate courses
			$this->migrate_course( $this->course );
			// Migrate Reviews
			$this->migrate_course_reviews( $this->course->ID ); // next work
		}
	}

	public function get_logs() {
		return $this->logs;
	}

	public function migrate_course( $course ) {
		$course_id = $course->ID;
		$remove_content = '[lifterlms_course_continue_button]';
		$content = str_replace( $remove_content, '', $course->post_content );
		wp_update_post([
			'ID' => $course_id,
			'post_type' => 'academy_courses',
			'post_content' => $content,
		]);
		// migrate course instructor
		$this->migrate_course_author( $course->post_author, $course_id );
		// migrate course meta
		$this->migrate_course_meta( $course_id );
		// migrate course curriculum
		$this->migrate_section( $course_id );
		// migrate course complete
		$this->migrate_course_complete( $course_id );
		// migrate course enroll
		$this->migrate_enrollments( $course_id );
		// insert product woo commerce
		$this->woo_product_insert( $course );
		// Migrate course taxonomy
		$this->migrate_course_taxonomy();
	}

	public function migrate_course_author( $author, $course_id ) {
		add_user_meta( $author, 'academy_instructor_course_id', $course_id );
	}

	public function migrate_course_meta( $course_id ) {
		global $wpdb;
		// thumbnail id
		$thumbnail = get_post_meta( $course_id, '_thumbnail_id', true );
		set_post_thumbnail( $course_id, $thumbnail );
		// target audience
		update_post_meta( $course_id, 'academy_course_audience', '' );
		// update level
		update_post_meta(
			$course_id,
			'academy_course_difficulty_level',
			'beginner'
		); // default value
		// update max student
		$max_student = (int) abs(
			get_post_meta( $course_id, '_llms_capacity', true )
		);
		update_post_meta(
			$course_id,
			'academy_course_max_students',
			isset( $max_student ) ? $max_student : 0
		);
		// course requirements
		update_post_meta( $course_id, 'academy_course_requirements', '' );
		// course duration
		add_post_meta( $course_id, 'academy_course_expire_enrollment', 0 );
		// course announcement
		add_post_meta(
			$course_id,
			'academy_is_enabled_course_announcements',
			true
		);
		// course enable QA
		add_post_meta( $course_id, 'academy_is_enabled_course_qa', true );
		// course metarial
		add_post_meta( $course_id, 'academy_course_materials_included', '' );
		// course language
		add_post_meta( $course_id, 'academy_course_language', '' );
		// course benefits
		add_post_meta( $course_id, 'academy_course_benefits', '' );
		// course expire enrollment
		add_post_meta( $course_id, 'academy_course_duration', [ 0, 0, 0 ] );
		// course intro video
		$url = get_post_meta( $course_id, '_llms_video_embed', true );
		$source = $this->set_video_source( $url );
		add_post_meta(
			$course_id,
			'academy_course_intro_video',
			is_array( $source ) ? $source : []
		);
		add_post_meta( $course_id, 'academy_course_drip_content_enabled', false );
		add_post_meta(
			$course_id,
			'academy_course_drip_content_type',
			'schedule_by_date'
		);
		add_post_meta( $course_id, 'academy_prerequisite_type', 'course' );
		// course prerequisite
		$prerequisite_id = get_post_meta(
			$course_id,
			'_llms_prerequisite',
			true
		);
		$course_prerequisites = [];
		if ( $prerequisite_id ) {
			$posts = $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE ID = %d", $prerequisite_id )
			);
			foreach ( $posts as $post ) {
				$course_prerequisites[] = [
					'label' => $post->post_title,
					'value' => $prerequisite_id,
				];
			}
		}
		update_post_meta(
			$course_id,
			'academy_prerequisite_courses',
			is_array( $course_prerequisites ) ? $course_prerequisites : []
		);
		add_post_meta( $course_id, 'academy_prerequisite_categories', [] );
	}

	public function set_video_source( $url ) {
		$pattern = '/<iframe[^>]*>.*?<\/iframe>/i';
		preg_match_all( $pattern, $url, $match );
		if ( $match[0] ) {
			return [ 'embed', $url ];
		} elseif (
			strpos( $url, 'youtube.com' ) !== false ||
			strpos( $url, 'youtu.be' ) !== false
		) {
			return [ 'youtube', $url ];
		} elseif (
			strpos( $url, 'vimeo.com' ) !== false ||
			strpos( $url, 'player.vimeo.com' ) !== false
		) {
			return [ 'vimeo', $url ];
		} else {
			return [ 'external', $url ];
		}
	}

	public function migrate_section( $course_id ) {
		$course = new \LLMS_Course( $course_id );
		$sections = $course->get_sections();
		$curriculums = [];
		foreach ( $sections as $section ) {
			$title = get_the_title( $section->get( 'id' ) );
			$lessons = $section->get_lessons();
			$items = [];
			foreach ( $lessons as $lesson ) {
				$id = $lesson->get( 'id' );
				$lesson = get_post( $id );
				$items[] = $this->migrate_course_lesson( $lesson );
				$quiz_id = get_post_meta( $id, '_llms_quiz', true );
				if ( $quiz_id ) {
					$items[] = $this->migrate_course_quiz( $quiz_id );
				}
			}
			$curriculums[] = [
				'title' => isset( $title ) ? $title : 'Academy Topics',
				'content' => '',
				'topics' => $items,
			];
		}
		update_post_meta( $course_id, 'academy_course_curriculum', $curriculums );
	}

	public function migrate_course_lesson( $lesson ) {
		$array = [
			'lesson_author' => $lesson->post_author,
			'lesson_title' => $lesson->post_title,
			'lesson_name' => $lesson->post_status,
			'lesson_status' => 'publish',
			'lesson_content' =>
				'<!-- wp:html -->' .
				$lesson->post_content .
				'<!-- /wp:html -->',
		];
		$lesson_id = \Academy\Classes\Query::lesson_insert( $array );
		$video_url = get_post_meta( $lesson->ID, '_llms_video_embed', true );
		$source = $this->set_video_source( $video_url );
		$video_source = wp_json_encode([
			'type' => is_array( $source ) ? $source[0] : '',
			'url' => is_array( $source ) ? $source[1] : '',
		]);
		$lesson_meta = [
			'featured_media' => '',
			'attachment' => '',
			'is_previewable' => 0,
			'video_duration' => wp_json_encode([
				'hours' => 0,
				'minutes' => 0,
				'seconds' => 0,
			]),
			'video_source' => $video_source,
		];
		\Academy\Classes\Query::lesson_meta_insert( $lesson_id, $lesson_meta );

		return [
			'id' => $lesson_id,
			'name' => $lesson->post_title,
			'type' => 'lesson',
		];
	}

	public function migrate_course_quiz( $quiz_id ) {
		global $wpdb;
		$quiz = get_post( $quiz_id );
		// quiz migrate
		wp_update_post([
			'ID' => $quiz_id,
			'post_type' => 'academy_quiz',
			'post_content' => $quiz->post_content,
		]);
		// quiz meta update
		$attempts_allowed = get_post_meta(
			$quiz_id,
			'_llms_allowed_attempts',
			true
		);
		$random = get_post_meta( $quiz_id, '_llms_random_questions', true );
		$attempts_limit = get_post_meta( $quiz_id, '_llms_limit_attempts', true );
		$quiz_meta = [
			'_wp_page_template' => '',
			'academy_quiz_drip_content' => [],
			'academy_quiz_time' => get_post_meta(
				$quiz_id,
				'_llms_time_limit',
				true
			),
			'academy_quiz_time_unit' => 'minutes',
			'academy_quiz_hide_quiz_time' => false,
			'academy_quiz_feedback_mode' => 'yes' === $attempts_limit ? 'retry' : 'default',
			'academy_quiz_passing_grade' => get_post_meta(
				$quiz_id,
				'_llms_passing_percent',
				true
			),
			'academy_quiz_max_questions_for_answer' => 0,
			'academy_quiz_max_attempts_allowed' => 'yes' === $attempts_limit ? $attempts_allowed : 0,
			'academy_quiz_auto_start' => '',
			'academy_quiz_questions_order' => 'yes' === $random ? 'random' : 'default',
			'academy_quiz_hide_question_number' => '',
			'academy_quiz_short_answer_characters_limit' => 200,
			'academy_quiz_questions' => [],
		];
		foreach ( $quiz_meta as $key => $value ) {
			add_post_meta( $quiz_id, $key, $value, true );
		}

		// Execute the query
		$question_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %d",
			'_llms_parent_id',
			$quiz_id
		) );
		if ( $question_ids ) {
			foreach ( $question_ids as $question_id ) {
				$question = get_post( $question_id );
				$llms_question_type = get_post_meta(
					$question_id,
					'_llms_question_type',
					true
				);
				$score = get_post_meta( $question_id, '_llms_points', true );
				$question_order = $question->menu_order;
				if ( 'true_false' === $llms_question_type ) {
					$question_type = 'trueFalse';
				} elseif ( 'choice' === $llms_question_type ) {
					$question_type = 'multipleChoice';
				} elseif ( 'picture_choice' === $llms_question_type ) {
					$question_type = 'imageAnswer';
				}
				if ( ! $question_type ) {
					continue;
				}
				$array = [
					'quiz_id' => $quiz_id,
					'question_title' => $question->post_title,
					'question_content' => $question->post_content,
					'question_status' => 'publish',
					'question_type' => $question_type,
					'question_score' => isset( $score ) ? $score : 0,
					'question_order' => $question_order,
					'question_settings' => wp_json_encode([
						'display_points' => false,
						'answer_required' => false,
						'randomize' => false,
					]),
				];
				$alms_question_id = \AcademyQuizzes\Classes\Query::quiz_question_insert(
					$array
				);
				// quiz questions meta update
				$old_quiz_questions = get_post_meta(
					$quiz_id,
					'academy_quiz_questions',
					true
				);
				if ( is_array( $old_quiz_questions ) ) {
					$quiz_question = [
						'id' => $alms_question_id,
						'title' => $question->post_title,
					];
					$academy_quiz_question[] = $quiz_question;
				} else {
					$academy_quiz_question = [
						'id' => $alms_question_id,
						'title' => $question->post_title,
					];
				}
				update_post_meta(
					$quiz_id,
					'academy_quiz_questions',
					$academy_quiz_question,
					$old_quiz_questions
				);

				// quiz answer migrate
				$question = new \LLMS_Question( $question_id );
				foreach ( $question->get_choices() as $answer ) {
					$reflectionClass = new \ReflectionClass( $answer );
					$dataProperty = $reflectionClass->getProperty( 'data' );
					$dataProperty->setAccessible( true );
					$data = $dataProperty->getValue( $answer );
					\AcademyQuizzes\Classes\Query::quiz_answer_insert([
						'quiz_id' => $quiz_id,
						'question_id' => $alms_question_id,
						'question_type' => $question_type,
						'answer_title' => ! is_array( $data['choice'] )
							? $data['choice']
							: '',
						'is_correct' => $data['correct'] ? true : false,
						'image_id' => is_array( $data['choice'] )
							? $data['choice']['id']
							: 0,
						'answer_order' => 0,
						'view_format' => 'text',
					]);
				}
			}//end foreach
		}//end if
		return [
			'id' => $quiz_id,
			'name' => $quiz->post_title,
			'type' => 'quiz',
		];
	}

	public function migrate_enrollments( $course_id ) {
		global $wpdb;
		$enrollments = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id, post.post_date as order_post_date
		FROM {$wpdb->prefix}lifterlms_user_postmeta
		LEFT JOIN {$wpdb->posts} post ON post_id = post.ID
		WHERE post_id = %d AND meta_value = %s ",
				$course_id,
				'enrolled'
			)
		);
		if ( is_array( $enrollments ) ) {
			$this->enrollment_migration( $course_id, $enrollments );
		}
	}

	public function migrate_course_complete( $course_id ) {
		global $wpdb;
		$course_complete = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id
		FROM {$wpdb->prefix}lifterlms_user_postmeta 
		LEFT JOIN {$wpdb->posts} post ON post_id = post.ID
		WHERE post_id = %d AND meta_key = %s AND meta_value = %s ",
				$course_id,
				'_is_complete',
				'yes'
			)
		);
		if ( $course_complete ) {
			$this->migrate_course_complete_data( $course_complete, $course_id );
		}
	}

	public function migrate_course_reviews( $course_id ) {
		// up next work
	}

	public function woo_product_insert( $course ) {
		global $wpdb;
		$course_id = $course->ID;

		// Execute the query
		$product_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %d",
			'_llms_product_id',
			$course_id
		) );
		if ( $product_ids ) {
			foreach ( $product_ids as $product_id ) {
				$regular_price = get_post_meta(
					$product_id,
					'_llms_price',
					true
				);
				$sale_price = get_post_meta(
					$product_id,
					'_llms_sale_price',
					true
				);
				if ( $regular_price ) {
					$course_type = 'paid';
					$args = [
						'course_id' => $course_id,
						'course_title' => $course->post_title,
						'course_slug' => $course->post_name,
						'regular_price' => $regular_price,
						'sale_price' => $sale_price,
					];
					$this->woo_create_or_update_product( $args );
				} else {
					$course_type = 'free';
					update_post_meta(
						$course_id,
						'academy_course_product_id',
						0
					);
				}
				// update course meta
				update_post_meta(
					$course_id,
					'academy_course_type',
					$course_type
				);
			}//end foreach
		} else {
			update_post_meta( $course_id, 'academy_course_product_id', 0 );
			update_post_meta( $course_id, 'academy_course_type', 'free' );
		}//end if
	}

	public function migrate_course_taxonomy() {
		// course category
		$this->migrate_taxonomy_category(
			'course_cat',
			'academy_courses_category'
		);
		// course tag
		$this->migrate_taxonomy_tag( 'course_tag', 'academy_courses_tag' );
	}
}
