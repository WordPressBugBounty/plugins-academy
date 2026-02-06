<?php
namespace Academy\Ajax\CourseImport\Importers;

use Exception;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class Course implements Interfaces\Insertable {
	public int $id;
	protected string $title;
	protected string $content;
	protected string $status = 'publish';
	protected array $data;
	protected array $meta = [
		'academy_course_curriculum' => [],
		'academy_course_intro_video' => [],
		'academy_course_duration' => [ 0, 0, 0 ],
		'academy_rcp_membership_levels' => [],
		'academy_course_enable_certificate' => 1,
		'academy_course_certificate_id' => 0,
		'academy_is_disabled_course_review' => '',
		'academy_is_enabled_course_announcements' => 1,
		'academy_is_enabled_course_qa' => 1,
		'academy_course_materials_included' => '',
		'academy_course_audience' => '',
		'academy_course_requirements' => '',
		'academy_course_benefits' => '',
		'academy_course_difficulty_level' => 'beginner',
		'academy_course_language' => '',
		'academy_course_max_students' => 0,
		'acdemy_store_product' => 0,
		'academy_course_download_id' => 0,
		'academy_course_product_id' => 0,
		'academy_course_type' => 'free',
		'academy_course_expire_enrollment' => 0,
	];
	protected int $thumbnail_id;
	protected bool $is_edit;

	public function __construct( array $data, int $course_id, int $thumbnail_id ) {

		$this->is_edit = $course_id > 0;
		if ( $this->is_edit && ! get_post( $course_id ) ) {
			throw new Exception( __( 'Course not found.', 'academy' ) );
		}

		if ( $thumbnail_id > 0 && ! wp_attachment_is_image( $thumbnail_id ) ) {
			throw new Exception( __( 'Invalid thumbnail ID.', 'academy' ) );
		}

		if ( empty( $data ) || empty( $data['modules'] ?? [] ) ) {
			throw new Exception( __( 'Data is empty.', 'academy' ) );
		}
		$this->id = $course_id;
		$this->thumbnail_id = $thumbnail_id;
		$this->data = $data;
		$this->title = $data['courseTitle'] ?? '';
		$this->content = $data['courseDescription'] ?? '';
		$this->meta['academy_course_duration'] = array_values( $data['courseDuration'] ?? [] );
		$this->meta['academy_course_difficulty_level'] = $data['difficultyLevel'] ?? 'beginner';
		$this->meta['academy_course_language'] = $data['language'] ?? '';
		$this->meta['academy_course_requirements'] = $data['requirements'] ?? '';
		$this->meta['academy_course_benefits'] = $data['benefit_of_the_course'] ?? '';
		$this->meta['academy_course_audience'] = $data['targeted_audience'] ?? '';
		$this->meta['academy_course_materials_included'] = $data['materials_included'] ?? '';
	}

	public function insert() : int {
		$id = wp_insert_post( array_merge( $this->is_edit ? [ 'ID' => $this->id ] : [], [
			'post_title' => $this->title,
			'post_type' => 'academy_courses',
			'post_content' => $this->content,
			'post_status' => $this->status
		] ) );

		if ( is_wp_error( $id ) ) {
			throw new Exception( __( 'Error.', 'academy' ) );
		}
		$this->id = $id;

		if ( $this->thumbnail_id > 0 ) {
			set_post_thumbnail( $this->id, $this->thumbnail_id );
		}

		$this->delete_old_data();
		$this->insert_modules();
		$this->insert_meta();

		return $this->id;
	}

	protected function insert_lessons( array $lessons ) : array {
		$topics = [];
		foreach ( $lessons as $lesson ) {
			try {
				$id = ( new Lesson( $lesson['lessonTitle'] ?? '', $lesson['lessonDescription'] ?? '', $lesson['duration'] ?? [] ) )->insert();
				$topics[] = [
					'id' => $id,
					'name' => $lesson['lessonTitle'] ?? '',
					'type' => 'lesson',
				];
			} catch ( Exception $e ) {// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				// do nothing
			}
		}
		return $topics;
	}
	protected function insert_quizzes( array $quizzes ) : array {
		if ( empty( $quizzes ) ) {
			return [];
		}
		$topics = [];
		try {
			$name = 'Quizzes';
			$quiz_id = ( new Quiz( $name, '', $quizzes ) )->insert();
			$topics[] = [
				'id' => $quiz_id,
				'name' => $name,
				'type' => 'quiz',
			];
		} catch ( Exception $e ) {// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// do nothing
		}

		return $topics;
	}

	protected function insert_modules() : void {
		foreach ( $this->data['modules'] ?? [] as $module ) {
			$this->meta['academy_course_curriculum'][] = [
				'title'   => $module['moduleTitle'] ?? '',
				'content' => $module['moduleDescription'] ?? '',
				'topics'  => array_merge(
					$this->insert_lessons( $module['lessons'] ?? [] ),
					$this->insert_quizzes( $module['quiz'] ?? [] ),
				),
			];
		}
	}
	protected function insert_meta() : void {
		foreach ( $this->meta as $key => $value ) {
			update_post_meta( $this->id, $key, $value );
		}
	}
	protected function delete_old_data() : void {
		if ( empty( $this->id ) ) {
			return;
		}

		$modules = get_post_meta( $this->id, 'academy_course_curriculum', true );

		foreach ( $modules as [ 'topics' => $topics ] ) {
			foreach ( $topics as [ 'id' => $id, 'type' => $type ] ) {
				switch ( $type ) {
					case 'quiz':
						Quiz::delete( $id );
						break;
					case 'lesson':
						Lesson::delete( $id );
						break;
				}
			}
		}
	}
}
