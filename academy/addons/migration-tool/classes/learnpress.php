<?php
namespace AcademyMigrationTool\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyMigrationTool\Interfaces\MigrationInterface;

class LearnPress extends Migration implements MigrationInterface {
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
			$this->migrate_course_reviews( $this->course->ID );
		}
	}

	public function get_logs() {
		return $this->logs;
	}

	public function migrate_course( $course ) {
		$course_id = $course->ID;
		// course update
		wp_update_post(array(
			'ID'         => $course_id,
			'post_type'  => 'academy_courses',
			'post_content' => '<!-- wp:html -->' . $course->post_content . '<!-- /wp:html -->'
		));
		$this->migrate_course_author( $course->post_author, $course_id );
		// LP product insert in ALMS
		$this->woo_product_insert( $course );
		// ALMS course meta update
		$this->migrate_course_meta( $course );
		// Course complete status Migrate to ALMS
		$this->migrate_course_complete( $course_id );
		// Enrollment Migration
		$this->migrate_enrollments( $course_id );
		// Migrate course taxonomy
		$this->migrate_course_taxonomy();
	}

	public function migrate_course_author( $user_id, $course_id ) {
		add_user_meta( $user_id, 'academy_instructor_course_id', $course_id );
	}

	public function migrate_course_quiz( $quiz ) {
		global $wpdb;
		$quizzes = get_post( $quiz->id );
		// quiz migrate
		wp_update_post(array(
			'ID' => $quiz->id,
			'post_type' => 'academy_quiz',
		));
		// quiz meta update
		$duration = get_post_meta( $quiz->id, '_lp_duration', true );
		preg_match( '/(\d+)\s*(\w+)/', $duration, $matches );
		$time = intval( $matches[1] );
		$time_unit = strtolower( $matches[2] );
		$attempts_allowed = get_post_meta( $quiz->id, '_lp_retake_count', true );
		$quiz_meta = array(
			'_wp_page_template' => '',
			'academy_quiz_drip_content' => array( '' ),
			'academy_quiz_time' => (int) $time,
			'academy_quiz_time_unit' => $time_unit . 's',
			'academy_quiz_hide_quiz_time' => false,
			'academy_quiz_feedback_mode' => ( $attempts_allowed > 0 ) ? 'retry' : 'default',
			'academy_quiz_passing_grade' => (int) get_post_meta( $quiz->id, '_lp_passing_grade', true ),
			'academy_quiz_max_questions_for_answer' => 0,
			'academy_quiz_max_attempts_allowed' => (int) $attempts_allowed > 0 ? $attempts_allowed : 0,
			'academy_quiz_auto_start' => false,
			'academy_quiz_questions_order' => 'default',
			'academy_quiz_hide_question_number' => false,
			'academy_quiz_short_answer_characters_limit' => (int) 200,
			'academy_quiz_questions' => [],
		);
		foreach ( $quiz_meta as $key => $value ) {
			add_post_meta( $quiz->id, $key, $value, true );
		}
		// quiz question migrate
		$questions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT question_id,question_order FROM {$wpdb->prefix}learnpress_quiz_questions 
				WHERE quiz_id = %d",
				$quiz->id
			)
		);
		if ( $questions ) {
			foreach ( $questions as $question ) {
				if ( 'fill_in_blanks' === $question->question_type ) {
					continue;
				}
				$question_id = $question->question_id;
				$question_type = get_post_meta( $question_id, '_lp_type', true );
				$score = get_post_meta( $question_id, '_lp_mark', true );
				$question_order = $question->question_order;
				if ( 'true_or_false' === $question_type ) {
					$question_type = 'trueFalse';
				}
				if ( 'single_choice' === $question_type ) {
					$question_type = 'singleChoice';
				}
				if ( 'multi_choice' === $question_type ) {
					$question_type = 'multipleChoice';
				}
				$question = get_post( $question_id );
				$alms_question_id = \AcademyQuizzes\Classes\Query::quiz_question_insert( array(
					'quiz_id' => (int) $quiz->id,
					'question_title' => $question->post_title,
					'question_content' => $question->post_content,
					'question_status' => 'publish',
					'question_type' => $question_type,
					'question_score' => $score,
					'question_order' => $question_order,
					'question_settings' => wp_json_encode(array(
						'display_points' => false,
						'answer_required' => false,
						'randomize' => false,
					)),
				) );
				// quiz questions meta update
				$old_quiz_questions = get_post_meta( $quiz->id, 'academy_quiz_questions', true );
				if ( is_array( $old_quiz_questions ) ) {
					$quiz_question = array(
						'id' => $alms_question_id,
						'title' => $question->post_title,
					);
					$academy_quiz_question[] = $quiz_question;
				} else {
					$academy_quiz_question = array(
						'id' => $alms_question_id,
						'title' => $question->post_title,
					);
				}
				update_post_meta( $quiz->id, 'academy_quiz_questions', $academy_quiz_question, $old_quiz_questions );

				// quiz answer migrate
				$answers = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * from {$wpdb->prefix}learnpress_question_answers 
						WHERE question_id = %d",
						$question_id
					)
				);
				foreach ( $answers as $answer ) {
					$is_correct = 'yes' === $answer->is_true ? 1 : 0;
					\AcademyQuizzes\Classes\Query::quiz_answer_insert( array(
						'quiz_id' => (int) $quiz->id,
						'question_id' => (int) $alms_question_id,
						'question_type' => $question_type,
						'answer_title' => $answer->title,
						'is_correct' => $is_correct,
						'answer_order' => $answer->order,
						'view_format' => 'text',
					) );
				}
			}//end foreach
		}//end if
		return array(
			'id' => $quiz->id,
			'name' => $quizzes->post_title,
			'type' => 'quiz',
		);
	}

	public function woo_product_insert( $lp_course ) {
		$regular_price = get_post_meta( $lp_course->ID, '_lp_regular_price', true );
		$sale_price = get_post_meta( $lp_course->ID, '_lp_sale_price', true );
		if ( $regular_price ) {
			$course_type = 'paid';
			$this->woo_create_or_update_product( array(
				'course_id' => $lp_course->ID,
				'course_title' => $lp_course->post_title,
				'course_slug' => $lp_course->post_name,
				'regular_price' => $regular_price,
				'sale_price' => $sale_price,
			) );
		} else {
			$course_type = 'free';
			update_post_meta( $lp_course->ID, 'academy_course_product_id', 0 );
		}
		// update course meta
		update_post_meta( $lp_course->ID, 'academy_course_type', $course_type );
	}

	public function migrate_course_meta( $course ) {
		$course_id = $course->ID;
		// curriculum update
		$curriculums = get_post_meta( $course_id, '_lp_info_extra_fast_query', true );
		$curriculum = json_decode( $curriculums );
		$curriculums = $curriculum->sections_items;
		$new_curriculums = [];
		foreach ( $curriculums as $curriculum ) {
			$topics = array();
			foreach ( $curriculum->items as $topic_item ) {
				if ( 'lp_lesson' === $topic_item->type ) {
					$topics[] = $this->migrate_course_lesson( $topic_item );
				} elseif ( 'lp_quiz' === $topic_item->type ) {
					$topics[] = $this->migrate_course_quiz( $topic_item );
				}
			}
			$new_curriculums[] = array(
				'title' => $curriculum->title,
				'content' => $curriculum->description,
				'topics' => $topics,
			);
		}
		update_post_meta( $course_id, 'academy_course_curriculum', $new_curriculums );

		// thumbnail id
		$thumbnail = get_post_meta( $course_id, '_thumbnail_id', true );
		set_post_thumbnail( $course_id, $thumbnail );

		// target audience
		$target_audiences = get_post_meta( $course_id, '_lp_target_audiences', true );
		if ( $target_audiences ) {
			foreach ( $target_audiences as $target_audience ) {
				update_post_meta( $course_id, 'academy_course_audience', $target_audience );
			}
		} else {
			update_post_meta( $course_id, 'academy_course_audience', '' );
		}
		// update level
		$lp_level = get_post_meta( $course_id, '_lp_level', true );
		if ( 'expert' === $lp_level ) {
			$lp_level = 'experts';
		}
		update_post_meta( $course_id, 'academy_course_difficulty_level', $lp_level );

		// update max student
		$max_student = (int) get_post_meta( $course_id, '_lp_max_students', true );
		update_post_meta( $course_id, 'academy_course_max_students', $max_student );

		// course requirements
		$requirements = get_post_meta( $course_id, '_lp_requirements', true );
		if ( $requirements ) {
			foreach ( $requirements as $requirement ) {
				update_post_meta( $course_id, 'academy_course_requirements', $requirement );
			}
		} else {
			update_post_meta( $course_id, 'academy_course_requirements', '' );
		}
		// course duration
		$duration = get_post_meta( $course_id, '_lp_duration', true );
		$weeksToDays = 7;
		// Parse duration and unit
		preg_match( '/(\d+)\s*(\w+)/', $duration, $matches );
		$value = intval( $matches[1] );
		$unit = strtolower( $matches[2] );
		if ( 'week' === $unit ) {
			$inDay = $value * $weeksToDays;
		} elseif ( 'day' === $unit ) {
			$inDay = $value;
		} elseif ( 'hour' === $unit ) {
			$inDay = $value > 23 ? $value : 0;
		}
		add_post_meta( $course_id, 'academy_course_expire_enrollment', (int) $inDay );
		// course announcement
		add_post_meta( $course_id, 'academy_is_enabled_course_announcements', true );
		// course enable QA
		add_post_meta( $course_id, 'academy_is_enabled_course_qa', true );
		// course material
		add_post_meta( $course_id, 'academy_course_materials_included', '' );
		// course language
		add_post_meta( $course_id, 'academy_course_language', '' );
		// course benefits
		add_post_meta( $course_id, 'academy_course_benefits', '' );
		// course expire enrollment
		add_post_meta( $course_id, 'academy_course_duration', array( 0, 0, 0 ) );
		// course intro video
		add_post_meta( $course_id, 'academy_course_intro_video', '' );
		add_post_meta( $course_id, 'academy_course_drip_content_enabled', false );
		add_post_meta( $course_id, 'academy_course_drip_content_type', 'schedule_by_date' );
		add_post_meta( $course_id, 'academy_prerequisite_type', 'course' );
		// course prerequisite
		$prerequisite_ids = get_post_meta( $course_id, '_lp_course_prerequisite', true );
		$course_prerequisites = $this->academy_course_prerequisite( $prerequisite_ids );
		update_post_meta( $course_id, 'academy_prerequisite_courses', is_array( $course_prerequisites ) ? $course_prerequisites : array() );
		add_post_meta( $course_id, 'academy_prerequisite_categories', array() );
	}

	public function migrate_course_lesson( $lesson ) {
		$lp_lesson = get_post( $lesson->id );
		$array = array(
			'lesson_author' => $lp_lesson->post_author,
			'lesson_title' => $lp_lesson->post_title,
			'lesson_name' => $lp_lesson->post_status,
			'lesson_status' => 'publish',
			'lesson_content' => '<!-- wp:html -->' . $lp_lesson->post_content . '<!-- /wp:html -->'
		);
		$lesson_id = \Academy\Classes\Query::lesson_insert( $array );
		$is_preview = get_post_meta( $lp_lesson->ID, '_lp_preview', true ) === 'yes' ? 1 : 0;
		$duration = get_post_meta( $lp_lesson->ID, '_lp_duration', true );
		$duration = $this->duration_convert_to_array( $duration );
		$duration = wp_json_encode(array(
			'hours' => $duration[0],
			'minutes' => $duration[1],
			'seconds' => 0,
		));
		$lesson_meta = [
			'featured_media' => '',
			'attachment' => '',
			'is_previewable' => $is_preview,
			'video_duration' => $duration,
			'video_source' => '',
		];
		\Academy\Classes\Query::lesson_meta_insert( $lesson_id, $lesson_meta );

		return array(
			'id' => $lesson_id,
			'name' => $lp_lesson->post_title,
			'type' => 'lesson',
		);
	}

	public function duration_convert_to_array( $duration ) {
		// Conversion factors
		$weeksToDays = 7;
		$daysToHours = 24;
		// Parse duration and unit
		preg_match( '/(\d+)\s*(\w+)/', $duration, $matches );
		$value = intval( $matches[1] );
		$unit = strtolower( $matches[2] );

		if ( 'week' === $unit ) {
			$inHours = $value * $weeksToDays * $daysToHours;
		} elseif ( 'day' === $unit ) {
			$inHours = $value * $daysToHours;
		} elseif ( 'hour' === $unit ) {
			$inHours = $value;
		} elseif ( 'minute' === $unit ) {
			$inMinutes = $value;
		}
		$array = array(
			isset( $inHours ) ? $inHours : 0,
			isset( $inMinutes ) ? $inMinutes : 0,
			'0'
		);
		return $array;
	}

	public function migrate_course_reviews( $course_id ) {
		global $wpdb;
		$review_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT comments.comment_ID 
				FROM {$wpdb->comments} comments
				INNER JOIN {$wpdb->commentmeta} meta ON comments.comment_ID = meta.comment_id AND meta.meta_key = %s
				WHERE comments.comment_type = %s AND comment_post_ID = %d",
				'_lpr_rating',
				'review',
				$course_id
			)
		);
		if ( $review_ids ) {
			foreach ( $review_ids as $review_id ) {
				$wpdb->update( $wpdb->comments,
					array(
						'comment_approved' => 'approved',
						'comment_type' => 'academy_courses',
						'comment_agent' => 'academy',
						'comment_approved' => 1,
					),
					array(
						'comment_ID' => $review_id
					)
				);
				$wpdb->update( $wpdb->commentmeta,
					array(
						'meta_key' => 'academy_rating',
						'comment_id' => $review_id
					),
					array(
						'meta_key' => '_lpr_rating',
						'comment_id' => $review_id
					)
				);
			}//end foreach
		}//end if
	}

	public function migrate_course_complete( $course_id ) {
		global $wpdb;
		$complete_courses = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id, lp_order.post_date as order_date
				FROM {$wpdb->prefix}learnpress_user_items 
				LEFT JOIN {$wpdb->posts} lp_order ON ref_id = lp_order.ID
				WHERE item_id = %d AND item_type = %s AND graduation = %s",
				$course_id, 'lp_course', 'passed'
			)
		);
		if ( $complete_courses ) {
			$this->migrate_course_complete_data( $complete_courses, $course_id );
		}
	}

	public function migrate_enrollments( $course_id ) {
		global $wpdb;
		$enrollments = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id, lp_order.post_date as order_post_date
				FROM {$wpdb->prefix}learnpress_user_items
				LEFT JOIN {$wpdb->posts} lp_order ON ref_id = lp_order.ID
				WHERE item_id = %d AND ref_type = %s",
				$course_id, 'lp_order'
			)
		);
		if ( $enrollments ) {
			$this->enrollment_migration( $course_id, $enrollments );
		}
	}

	public function migrate_course_taxonomy() {
		// course category
		$this->migrate_taxonomy_category( 'course_category', 'academy_courses_category' );
		// course tag
		$this->migrate_taxonomy_tag( 'course_tag', 'academy_courses_tag' );
	}
}
