<?php

namespace AcademyMigrationTool\Classes;

use Academy\Helper as Helper;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyMigrationTool\Interfaces\MigrationInterface;

class Tutor  extends Migration implements MigrationInterface {
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
		// Course update
		wp_update_post(
			array(
				'ID'           => $course_id,
				'post_type'    => 'academy_courses',
				'post_name'    => Helper::generate_unique_lesson_slug( $course->post_name ),
				'comment_status' => 'open',
				'post_ping'    => 'open',
				'post_content' => '<!-- wp:html -->' . wp_kses_post( $course->post_content ) . '<!-- /wp:html -->',
			)
		);
		$this->migrate_course_author( $course->post_author, $course_id );
		// ALMS course meta update
		$this->migrate_course_meta( $course_id );
		// LP product insert in ALMS
		$this->woo_product_insert( $course );
		// Order Migrate to ALMS
		$this->migrate_course_orders( $course_id );
		// Enrollment Migration
		$this->migrate_enrollments( $course_id );
		// Course complete status Migrate to ALMS
		$this->migrate_course_complete( $course_id );
		// course topics update
		$this->migrate_course_topics( $course_id );
		// Migrate Tutor Announcements to Alms
		$this->migrate_announcements( $course );
		// course review migrate
		$this->migrate_course_reviews( $course_id );
		// Migrate course taxonomy
		$this->migrate_course_taxonomy();
	}

	public function migrate_course_author( $author, $course_id ) {
		$meta_key = '_tutor_instructor_course_id';
		$meta_value = $course_id;
		$user_ids = get_users(
			array(
				'meta_key' => $meta_key,
				'meta_value' => $meta_value,
			)
		);
		foreach ( $user_ids as $user_id ) {
			add_user_meta( $user_id->ID, 'academy_instructor_course_id', $course_id );
		}
	}

	public function migrate_announcements( $course ) {
		if ( $course->ID ) {
			$tutor_announcements_posts = get_posts(array(
				'post_parent' => $course->ID,
				'post_type'   => 'tutor_announcements',
			));
			if ( $tutor_announcements_posts ) {
				foreach ( $tutor_announcements_posts as $tutor_announcements_post ) {
					$ID = $tutor_announcements_post->ID;
					wp_update_post( array(
						'ID'        => $ID,
						'post_type' => 'academy_announcement',
					) );
					$data[] = array(
						'label' => $course->post_title,
						'value' => $course->ID,
					);
					add_post_meta( $ID, 'academy_announcements_course_ids', $data );
				}
			}
			add_post_meta( $course->ID, 'academy_is_enabled_course_announcements', isset( $tutor_announcements_posts ) ? 1 : 0 );
		}//end if
	}

	public function migrate_course_topics( $course_id ) {
		global $wpdb;

		$topics = get_posts([
			'post_type'      => 'topics',
			'post_parent'    => $course_id,
			'order'          => 'ASC',
			'orderby'        => 'menu_order',
			'posts_per_page' => -1,
		]);

		if ( empty( $topics ) ) {
			return;
		}

		$new_curriculums = [];

		foreach ( $topics as $topic ) {
			$items = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->posts} WHERE post_parent = %d AND post_status = %s",
					$topic->ID,
					'publish'
				)
			);

			$total_items = [];

			foreach ( $items as $item ) {
				switch ( $item->post_type ) {
					case 'lesson':
						$total_items[] = $this->migrate_course_lesson( $item );
						break;
					case 'tutor_quiz':
						$total_items[] = $this->migrate_course_quiz( $item );
						break;
					case 'tutor_assignments':
						$total_items[] = $this->migrate_course_assignments( $item );
						break;
				}
			}

			$new_curriculums[] = [
				'title'   => sanitize_text_field( $topic->post_title ),
				'content' => sanitize_text_field( $topic->post_content ),
				'topics'  => $total_items,
			];
		}//end foreach

		update_post_meta( $course_id, 'academy_course_curriculum', $new_curriculums );
	}

	public function migrate_course_meta( $id ) {
		$course_settings = get_post_meta( $id, '_tutor_course_settings', true );
		// max students
		$student = isset( $course_settings['maximum_students'] ) ? (int) $course_settings['maximum_students'] : 0;
		update_post_meta( $id, 'academy_course_max_students', $student );
		// course expire enrollment
		$enrollment_expiry = isset( $course_settings['enrollment_expiry'] ) ? (int) $course_settings['enrollment_expiry'] : 0;
		update_post_meta( $id, 'academy_course_expire_enrollment', $enrollment_expiry );
		// content drip enabled
		update_post_meta( $id, 'academy_course_drip_content_enabled', isset( $course_settings['enable_content_drip'] ) ? 1 : false );
		// content drip type
		$content_drip = isset( $course_settings['content_drip_type'] ) ? $course_settings['content_drip_type'] : '';
		if ( 'unlock_by_date' === $content_drip ) {
			$content_drip_type = 'schedule_by_date';
		} elseif ( 'specific_days' === $content_drip ) {
			$content_drip_type = 'schedule_by_enroll_date';
		} elseif ( 'unlock_sequentially' === $content_drip ) {
			$content_drip_type = 'schedule_by_sequentially';
		} elseif ( 'after_finishing_prerequisites' === $content_drip ) {
			$content_drip_type = 'schedule_by_prerequisite';
		} else {
			$content_drip_type = 'schedule_by_date';
		}
		update_post_meta( $id, 'academy_course_drip_content_type', $content_drip_type );
		// course durations
		$course_durations = get_post_meta( $id, '_course_duration', true );
		foreach ( $course_durations as $duration ) {
			$time[] = (int) $duration;
		}
		$time[] = 0;
		update_post_meta( $id, 'academy_course_duration', ! empty( $time ) ? $time : array( 0, 0, 0 ) );
		// course qa enable
		$enable_qa = get_post_meta( $id, '_tutor_enable_qa', true );
		update_post_meta( $id, 'academy_is_enabled_course_qa', 'yes' === $enable_qa ? true : false );
		// target audience
		$audience = get_post_meta( $id, '_tutor_course_target_audience', true );
		update_post_meta( $id, 'academy_course_audience', $audience );
		// course materials
		$materials = get_post_meta( $id, '_tutor_course_material_includes', true );
		update_post_meta( $id, 'academy_course_materials_included', $materials );
		// course level
		$tr_level = get_post_meta( $id, '_tutor_course_level', true );
		if ( 'expert' === $tr_level ) {
			$tr_level = 'experts';
		}
		update_post_meta( $id, 'academy_course_difficulty_level', $tr_level );
		// course type
		$course_type = get_post_meta( $id, '_tutor_course_price_type', true );
		$public_course_status = get_post_meta( $id, '_tutor_is_public_course', true );
		if ( 'yes' === $public_course_status ) {
			update_post_meta( $id, 'academy_course_type', 'public' );
		} elseif ( 'free' === $course_type ) {
			update_post_meta( $id, 'academy_course_type', 'free' );
			add_post_meta( $id, 'academy_course_product_id', 0 );
		} elseif ( 'paid' === $course_type ) {
			$this->woo_product_insert( $id );
		}
		// thumbnail id
		$thumbnail = get_post_meta( $id, '_thumbnail_id', true );
		set_post_thumbnail( $id, $thumbnail );
		// course benefits
		$benefits = get_post_meta( $id, '_tutor_course_benefits', true );
		update_post_meta( $id, 'academy_course_benefits', $benefits );
		// course requirements
		$requirements = get_post_meta( $id, '_tutor_course_requirements', true );
		update_post_meta( $id, 'academy_course_requirements', $requirements );
		// course language
		add_post_meta( $id, 'academy_course_language', '' );
		// intro video
		$source       = $this->set_video_source( get_post_meta( $id, '_video', true ) );
		if ( ! empty( $source ) ) {
			$intro_video = array(
				$source['type'],
				$source['url']
			);
			if ( 'html5' === $source['type'] ) {
				$intro_video = array(
					$source['type'],
					$source['id'],
					$source['url']
				);
			}
		}
		update_post_meta( $id, 'academy_course_intro_video', ! empty( $intro_video ) ? $intro_video : array() );
		// course prerequisite
		add_post_meta( $id, 'academy_prerequisite_type', 'course' );
		$course_ids           = get_post_meta( $id, '_tutor_course_prerequisites_ids', true );
		$prerequisites = $this->academy_course_prerequisite( $course_ids );
		update_post_meta( $id, 'academy_prerequisite_courses', is_array( $prerequisites ) ? $prerequisites : array() );
		// prerequisite category
		add_post_meta( $id, 'academy_prerequisite_categories', array() );
		add_post_meta( $id, 'academy_is_disabled_course_review', false );
		add_post_meta( $id, 'academy_course_enable_certificate', true );
		add_post_meta( $id, 'academy_rcp_membership_levels', array() );
		add_post_meta( $id, 'academy_course_certificate_id', 0 );
		add_post_meta( $id, 'academy_course_download_id', 0 );
	}

	public function course_topics_prerequisite( $topic_ids ) {
		global $wpdb;
		$prerequisite = array();
		if ( is_array( $topic_ids ) ) {
			foreach ( $topic_ids as $topic_id ) {
				$topics = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM {$wpdb->posts} 
						WHERE ID = %d ",
						$topic_id
					)
				);
				foreach ( $topics as $topic ) {
					if ( 'lesson' === $topic->post_type ) {
						$lessons = $wpdb->get_results(
							$wpdb->prepare(
								"SELECT * FROM {$wpdb->prefix}academy_lessons WHERE lesson_title LIKE %s",
								'%' . $wpdb->esc_like( $topic->post_title ) . '%'
							)
						);
						if ( is_array( $lessons ) ) {
							foreach ( $lessons as $lesson ) {
								$prerequisite[] = array(
									'label' => 'Lesson - ' . $lesson->lesson_title,
									'type'  => 'lesson',
									'value' => (int) $lesson->ID,
								);
							}
						}
					} elseif ( 'academy_assignments' === $topic->post_type ) {
						$prerequisite[] = array(
							'label' => 'Assignment - ' . $topic->post_title,
							'type'  => 'assignment',
							'value' => (int) $topic_id,
						);
					} elseif ( 'academy_quiz' === $topic->post_type ) {
						$prerequisite[] = array(
							'label' => 'Quiz - ' . $topic->post_title,
							'type'  => 'quiz',
							'value' => (int) $topic_id,
						);
					}//end if
				}//end foreach
			}//end foreach
			return $prerequisite;
		}//end if
		return $prerequisite;
	}

	public function migrate_course_lesson( $item ) {
		$lesson_title = sanitize_text_field( $item->post_title );
		$existing_lesson = Helper::get_lesson_by_title( $lesson_title );

		if ( $existing_lesson ) {
			return [
				'id'   => $existing_lesson->ID,
				'name' => $lesson_title,
				'type' => 'lesson',
			];
		}

		$lesson_data = [
			'lesson_author'  => $item->post_author,
			'lesson_title'   => $lesson_title,
			'lesson_name'    => \Academy\Helper::generate_unique_lesson_slug( $lesson_title ),
			'lesson_status'  => $item->post_status,
			'lesson_content' => '<!-- wp:html -->' . sanitize_text_field( $item->post_content ) . '<!-- /wp:html -->',
		];

		$lesson_id = \Academy\Classes\Query::lesson_insert( $lesson_data );
		$is_previewable = (bool) get_post_meta( $item->ID, '_is_preview', true );

		$video_durations = [
			'hours'   => 0,
			'minutes' => 0,
			'seconds' => 0,
		];

		$videos = get_post_meta( $item->ID, '_video', true );

		if ( ! empty( $videos ) && 'shortcode' !== $videos['source'] ) {
			foreach ( [ 'hours', 'minutes', 'seconds' ] as $unit ) {
				if ( isset( $videos['runtime'][ $unit ] ) ) {
					$video_durations[ $unit ] = (int) $videos['runtime'][ $unit ];
				}
			}
		}

		$video_source = $videos ? $this->set_video_source( $videos ) : [];
		$featured = get_post_meta( $item->ID, '_thumbnail_id', true );

		$lesson_meta = [
			'featured_media' => $featured,
			'attachment'     => '', // upnext
			'is_previewable' => $is_previewable,
			'video_duration' => wp_json_encode( $video_durations ),
			'video_source'   => wp_json_encode( $video_source ),
		];

		$content_drip_settings = get_post_meta( $item->ID, '_content_drip_settings', true );
		if ( ! empty( $content_drip_settings ) ) {
			$drip_content = [
				'schedule_by_date'         => '',
				'schedule_by_enroll_date'  => 0,
				'schedule_by_prerequisite' => [],
			];

			if ( isset( $content_drip_settings['unlock_date'] ) ) {
				$drip_content['schedule_by_date'] = gmdate( 'Y-m-d', strtotime( $content_drip_settings['unlock_date'] ) );
			} elseif ( isset( $content_drip_settings['after_xdays_of_enroll'] ) ) {
				$drip_content['schedule_by_enroll_date'] = (int) $content_drip_settings['after_xdays_of_enroll'];
			} elseif ( isset( $content_drip_settings['prerequisites'] ) ) {
				$drip_content['schedule_by_prerequisite'] = (array) $this->course_topics_prerequisite( $content_drip_settings['prerequisites'] );
			}

			$lesson_meta['drip_content'] = $drip_content;
		}

		\Academy\Classes\Query::lesson_meta_insert( $lesson_id, $lesson_meta );

		return [
			'id'   => $lesson_id,
			'name' => $lesson_title,
			'type' => 'lesson',
		];
	}

	public function migrate_course_assignments( $item ) {
		if ( empty( $item->ID ) ) {
			return [];
		}

		$id = (int) $item->ID;

		wp_update_post(
			[
				'ID'           => $id,
				'post_type'    => 'academy_assignments',
				'post_content' => '<!-- wp:html -->' . wp_kses_post( $item->post_content ) . '<!-- /wp:html -->',
			]
		);

		$assignments = get_post_meta( $id, 'assignment_option', true );

		$assignment_settings = [
			'submission_time'        => isset( $assignments['time_duration']['value'] ) ? (int) $assignments['time_duration']['value'] : 0,
			'submission_time_unit'   => isset( $assignments['time_duration']['time'] ) ? sanitize_text_field( $assignments['time_duration']['time'] ) : '',
			'minimum_passing_points' => isset( $assignments['pass_mark'] ) ? (int) $assignments['pass_mark'] : 0,
			'total_points'           => isset( $assignments['total_mark'] ) ? (int) $assignments['total_mark'] : 0,
		];

		update_post_meta( $id, 'academy_assignment_settings', $assignment_settings );
		update_post_meta( $id, 'academy_assignment_attachment', '' ); // upnext work.

		$content_drip_settings = get_post_meta( $id, '_content_drip_settings', true );
		$drip_content          = [
			'schedule_by_prerequisite' => [],
			'schedule_by_enroll_date'  => 0,
			'schedule_by_date'         => '',
		];

		if ( ! empty( $content_drip_settings ) ) {
			if ( ! empty( $content_drip_settings['unlock_date'] ) ) {
				$unlock_date                   = strtotime( sanitize_text_field( $content_drip_settings['unlock_date'] ) );
				$drip_content['schedule_by_date'] = gmdate( 'Y-m-d', $unlock_date );
			}

			if ( ! empty( $content_drip_settings['after_xdays_of_enroll'] ) ) {
				$drip_content['schedule_by_enroll_date'] = (int) $content_drip_settings['after_xdays_of_enroll'];
			}

			if ( ! empty( $content_drip_settings['prerequisites'] ) ) {
				$drip_content['schedule_by_prerequisite'] = (array) $this->course_topics_prerequisite( $content_drip_settings['prerequisites'] );
			}
		}

		update_post_meta( $id, 'academy_assignment_drip_content', $drip_content );

		return [
			'id'   => $id,
			'name' => sanitize_text_field( $item->post_title ),
			'type' => 'assignment',
		];
	}

	public function set_video_source( $source ) {
		if ( $source ) {
			if ( 'external_url' === $source['source'] ) {
				return array(
					'type' => 'external',
					'url'  => $source['source_external_url'],
				);
			} elseif ( 'html5' === $source['source'] ) {
				return array(
					'id' => $source['source_video_id'],
					'type' => $source['source'],
					'url' => '',
				);
			} elseif ( 'youtube' === $source['source'] ) {
				return array(
					'type' => $source['source'],
					'url'  => $source['source_youtube'],
				);
			} elseif ( 'vimeo' === $source['source'] ) {
				return array(
					'type' => $source['source'],
					'url'  => $source['source_vimeo'],
				);
			} elseif ( 'embedded' === $source['source'] ) {
				return array(
					'type' => $source['source'],
					'url'  => $source['source_embedded'],
				);
			} elseif ( 'shortcode' ) {
				return array(
					'type' => '',
					'url'  => ''
				);
			}//end if
			return '';
		}//end if
		return '';
	}

	public function migrate_course_quiz( $item ) {
		global $wpdb;
		$quiz_id = $item->ID;
		$quiz    = get_post( $quiz_id );

		wp_update_post(
			array(
				'ID'        => $quiz_id,
				'post_type' => 'academy_quiz',
			)
		);

		$quiz_meta = get_post_meta( $quiz_id, 'tutor_quiz_option', true );

		$question_order = isset( $quiz_meta['questions_order'] ) ? sanitize_text_field( $quiz_meta['questions_order'] ) : 'default';

		$time       = isset( $quiz_meta['time_limit']['time_value'] ) ? (int) $quiz_meta['time_limit']['time_value'] : 0;
		$time_unit  = isset( $quiz_meta['time_limit']['time_type'] ) ? sanitize_text_field( $quiz_meta['time_limit']['time_type'] ) : '';

		// Content drip settings.
		$content_drip_settings = get_post_meta( $quiz_id, '_content_drip_settings', true );
		$date                 = '';
		$enroll_date          = 0;
		$course_prerequisites = array();

		if ( ! empty( $content_drip_settings['unlock_date'] ) ) {
			$unlock_date = strtotime( sanitize_text_field( $content_drip_settings['unlock_date'] ) );
			$date        = gmdate( 'Y-m-d', $unlock_date );
		} elseif ( ! empty( $content_drip_settings['after_xdays_of_enroll'] ) ) {
			$enroll_date = (int) $content_drip_settings['after_xdays_of_enroll'];
		} elseif ( ! empty( $content_drip_settings['prerequisites'] ) ) {
			$course_prerequisites = $this->course_topics_prerequisite( $content_drip_settings['prerequisites'] );
		}

		$drip_content = array(
			'schedule_by_prerequisite' => $course_prerequisites,
			'schedule_by_enroll_date'  => $enroll_date,
			'schedule_by_date'         => $date,
		);

		$quiz_meta_data = array(
			'academy_quiz_drip_content'                  => $drip_content,
			'academy_quiz_time'                          => $time,
			'academy_quiz_time_unit'                     => $time_unit,
			'academy_quiz_hide_quiz_time'                => ! empty( $quiz_meta['hide_quiz_time_display'] ),
			'academy_quiz_feedback_mode'                 => isset( $quiz_meta['feedback_mode'] ) && 'retry' === $quiz_meta['feedback_mode'] ? 'retry' : 'default',
			'academy_quiz_passing_grade'                 => isset( $quiz_meta['passing_grade'] ) ? (int) $quiz_meta['passing_grade'] : 0,
			'academy_quiz_max_questions_for_answer'      => isset( $quiz_meta['max_questions_for_answer'] ) ? (int) $quiz_meta['max_questions_for_answer'] : 0,
			'academy_quiz_max_attempts_allowed'          => isset( $quiz_meta['attempts_allowed'] ) ? (int) $quiz_meta['attempts_allowed'] : 0,
			'academy_quiz_auto_start'                    => false,
			'academy_quiz_questions_order'               => $question_order,
			'academy_quiz_hide_question_number'          => isset( $quiz_meta['hide_question_number_overview'] ),
			'academy_quiz_short_answer_characters_limit' => isset( $quiz_meta['short_answer_characters_limit'] ) ? (int) $quiz_meta['short_answer_characters_limit'] : 0,
			'academy_quiz_questions'                     => array(),
		);

		foreach ( $quiz_meta_data as $key => $value ) {
			add_post_meta( $quiz_id, $key, $value, true );
		}

		// Quiz question migration.
		$questions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}tutor_quiz_questions WHERE quiz_id = %d",
				$quiz_id
			)
		);

		if ( $questions ) {
			$quiz_question = [];
			foreach ( $questions as $question ) {
				$question_type_map = array(
					'true_false'       => 'trueFalse',
					'single_choice'    => 'singleChoice',
					'multiple_choice'  => 'multipleChoice',
					'fill_in_the_blank' => 'fillInTheBlanks',
					'short_answer'     => 'shortAnswer',
					'image_answering'  => 'imageAnswer',
				);

				$new_question_type = isset( $question_type_map[ $question->question_type ] ) ? $question_type_map[ $question->question_type ] : 'unknown';

				$question_settings = maybe_unserialize( $question->question_settings );
				$display_point     = ! empty( $question_settings['show_question_mark'] ) ? 'true' : 'false';
				$randomize         = ! empty( $question_settings['randomize_question'] ) ? 'true' : 'false';
				$answer_required   = ! empty( $question_settings['answer_required'] ) ? 'true' : 'false';

				$array = array(
					'quiz_id'           => (int) $quiz_id,
					'question_title'    => sanitize_text_field( $question->question_title ),
					'quiz_content'      => wp_kses_post( $question->question_description ),
					'question_status'   => 'publish',
					'question_type'     => $new_question_type,
					'question_score'    => (int) $question->question_mark,
					'question_order'    => (int) $question->question_order,
					'question_settings' => wp_json_encode(
						array(
							'display_points'  => $display_point,
							'answer_required' => $answer_required,
							'randomize'       => $randomize,
						)
					),
				);

				$alms_question_id = \AcademyQuizzes\Classes\Query::quiz_question_insert( $array );
				$quiz_question[]   = array(
					'id' => $alms_question_id,
					'title' => $array['question_title']
				);
				// Quiz answers migration.
				$answers = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}tutor_quiz_question_answers WHERE belongs_question_id = %d AND belongs_question_type = %s",
						$question->question_id,
						$question->question_type
					)
				);
				foreach ( $answers as $answer ) {
					\AcademyQuizzes\Classes\Query::quiz_answer_insert(
						array(
							'quiz_id'        => (int) $quiz_id,
							'question_id'    => (int) $alms_question_id,
							'question_type'  => $new_question_type,
							'answer_title'   => sanitize_text_field( $answer->answer_title ),
							'is_correct'     => (bool) $answer->is_correct,
							'answer_order'   => (int) $answer->answer_order,
							'view_format'    => 'text',
							'image_id'      => isset( $answer->image_id ) ? (int) $answer->image_id : 0,
						)
					);
				}
			}//end foreach
			update_post_meta( $quiz_id, 'academy_quiz_questions', $quiz_question );
		}//end if

		return array(
			'id'   => $quiz_id,
			'name' => $quiz->post_title,
			'type' => 'quiz',
		);
	}

	public function migrate_course_reviews( $course_id ) {
		global $wpdb;
		$tr_review_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT comments.comment_ID FROM {$wpdb->comments} comments 
				INNER JOIN {$wpdb->commentmeta} cm ON cm.comment_id = comments.comment_ID 
				AND cm.meta_key = %s WHERE comments.comment_post_ID = %d",
				'tutor_rating', $course_id
			)
		);
		// tutor rating migrate
		if ( $tr_review_ids ) {
			foreach ( $tr_review_ids as $review_id ) {
				$where = array(
					'comment_type'     => 'tutor_course_rating',
					'comment_agent'    => 'TutorLMSPlugin',
					'comment_approved' => 'approved',
					'comment_post_ID'  => $course_id,
				);
				$update = array(
					'comment_type'     => 'academy_courses',
					'comment_agent'    => 'academy',
					'comment_approved' => 1,
				);
				$wpdb->update( $wpdb->prefix . 'comments', $update, $where );
				$wpdb->update($wpdb->commentmeta,
					array(
						'meta_key' => 'academy_rating'
					),
					array(
						'comment_id' => $review_id,
						'meta_key' => 'tutor_rating'
					)
				);
			}//end foreach
		}//end if
	}

	public function woo_product_insert( $course_id ) {
		$product_id = get_post_meta( $course_id, '_tutor_course_product_id', true );
		$regular_price = (int) get_post_meta( $course_id, 'tutor_course_price', true );
		$sale_price = (int) get_post_meta( $course_id, 'tutor_course_sale_price', true );
		if ( ! empty( $product_id ) ) {
			update_post_meta( $course_id, 'academy_course_product_id', $product_id );
			update_post_meta( $product_id, '_academy_product', 'yes' );
			update_post_meta( $course_id, 'academy_course_type', 'paid' );
		} elseif ( $regular_price > 0 || $sale_price > 0 ) {
			$product_id = Migration::woo_create_or_update_product(
				array(
					'course_id'     => $course_id,
					'course_title'   => get_the_title( $course_id ),
					'course_slug' => get_post( $course_id )->post_name,
					'regular_price' => isset( $regular_price ) ? $regular_price : 0,
					'sale_price'    => isset( $sale_price ) ? $sale_price : 0,
				)
			);
			add_post_meta( $course_id, 'academy_course_type', 'paid' );
		}
	}

	public function migrate_course_orders( $course_id ) {
		global $wpdb;
		$enrolled_ids = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} 
				WHERE post_parent = %d AND post_type = %s",
				$course_id, 'tutor_enrolled'
			)
		);
		if ( $enrolled_ids ) {
			foreach ( $enrolled_ids as $enrolled_id ) {
				// enrolled order id update
				$order_id = get_post_meta( $enrolled_id->ID, '_tutor_enrolled_by_order_id', true );
				update_post_meta( $enrolled_id->ID, 'academy_enrolled_by_order_id', $order_id );

				// enrolled product id update
				$enroll_product_id = get_post_meta( $enrolled_id->ID, '_tutor_enrolled_by_product_id', true );
				update_post_meta( $enrolled_id->ID, 'academy_enrolled_by_product_id', $enroll_product_id );

				// order_for_course value update
				$order_for_course = get_post_meta( $order_id, '_is_tutor_order_for_course', true );
				update_post_meta( $order_id, 'is_academy_order_for_course', $order_for_course );

				// order_for_course_id update
				$order_for_course_id = get_post_meta( $order_id, '_tutor_order_for_course_id' . $course_id, true );
				update_post_meta( $order_id, 'academy_order_for_course_id_' . $course_id, $order_for_course_id );
			}
		}
	}

	public function migrate_enrollments( $course_id ) {
		global $wpdb;
		$enroll_courses = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->posts} 
				WHERE post_parent = %d AND post_type = %s",
				$course_id, 'tutor_enrolled'
			)
		);
		if ( $enroll_courses ) {
			foreach ( $enroll_courses as $enroll_course ) {
				// custom enroll post update
				wp_update_post(
					array(
						'ID'        => $enroll_course->ID,
						'post_type' => 'academy_enrolled',
					)
				);
				$tutor_student = get_user_meta( $enroll_course->post_author, '_is_tutor_student', true );
				if ( $tutor_student ) {
					update_user_meta( $enroll_course->post_author, 'is_academy_student', $tutor_student );
				}
			}
		}
	}

	public function migrate_course_complete( $course_id ) {
		global $wpdb;
		$where = array(
			'comment_type'     => 'course_completed',
			'comment_agent'    => 'TutorLMSPlugin',
			'comment_approved' => 'approved',
			'comment_post_ID'  => $course_id,
		);
		$update = array(
			'comment_type'     => 'course_completed',
			'comment_agent'    => 'academy',
			'comment_approved' => 'approved',
		);
		$wpdb->update( $wpdb->prefix . 'comments', $update, $where );
	}

	public function migrate_course_taxonomy() {
		// course category
		$this->migrate_taxonomy_category( 'course-category', 'academy_courses_category' );
		// course tag
		$this->migrate_taxonomy_tag( 'course-tag', 'academy_courses_tag' );
	}
}
