<?php
namespace AcademyMigrationTool\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyMigrationTool\Interfaces\MigrationInterface;
use \MasterStudy\Lms\Repositories\CurriculumRepository;

class Masterstudy extends Migration implements MigrationInterface {

	public $course;
	public $logs = [];
	public function __construct( $course_id ) {
		$this->course = get_post( $course_id );
	}

	public function run_migration() {
		if ( $this->course ) {
			// Migrate course
			$this->migrate_course( $this->course );
			// Migrate Reviews
			$this->migrate_course_reviews( $this->course );
		}
	}

	public function get_logs() {
		return $this->logs;
	}

	public function migrate_course( $course ) {
		$course_id = $course->ID;
		wp_update_post(array(
			'ID' => $course_id,
			'post_type' => 'academy_courses',
			'post_content' => '<!-- wp:html -->' . $course->post_content . '<!-- /wp:html -->'
		) );
		// migrate course instructor
		$this->migrate_course_author( $course->post_author, $course_id );
		// migrate course meta
		$this->migrate_course_meta( $course_id );
		// migrate course section
		$this->migrate_course_section( $course_id );
		// migrate course orders
		$this->woo_product_insert( $course );
		// migrate course complete data
		$this->migrate_course_complete( $course_id );
		// migrate enrollments
		$this->migrate_enrollments( $course_id );
		// migrate course taxonomy
		$this->migrate_course_taxonomy();
	}

	public function migrate_course_author( $author, $course_id ) {
		add_user_meta( $author, 'academy_instructor_course_id', $course_id );
	}

	public function migrate_course_meta( $course_id ) {
		// thumbnail id
		$thumbnail = get_post_meta( $course_id, '_thumbnail_id', true );
		set_post_thumbnail( $course_id, $thumbnail );
		// target audience
		update_post_meta( $course_id, 'academy_course_audience', '' );
		// update level
		$level = get_post_meta( $course_id, 'level', true );
		if ( 'advanced' === $level ) {
			$level = 'experts';
		}
		update_post_meta( $course_id, 'academy_course_difficulty_level', $level ? $level : 'beginner' );
		// update max student
		update_post_meta( $course_id, 'academy_course_max_students', 0 );
		// course requirements
		update_post_meta( $course_id, 'academy_course_requirements', '' );
		// course duration
		$enrollment = get_post_meta( $course_id, 'end_time', true );
		add_post_meta( $course_id, 'academy_course_expire_enrollment', isset( $enrollment ) ? $enrollment : 0 );
		// course announcement
		$announcement = get_post_meta( $course_id, 'announcement', true );
		add_post_meta( $course_id, 'academy_is_enabled_course_announcements', $announcement ? $announcement : true );
		// course enable QA
		add_post_meta( $course_id, 'academy_is_enabled_course_qa', true );
		// course metarial
		add_post_meta( $course_id, 'academy_course_materials_included', '' );
		// course language
		add_post_meta( $course_id, 'academy_course_language', '' );
		// course benefits
		add_post_meta( $course_id, 'academy_course_benefits', '' );
		// course expire enrollment
		update_post_meta( $course_id, 'academy_course_duration', array( 0, 0, 0 ) );
		// course intro video
		update_post_meta( $course_id, 'academy_course_intro_video', array() );
		update_post_meta( $course_id, 'academy_course_drip_content_enabled', false );
		update_post_meta( $course_id, 'academy_course_drip_content_type', 'schedule_by_date' );
		update_post_meta( $course_id, 'academy_prerequisite_type', 'course' );
		update_post_meta( $course_id, 'academy_prerequisite_courses', array() );
		update_post_meta( $course_id, 'academy_prerequisite_categories', array() );
	}

	public function migrate_course_section( $course_id ) {
		$curriculum   = new CurriculumRepository();
		$curriculums   = $curriculum->get_curriculum( $course_id, true );
		$new_curriculums = array();
		if ( $curriculums ) {
			foreach ( $curriculums as $section ) {
				$title = $section['title'];
				$items = array();
				foreach ( $section['materials'] as $key => $value ) {
					if ( 'stm-lessons' === $value['post_type'] ) {
						$lesson = get_post( $value['post_id'] );
						$items[] = $this->migrate_course_lesson( $lesson );
					} elseif ( 'stm-quizzes' === $value['post_type'] ) {
						$items[] = $this->migrate_course_quiz( $value['post_id'] );
					}
				}
				if ( $title || $items ) {
					$new_curriculums[] = array(
						'title' => $title ? $title : 'Academy Topics',
						'content' => '',
						'topics' => $items,
					);
				}
			}
		}//end if

		update_post_meta( $course_id, 'academy_course_curriculum', $new_curriculums );
	}

	public function migrate_course_lesson( $lesson ) {
		$lesson_id = $lesson->ID;
		$lesson_excerpt = get_post_meta( $lesson_id, 'lesson_excerpt', true );
		$lesson_data = array(
			'lesson_author'   => $lesson->post_author,
			'lesson_title'    => $lesson->post_title,
			'lesson_status'   => 'publish',
			'lesson_content'  => '<!-- wp:html -->' . $lesson_excerpt . $lesson->post_content . '<!-- /wp:html -->',
		);
		$new_lesson_id = \Academy\Classes\Query::lesson_insert( $lesson_data );
		$video_source = $this->get_video_source( $lesson_id );
		$preview = get_post_meta( $lesson_id, 'preview', true );
		$featured_media = get_post_meta( $lesson_id, '_thumbnail_id', true );
		$attachment_id = trim( get_post_meta( (int) $lesson_id, 'lesson_files', true ), '[]' );
		$lesson_meta = [
			'featured_media' => isset( $featured_media ) ? $featured_media : 0,
			'attachment' => $attachment_id,
			'is_previewable' => 'on' === $preview ? true : false,
			'video_duration' => wp_json_encode( array(
				'hours' => 0,
				'minutes' => 0,
				'seconds' => 0
			) ),
			'video_source' => wp_json_encode( $video_source ),
		];
		\Academy\Classes\Query::lesson_meta_insert( $new_lesson_id, $lesson_meta );

		return array(
			'id' => $new_lesson_id,
			'name' => $lesson->post_title,
			'type' => 'lesson',
		);
	}

	public function get_video_source( $lesson_id ) {
		$video_type = get_post_meta( $lesson_id, 'video_type', true );
		switch ( $video_type ) {
			case 'youtube':
				return array(
					'type' => 'youtube',
					'url'  => get_post_meta( $lesson_id, 'lesson_youtube_url', true )
				);
			case 'vimeo':
				return array(
					'type' => 'vimeo',
					'url'  => get_post_meta( $lesson_id, 'lesson_vimeo_url', true )
				);
			case 'html':
				return array(
					'id'   => get_post_meta( $lesson_id, 'lesson_video', true ),
					'type' => 'html5',
					'url'  => '',
				);
			case 'ext_link':
				return array(
					'type' => 'external',
					'url'  => get_post_meta( $lesson_id, 'lesson_ext_link_url', true )
				);
			case 'embed':
				return array(
					'type' => 'embedded',
					'url'  => get_post_meta( $lesson_id, 'lesson_embed_ctx', true )
				);
			default:
				return array(
					'type' => '',
					'url' => ''
				);
		}//end switch
	}

	public function migrate_course_quiz( $quiz_id ) {
		$quiz = get_post( $quiz_id );
		// quiz migrate
		wp_update_post(array(
			'ID' => $quiz_id,
			'post_type' => 'academy_quiz',
		) );
		// quiz meta update
		$attempts_allowed = get_post_meta( $quiz_id, 're_take_cut', true );
		$time_unit = get_post_meta( $quiz_id, 'duration_measure', true );
		$quiz_metas = array(
			'_wp_page_template' => '',
			'academy_quiz_drip_content' => '',
			'academy_quiz_time' => get_post_meta( $quiz_id, 'duration', true ),
			'academy_quiz_time_unit' => isset( $time_unit ) ? $time_unit : 'minutes',
			'academy_quiz_hide_quiz_time' => false,
			'academy_quiz_feedback_mode' => $attempts_allowed ? 'retry' : 'default',
			'academy_quiz_passing_grade' => get_post_meta( $quiz_id, 'passing_grade', true ),
			'academy_quiz_max_questions_for_answer' => 0,
			'academy_quiz_max_attempts_allowed' => $attempts_allowed,
			'academy_quiz_auto_start' => false,
			'academy_quiz_questions_order' => 'default',
			'academy_quiz_hide_question_number' => false,
			'academy_quiz_short_answer_characters_limit' => 200,
			'academy_quiz_questions' => [],
		);
		foreach ( $quiz_metas as $key => $value ) {
			add_post_meta( $quiz_id, $key, $value, true );
		}
		$question_ids = get_post_meta( $quiz_id, 'questions', true );
		if ( isset( $question_ids ) ) {
			$question_ids = explode( ',', $question_ids );
			foreach ( $question_ids as $question_id ) {
				$question = get_post( $question_id );
				$question_type = $this->get_question_type( get_post_meta( $question_id, 'type', true ) );
				if ( ! $question_type ) {
					continue;
				}
				$array = array(
					'quiz_id' => $quiz_id,
					'question_title' => $question->post_title,
					'question_content' => $question->post_content,
					'question_status' => 'publish',
					'question_type' => $question_type,
					'question_score' => 1,
					'question_order' => $question->menu_order,
					'question_settings' => wp_json_encode(array(
						'display_points' => false,
						'answer_required' => false,
						'randomize' => false,
					)),
				);
				$alms_question_id = \AcademyQuizzes\Classes\Query::quiz_question_insert( $array );
				// quiz questions meta update
				$old_quiz_questions = get_post_meta( $quiz_id, 'academy_quiz_questions', true );
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
				update_post_meta( $quiz_id, 'academy_quiz_questions', $academy_quiz_question, $old_quiz_questions );
				// quiz answer migrate
				$answers = get_post_meta( $question_id, 'answers', true );
				if ( is_array( $answers ) ) {
					foreach ( $answers as $answer ) {
						$answer_title = $answer['text'];
						$answer_content = '';

						if ( 'fillInTheBlanks' === $question_type ) {
							$answer_text = $answer['text'];
							preg_match_all( '/\|.*?\|/', $answer_text, $matches );
							if ( isset( $matches[0] ) ) {
								foreach ( $matches[0] as $key => $value ) {
									$answer_values = explode( '|', $value );
									$answer_content .= $answer_values[1] . '|';
								}
								$answer_title = str_replace( $matches[0], '{dash}', $answer_text );
							}
						}
						$array = array(
							'quiz_id' => $quiz_id,
							'question_id' => $alms_question_id,
							'question_type' => $question_type,
							'answer_title' => $answer_title,
							'answer_content' => $answer_content,
							'is_correct' => $answer['isTrue'] ? true : false,
							'image_id' => is_array( $answer['text_image'] ) ? $answer['text_image']['id'] : 0,
							'answer_order' => 0,
							'view_format' => is_array( $answer['text_image'] ) ? 'textAndImage' : 'text',
						);
						\AcademyQuizzes\Classes\Query::quiz_answer_insert( $array );
					}//end foreach
				}//end if
			}//end foreach
		}//end if
		return array(
			'id' => $quiz_id,
			'name' => $quiz->post_title,
			'type' => 'quiz',
		);
	}

	public function get_question_type( $mst_question_type ) {
		switch ( $mst_question_type ) {
			case 'true_false':
				return 'trueFalse';
			case 'multi_choice':
				return 'multipleChoice';
			case 'single_choice':
				return 'singleChoice';
			case 'fill_the_gap':
				return 'fillInTheBlanks';
			default:
				return '';
		}
	}

	public function woo_product_insert( $course ) {
		$id = $course->ID;
		$regular_price = get_post_meta( $id, 'price', true );
		$sale_price = get_post_meta( $id, 'sale_price', true );
		if ( $regular_price ) {
			$course_type = 'paid';
			$args = array(
				'course_id' => $id,
				'course_title' => $course->post_title,
				'course_slug' => $course->post_name,
				'regular_price' => $regular_price,
				'sale_price' => $sale_price,
			);
			$this->woo_create_or_update_product( $args );
		} else {
			$course_type = 'free';
			update_post_meta( $id, 'academy_course_product_id', 0 );
		}
		// update course meta
		update_post_meta( $id, 'academy_course_type', $course_type );
	}

	public function migrate_enrollments( $course_id ) {
		global $wpdb;
		$enrollments = $wpdb->get_results( $wpdb->prepare(
			"SELECT * from {$wpdb->prefix}stm_lms_user_courses 
			WHERE course_id = %d AND status = %s ",
			$course_id, 'enrolled'
		) );
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
						update_user_meta( $enrollment->user_id, 'is_academy_student', $enrollment->start_time );
					}
				}
			}
		}
	}

	public function migrate_course_complete( $course_id ) {
		global $wpdb;
		$complete_courses = $wpdb->get_results( $wpdb->prepare(
			"SELECT * from {$wpdb->prefix}stm_lms_user_courses 
			WHERE course_id = %d AND status = %s AND progress_percent = %d",
			$course_id, 'enrolled', 100
		) );
		if ( $complete_courses ) {
			$this->migrate_course_complete_data( $complete_courses, $course_id );
		}
	}

	public function migrate_course_reviews( $course ) {
		global $wpdb;
		$reviews = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta}
				WHERE meta_key = %s AND meta_value = %d",
				'review_course', $course->ID
			)
		);
		if ( is_array( $reviews ) ) {
			foreach ( $reviews as $review ) {
				$review_post = get_post( $review->post_id );
				$user_id = get_post_meta( $review->post_id, 'review_user', true );
				$rating = get_post_meta( $review->post_id, 'review_mark', true );
				if ( $review_post->post_author === $user_id && 'publish' === $review_post->post_status ) {
					$user_data = get_user_by( 'ID', $user_id );
					$comment_author = $user_data ? $user_data->display_name : '';
					$email = $user_data ? $user_data->user_email : '';
					$wpdb->insert(
						$wpdb->comments,
						array(
							'comment_post_ID' => $course->ID,
							'comment_content' => $review_post->post_content,
							'comment_author_email' => $email,
							'comment_approved' => true,
							'comment_type'    => 'academy_courses',
							'comment_agent'   => 'academy',
							'user_id'         => $user_id,
							'comment_author'  => $comment_author,
						),
						array( '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
					);

					$wpdb->insert(
						$wpdb->commentmeta,
						array(
							'meta_key'   => 'academy_rating',
							'meta_value' => $rating,
							'comment_id' => $wpdb->insert_id,
						),
						array( '%s', '%d', '%d' )
					);
				}//end if
			}//end foreach
		}//end if
	}

	public function migrate_course_taxonomy() {
		// course category
		$this->migrate_taxonomy_category( 'stm_lms_course_taxonomy', 'academy_courses_category' );
	}
}
