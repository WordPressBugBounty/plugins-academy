<?php
namespace AcademyMigrationTool\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyMigrationTool\Interfaces\MigrationInterface;

class Learndash extends Migration implements MigrationInterface {
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

	public function migrate_course( $ld_course ) {
		$course_id = $ld_course->ID;
		$course_meta = get_post_meta( $course_id, '_sfwd-courses', true );
		$post_content = $course_meta['sfwd-courses_course_materials'];
		// migrate course topics
		$this->migrate_course_topics( $ld_course );
		// course update
		wp_update_post(
			array(
				'ID'         => $course_id,
				'post_type'  => 'academy_courses',
				'post_content' => $ld_course->post_content . '<!-- wp:html -->' . $post_content . '<!-- /wp:html -->',
			)
		);
		$this->migrate_course_author( $ld_course->post_author, $course_id );
		// LD product insert to alms
		$this->woo_product_insert( $ld_course );
		// Alms course meta update
		$this->migrate_course_meta( $course_id );
		// course complete data migrate
		$this->migrate_course_complete( $course_id );
		// Enrollment migration
		$this->migrate_enrollments( $course_id );
		// migrate course taxonomy
		$this->migrate_course_taxonomy();
	}

	public function migrate_course_author( $author, $course_id ) {
		add_user_meta( $author, 'academy_instructor_course_id', $course_id );
	}

	public function migrate_course_topics( $course ) {
		global $wpdb;
		$course_id = $course->ID;
		$sections = learndash_30_get_course_sections( $course_id );
		if ( $sections ) {
			$new_curriculums = [];
			foreach ( $sections as $section ) {
				$items = array();
				foreach ( $section->steps as $key => $value ) {
					$items[] = $this->migrate_course_lesson( $value );
					$results = $wpdb->get_col( $wpdb->prepare(
						"SELECT post_id FROM {$wpdb->postmeta} 
							WHERE meta_key = %s AND meta_value = %d",
						'lesson_id', $value
					) );
					if ( is_array( $results ) ) {
						foreach ( $results as $post_id ) {
							// get topics and quiz
							$topic = get_post( $post_id );
							if ( 'sfwd-topic' === $topic->post_type ) {
								$items[] = $this->migrate_course_lesson( $post_id );
							} elseif ( 'sfwd-quiz' === $topic->post_type ) {
								$items[] = $this->migrate_course_quiz( $post_id );
							}
						}
					}
				}//end foreach
				$quizzes = learndash_get_course_quiz_list( $course );
				if ( $quizzes ) {
					foreach ( $quizzes as $quiz ) {
						$items[] = $this->migrate_course_quiz( $quiz['post']->ID );
					}
				}
				// set curriculums
				$new_curriculums[] = array(
					'title' => $section->post_title,
					'content' => '',
					'topics' => $items,
				);
			}//end foreach
			update_post_meta( $course_id, 'academy_course_curriculum', $new_curriculums );
		} else {
			$quizzes = learndash_get_course_quiz_list( $course );
			$course_steps = \LDLMS_Factory_Post::course_steps( $course_id );
				$course_topics = $course_steps->get_steps();
			$items = [];
			if ( $course_topics['sfwd-lessons'] ) {
				foreach ( $course_topics['sfwd-lessons'] as $key => $lessons ) {
					// lesson migrate
					$items[] = $this->migrate_course_lesson( $key );
					// topic migrate
					foreach ( $lessons['sfwd-topic'] as $key => $value ) {
						$items[] = $this->migrate_course_lesson( $key );
						// quiz migrate
						foreach ( $value['sfwd-quiz'] as $key => $quiz ) {
							$items[] = $this->migrate_course_quiz( $key );
						}
					}
					foreach ( $lessons['sfwd-quiz'] as $key => $value ) {
						$items[] = $this->migrate_course_quiz( $key );
					}
				}
			}
			if ( $quizzes ) {
				foreach ( $quizzes as $quiz ) {
					$items[] = $this->migrate_course_quiz( $quiz['post']->ID );
				}
			}
			$curriculums[] = array(
				'title' => 'Academy Topics',
				'content' => '',
				'topics' => $items,
			);
			update_post_meta( $course_id, 'academy_course_curriculum', $curriculums );
		}//end if
	}

	public function migrate_course_meta( $course_id ) {
		$course_details = get_post_meta( $course_id, '_sfwd-courses', true );
		// thumbnail id
		$thumbnail = get_post_meta( $course_id, '_thumbnail_id', true );
		set_post_thumbnail( $course_id, $thumbnail );
		update_post_meta( $course_id, 'academy_course_difficulty_level', 'beginner' );
		update_post_meta( $course_id, 'academy_course_audience', '' );
		// update max student
		update_post_meta( $course_id, 'academy_course_max_students', isset( $course_details['sfwd-courses_course_seats_limit'] ) ? $course_details['sfwd-courses_course_seats_limit'] : 0 );
		// course prerequisite
		$course_ids = $course_details['sfwd-courses_course_prerequisite'];
		$course_prerequisites = $this->academy_course_prerequisite( $course_ids );
		update_post_meta( $course_id, 'academy_prerequisite_courses', is_array( $course_prerequisites ) ? $course_prerequisites : array( '' ) );
		update_post_meta( $course_id, 'academy_prerequisite_type', 'course' );
		// course requirements
		update_post_meta( $course_id, 'academy_course_requirements', '' );
		// course duration
		update_post_meta( $course_id, 'academy_course_duration', array( 0, 0, 0 ) );
		// course announcement
		update_post_meta( $course_id, 'academy_is_enabled_course_announcements', true );
		// course enable QA
		update_post_meta( $course_id, 'academy_is_enabled_course_qa', true );
		// course material
		update_post_meta( $course_id, 'academy_course_materials_included', '' );
		// course language
		update_post_meta( $course_id, 'academy_course_language', '' );
		// course benefits
		update_post_meta( $course_id, 'academy_course_benefits', '' );
		// course expire enrollment
		update_post_meta( $course_id, 'academy_course_expire_enrollment', isset( $course_details['sfwd-courses_expire_access_days'] ) ? $course_details['sfwd-courses_expire_access_days'] : 0 );
		// course intro video
		$video_link = get_post_meta( $course_id, '_learndash_course_grid_video_embed_code', true );
		$source = $this->set_video_source( $video_link );
		$intro_video = array(
			$source[0],
			$source[1]
		);
		update_post_meta( $course_id, 'academy_course_intro_video', is_array( $intro_video ) ? $intro_video : array() );
		update_post_meta( $course_id, 'academy_course_drip_content_enabled', false );
		update_post_meta( $course_id, 'academy_course_drip_content_type', 'schedule_by_date' );
		update_post_meta( $course_id, 'academy_prerequisite_categories', array() );
	}

	public function migrate_enrollments( $course_id ) {
		global $wpdb;
		$enrollments = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * from {$wpdb->prefix}learndash_user_activity 
				WHERE course_id = %d AND activity_type = %s AND activity_status = %d",
				$course_id, 'access', 0
			)
		);
		if ( $enrollments ) {
			foreach ( $enrollments as $enrollment ) {
				$is_enrolled = \Academy\Helper::is_enrolled( $course_id, $enrollment->user_id );
				if ( ! $is_enrolled ) {
					$post_title = __( 'Course Enrolled', 'academy' );
					$enroll_data = array(
						'post_title' => $post_title,
						'post_type' => 'academy_enrolled',
						'post_status' => 'completed',
						'post_parent' => $course_id,
						'post_author' => $enrollment->user_id,
					);
					$enrolled = wp_insert_post( $enroll_data );
					if ( isset( $enrolled ) ) {
						update_user_meta( $enrollment->user_id, 'is_academy_student', $enrollment->activity_started );
					}
				}
			}
		}

	}

	public function migrate_course_lesson( $lesson_id ) {
		$lesson = get_post( $lesson_id );
		$lessons_meta = get_post_meta( $lesson_id, '_sfwd-lessons', true );
		$topics_meta = get_post_meta( $lesson_id, '_sfwd-topic', true );

		if ( $lessons_meta['sfwd-lessons_lesson_materials'] ) {
			$lesson_content = $lessons_meta['sfwd-lessons_lesson_materials'];
		} elseif ( $topics_meta['sfwd-topic_topic_materials'] ) {
			$lesson_content = $topics_meta['sfwd-topic_topic_materials'];
		} else {
			$lesson_content = '';
		}
		$array = array(
			'lesson_author' => $lesson->post_author,
			'lesson_title' => $lesson->post_title,
			'lesson_name' => $lesson->post_status,
			'lesson_status' => 'publish',
			'lesson_content' => $lesson->post_content . '<!-- wp:html -->' . $lesson_content . '<!-- /wp:html -->'
		);
		$new_lesson_id = \Academy\Classes\Query::lesson_insert( $array );
		$duration = array(
			'hours' => 0,
			'minutes' => 0,
			'seconds' => 0,
		);

		if ( $lessons_meta['sfwd-lessons_lesson_video_url'] ) {
			$set_video_source = $this->set_video_source( $lessons_meta['sfwd-lessons_lesson_video_url'] );
			$source = array(
				'type' => $set_video_source[0],
				'url' => $set_video_source[1],
			);
		} elseif ( $topics_meta['sfwd-topic_lesson_video_url'] ) {
			$set_video_source = $this->set_video_source( $topics_meta['sfwd-topic_lesson_video_url'] );
			$source = array(
				'type' => $set_video_source[0],
				'url' => $set_video_source[1],
			);
		} else {
			$source = array(
				'type' => '',
				'url'  => '',
			);
		}

		$lesson_meta = [
			'featured_media' => get_post_meta( $lesson_id, '_thumbnail_id', true ),
			'attachment'     => '',
			'is_previewable' => 0,
			'video_duration' => wp_json_encode( $duration ),
			'video_source'   => wp_json_encode( $source ),
		];
		\Academy\Classes\Query::lesson_meta_insert( $new_lesson_id, $lesson_meta );
		return array(
			'id' => $new_lesson_id,
			'name' => $lesson->post_title,
			'type' => 'lesson',
		);
	}

	public function set_video_source( $url ) {
		$pattern = '/<iframe[^>]*>.*?<\/iframe>/i';
		preg_match_all( $pattern, $url, $match );
		if ( $match[0] ) {
			return array(
				'embed',
				$url
			);
		} elseif ( strpos( $url, 'youtube.com' ) !== false || strpos( $url, 'youtu.be' ) !== false ) {
			return array(
				'youtube',
				$url
			);
		} elseif ( strpos( $url, 'vimeo.com' ) !== false || strpos( $url, 'player.vimeo.com' ) !== false ) {
			return array(
				'vimeo',
				$url
			);
		} else {
			return array(
				'external',
				$url
			);
		}//end if
	}

	public function migrate_course_quiz( $quiz_id ) {
		global $wpdb;
		$quiz = get_post( $quiz_id );
		wp_update_post(array(
			'ID' => $quiz_id,
			'post_type' => 'academy_quiz',
		));
		$quiz_meta = get_post_meta( $quiz_id, '_sfwd-quiz', true );
		$quiz_meta = array(
			'_wp_page_template' => '',
			'academy_quiz_drip_content' => array( '' ),
			'academy_quiz_time' => (int) isset( $quiz_meta['sfwd-quiz_timeLimit'] ) ? $quiz_meta['sfwd-quiz_timeLimit'] : 0,
			'academy_quiz_time_unit' => 'seconds',
			'academy_quiz_hide_quiz_time' => isset( $quiz_meta['sfwd-quiz_hideResultQuizTime'] ) ? $quiz_meta['sfwd-quiz_hideResultQuizTime'] : false,
			'academy_quiz_feedback_mode' => 'default',
			'academy_quiz_passing_grade' => (int) isset( $quiz_meta['sfwd-quiz_passingpercentage'] ) ? $quiz_meta['sfwd-quiz_passingpercentage'] : 0,
			'academy_quiz_max_questions_for_answer' => 0,
			'academy_quiz_max_attempts_allowed' => 0,
			'academy_quiz_auto_start' => false,
			'academy_quiz_questions_order' => 'sorting',
			'academy_quiz_hide_question_number' => isset( $quiz_meta['sfwd-quiz_hideQuestionNumbering'] ) ? $quiz_meta['sfwd-quiz_hideQuestionNumbering'] : false,
			'academy_quiz_short_answer_characters_limit' => (int) 200,
			'academy_quiz_questions' => [],
		);
		foreach ( $quiz_meta as $key => $value ) {
			add_post_meta( $quiz_id, $key, $value, true );
		}
		$question_ids = get_post_meta( $quiz_id, 'ld_quiz_questions', true );
		if ( is_array( $question_ids ) ) {
			$question_ids = array_keys( $question_ids );
			foreach ( $question_ids as $id ) {
				$question_id = get_post_meta( $id, 'question_pro_id', true );
				$questions = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT id, title, points, question, answer_type, answer_data FROM {$wpdb->prefix}learndash_pro_quiz_question
						WHERE id = %d",
						$question_id
					)
				);
				if ( ! $questions ) {
					$questions = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT id, title, points, question, answer_type, answer_data FROM {$wpdb->prefix}wp_pro_quiz_question 
							WHERE id = %d",
							$question_id
						)
					);
				}
				foreach ( $questions as $question ) {
					$question_type = $this->question_type( $question->answer_type );
					if ( ! $question_type ) {
						continue;
					}
					$question_data = array(
						'quiz_id' => $quiz_id,
						'question_title' => $question->title,
						'question_content' => (string) $question->question,
						'question_status' => 'publish',
						'question_type' => $question_type,
						'question_score' => (int) $question->points,
						'question_order' => 0,
						'question_settings' => wp_json_encode(
							array(
								'display_points' => false,
								'answer_required' => false,
								'randomize' => false,
							)
						),
					);
					$alms_question_id = \AcademyQuizzes\Classes\Query::quiz_question_insert( $question_data );
					$old_quiz_questions = get_post_meta( $quiz_id, 'academy_quiz_questions', true );
					if ( is_array( $old_quiz_questions ) ) {
						$quiz_question = array(
							'id' => $alms_question_id,
							'title' => $question->title,
						);
						$academy_quiz_question[] = $quiz_question;
					} else {
						$academy_quiz_question = array(
							'id' => $alms_question_id,
							'title' => $question->title,
						);
					}
					update_post_meta( $quiz_id, 'academy_quiz_questions', $academy_quiz_question );

					// quiz answer migrate
					foreach ( (array) maybe_unserialize( $question->answer_data ) as $key => $value ) {
						$answerValue = $value->getAnswer();
						$answer_title = array();
						$answer_content = array();
						if ( 'fillInTheBlanks' === $question_type ) {
							$answerValue = wp_strip_all_tags( $answerValue );
							preg_match_all( '/{.*?\}/', $answerValue, $matches );
							if ( ! empty( $matches[0] ) ) {
								foreach ( $matches[0] as $key => $found_match ) {
									$found_match = explode( ']', $found_match );
									if ( isset( $found_match[0] ) ) {
										$answer_str[] = str_replace( array( '{[', '{', '}' ), '', $found_match[0] );
										$merged_answer = implode( '|', $answer_str );
										$answer_content = preg_replace( '/\|\d+/', '', $merged_answer );
									}
								}
								$answer_title = str_replace( $matches[0], '{dash}', $answerValue );
							}
						} else {
							$answer_title = $value->getAnswer();
						}
						$answer_data = array(
							'quiz_id' => (int) $quiz_id,
							'question_id' => (int) $alms_question_id,
							'question_type' => $question_type,
							'answer_title' => $answer_title,
							'answer_content' => $answer_content ? $answer_content : '',
							'is_correct' => (int) $value->isCorrect() ? $value->isCorrect() : 0,
							'answer_order' => 0,
							'view_format' => 'text',
						);
						\AcademyQuizzes\Classes\Query::quiz_answer_insert( $answer_data );
					}//end foreach
				}//end foreach
			}//end foreach
		}//end if
		return array(
			'id' => $quiz_id,
			'name' => $quiz->post_title,
			'type' => 'quiz',
		);
	}

	public function woo_product_insert( $course ) {
		$id = $course->ID;
		$ld_price = get_post_meta( $id, '_sfwd-courses', true );
		if ( $ld_price['sfwd-courses_course_price'] ) {
			$course_type = 'paid';
			$this->woo_create_or_update_product( array(
				'course_id' => $id,
				'course_title' => $course->post_title,
				'course_slug' => $course->post_name,
				'regular_price' => $ld_price['sfwd-courses_course_price'],
				'sale_price' => '',
			) );
		} else {
			$course_type = 'free';
			update_post_meta( $id, 'academy_course_product_id', 0 );
		}
		// update course meta
		update_post_meta( $id, 'academy_course_type', $course_type );
	}

	public function question_type( $type ) {
		switch ( $type ) {
			case 'single':
				return 'singleChoice';

			case 'multiple':
				return 'multipleChoice';

			case 'cloze_answer':
				return 'fillInTheBlanks';

			default:
				return '';
		}
		return '';
	}

	public function migrate_course_complete( $course_id ) {
		global $wpdb;
		$complete_courses = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * from {$wpdb->prefix}learndash_user_activity 
				WHERE activity_type = %s AND activity_status = %d AND course_id = %d",
				'course', 1, $course_id
			)
		);
		if ( $complete_courses ) {
			$this->migrate_course_complete_data( $complete_courses, $course_id );
		}
	}

	public function migrate_course_reviews( $course_id ) {
		// UpNext work
	}

	public function migrate_course_taxonomy() {
		// course category
		$this->migrate_taxonomy_category( 'ld_course_category', 'academy_courses_category' );
		// course tag
		$this->migrate_taxonomy_tag( 'ld_course_tag', 'academy_courses_tag' );
		// another course category
		$this->migrate_taxonomy_category( 'category', 'academy_courses_category' );
		// another course tax
		$this->migrate_taxonomy_tag( 'post_tag', 'academy_courses_tag' );
	}
}
