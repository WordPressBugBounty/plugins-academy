<?php
/**
 * Seeds one complete showcase course: a full curriculum whose lessons cover
 * every content/video-source type, plus a quiz spanning every question type
 * (when the Quizzes addon is active).
 *
 * Lessons are created through the `LessonApi` abstraction so they land in
 * whichever backend ("HP" custom tables or the `academy_lessons` post type)
 * the site is actually configured to use, matching how the course builder
 * creates lessons.
 *
 * @package AcademySeeder\Providers
 */

namespace AcademySeeder\Providers;

use AcademySeeder\Classes\AbstractSeederProvider;
use AcademySeeder\Classes\Manager;
use AcademySeeder\Classes\QuizBuilder;
use AcademySeeder\Classes\SeederContext;
use AcademySeeder\Classes\SeederData;
use Academy\Lesson\LessonApi\Lesson as LessonApi;
use Throwable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CourseProvider extends AbstractSeederProvider {

	/**
	 * Cached self-hosted-video attachment id for the run (downloaded once).
	 *
	 * @var int|null
	 */
	private $video_attachment_id = null;

	public function get_key() {
		return 'course';
	}

	public function get_label() {
		return __( 'Sample Course', 'academy' );
	}

	public function get_description() {
		return __( 'A full course with every lesson type (text, YouTube, Vimeo, self-hosted, external, embedded, shortcode) and a quiz covering every question type.', 'academy' );
	}

	public function get_dependencies() {
		return [ 'categories' ];
	}

	public function get_default_count() {
		return 1;
	}

	public function seed( SeederContext $context, $count ) {
		for ( $i = 0; $i < $count; $i++ ) {
			$this->video_attachment_id = null;
			$this->create_course( $context, $i );
		}
	}

	/**
	 * Build a single course end to end.
	 *
	 * @param SeederContext $context Run context.
	 * @param int           $index   Zero-based course index (for unique titles).
	 *
	 * @return void
	 */
	private function create_course( SeederContext $context, $index ) {
		$thumbnail_id = SeederData::create_placeholder_image( 'Course', $index );
		if ( $thumbnail_id ) {
			update_post_meta( $thumbnail_id, Manager::MARKER_META, 1 );
			$context->record( 'attachment', $thumbnail_id );
		}

		$curriculum   = [];
		$quiz_placed  = false;
		$quiz_enabled = \Academy\Helper::get_addon_active_status( 'quizzes' );
		$modules      = SeederData::modules();
		$last_index   = count( $modules ) - 1;

		foreach ( $modules as $module_index => $module ) {
			$topics = [];

			foreach ( $module['lessons'] as $lesson_index => $lesson ) {
				$lesson_id = $this->create_lesson( $context, $lesson, $thumbnail_id, ( 0 === $module_index && 0 === $lesson_index ) );
				if ( $lesson_id ) {
					$topics[] = [
						'id'   => $lesson_id,
						'name' => $lesson['title'],
						'type' => 'lesson',
					];
				}
			}

			// Drop the quiz into the last module.
			if ( $quiz_enabled && ! $quiz_placed && $module_index === $last_index ) {
				$quiz_id = QuizBuilder::build( $context, __( 'Knowledge Check', 'academy' ) );
				if ( $quiz_id ) {
					$topics[]    = [
						'id'   => $quiz_id,
						'name' => __( 'Knowledge Check', 'academy' ),
						'type' => 'quiz',
					];
					$quiz_placed = true;
				}
			}

			$curriculum[] = [
				'title'   => $module['title'],
				'content' => $module['description'],
				'topics'  => $topics,
			];
		}//end foreach

		$title = SeederData::course_title();
		if ( $index > 0 ) {
			/* translators: %d: sample course number */
			$title .= sprintf( __( ' #%d', 'academy' ), $index + 1 );
		}

		// The course author is its (default) instructor. Without the
		// `academy_instructor_course_id` link the course shows no instructor.
		$author_id = get_current_user_id();
		if ( ! $author_id ) {
			$admins    = get_users(
				[
					'role'   => 'administrator',
					'number' => 1,
					'fields' => 'ID',
				]
			);
			$author_id = (int) ( $admins[0] ?? 0 );
		}

		$course_id = wp_insert_post( [
			'post_author'  => $author_id,
			'post_title'   => $title,
			'post_type'    => 'academy_courses',
			'post_content' => SeederData::course_description(),
			'post_status'  => 'publish',
		] );

		if ( is_wp_error( $course_id ) || ! $course_id ) {
			return;
		}

		update_post_meta( $course_id, Manager::MARKER_META, 1 );
		$context->record( 'course', $course_id );

		if ( $author_id && ! AcademyHelper::has_user_meta_exists( $author_id, 'academy_instructor_course_id', $course_id ) ) {
			add_user_meta( $author_id, 'academy_instructor_course_id', $course_id );
		}

		if ( $thumbnail_id ) {
			set_post_thumbnail( $course_id, $thumbnail_id );
		}

		$this->assign_terms( $course_id );
		$this->set_course_meta( $course_id, $curriculum );
	}

	/**
	 * Create a lesson row + its meta and return the new id.
	 *
	 * @param SeederContext $context       Run context.
	 * @param array         $lesson        Lesson blueprint [title, source].
	 * @param int           $featured_id   Attachment to use for featured/attachment meta.
	 * @param bool          $is_previewable Mark as a free preview lesson.
	 *
	 * @return int
	 */
	private function create_lesson( SeederContext $context, array $lesson, $featured_id, $is_previewable ) {
		$title = $lesson['title'];

		$video_source = SeederData::video_source_for( $lesson['source'], $this->resolve_video_attachment( $context, $lesson['source'] ) );

		$meta = [
			'featured_media' => (int) $featured_id,
			'attachment'     => (int) $featured_id,
			'video_duration' => [
				'hours'   => 0,
				'minutes' => 5,
				'seconds' => 30,
			],
			'is_previewable' => $is_previewable ? 1 : 0,
		];

		if ( ! empty( $video_source ) ) {
			$meta['video_source'] = $video_source;
		}

		try {
			$lesson_model = LessonApi::create( [
				'lesson_title'   => $title,
				'lesson_name'    => sanitize_title( $title ) . '-' . wp_generate_password( 4, false ),
				'lesson_content' => SeederData::paragraph(),
				'lesson_status'  => 'publish',
			], $meta );
			$lesson_model->save();
		} catch ( Throwable $e ) {
			return 0;
		}

		$lesson_id = (int) $lesson_model->id();
		$context->record( 'lesson', $lesson_id );

		return $lesson_id;
	}

	/**
	 * The self-hosted lesson needs a real video attachment. Download it once per
	 * course (best effort) and reuse. Non-html5 sources need no attachment.
	 *
	 * @param SeederContext $context Run context.
	 * @param string        $source  Lesson source type.
	 *
	 * @return int Attachment id (0 when not applicable or download failed).
	 */
	private function resolve_video_attachment( SeederContext $context, $source ) {
		if ( 'html5' !== $source ) {
			return 0;
		}

		if ( null === $this->video_attachment_id ) {
			$this->video_attachment_id = SeederData::sideload_video( SeederData::SAMPLE_VIDEO_URL );
			if ( $this->video_attachment_id ) {
				update_post_meta( $this->video_attachment_id, Manager::MARKER_META, 1 );
				$context->record( 'attachment', $this->video_attachment_id );
			}
		}

		return (int) $this->video_attachment_id;
	}

	/**
	 * Assign the sample course to the seeded categories + tags (resolved by name
	 * so it works whether the term was seeded now or already existed).
	 *
	 * @param int $course_id Course id.
	 *
	 * @return void
	 */
	private function assign_terms( $course_id ) {
		$this->assign_taxonomy( $course_id, SeederData::categories(), CategoryProvider::CATEGORY_TAXONOMY );
		$this->assign_taxonomy( $course_id, SeederData::tags(), CategoryProvider::TAG_TAXONOMY );
	}

	/**
	 * @param int      $course_id Course id.
	 * @param string[] $names     Term names.
	 * @param string   $taxonomy  Taxonomy slug.
	 *
	 * @return void
	 */
	private function assign_taxonomy( $course_id, array $names, $taxonomy ) {
		$term_ids = [];
		foreach ( $names as $name ) {
			$term = get_term_by( 'name', $name, $taxonomy );
			if ( $term ) {
				$term_ids[] = (int) $term->term_id;
			}
		}
		if ( $term_ids ) {
			wp_set_object_terms( $course_id, $term_ids, $taxonomy );
		}
	}

	/**
	 * Write the course meta, including the assembled curriculum.
	 *
	 * @param int   $course_id  Course id.
	 * @param array $curriculum Curriculum tree.
	 *
	 * @return void
	 */
	private function set_course_meta( $course_id, array $curriculum ) {
		$meta = [
			'academy_course_curriculum'               => $curriculum,
			'academy_course_intro_video'              => [ 'youtube', SeederData::SAMPLE_YOUTUBE_URL ],
			'academy_course_duration'                 => [ 2, 30, 0 ],
			'academy_course_enable_certificate'       => 1,
			'academy_course_certificate_id'           => 0,
			'academy_is_disabled_course_review'       => '',
			'academy_is_enabled_course_announcements' => 1,
			'academy_is_enabled_course_qa'            => 1,
			'academy_course_materials_included'       => __( 'Source files, slides and cheat-sheets.', 'academy' ),
			'academy_course_audience'                 => __( 'Beginners who want a hands-on introduction.', 'academy' ),
			'academy_course_requirements'             => __( 'A computer and an internet connection.', 'academy' ),
			'academy_course_benefits'                 => __( 'Build real projects from scratch and understand the fundamentals.', 'academy' ),
			'academy_course_difficulty_level'         => 'beginner',
			'academy_course_language'                 => __( 'English', 'academy' ),
			'academy_course_max_students'             => 0,
			'academy_course_type'                     => 'free',
			'academy_course_expire_enrollment'        => 0,
		];

		foreach ( $meta as $key => $value ) {
			update_post_meta( $course_id, $key, $value );
		}
	}
}
