<?php
namespace Academy\Admin;

use Academy\Classes\ExportBase;
use Academy\Classes\CourseExport;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Lesson\LessonApi\Lesson as LessonApi;
class Export extends ExportBase {
	public static function init() {
		$self = new self();
		add_action( 'admin_init', [ $self, 'export_lessons' ], -1 );
		add_action( 'admin_init', [ $self, 'course_export_data' ], -1 );
	}
	public function export_lessons() {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
		$exportType = isset( $_GET['exportType'] ) ? sanitize_text_field( $_GET['exportType'] ) : '';
		if ( 'academy-tools' !== $page || 'lessons' !== $exportType || ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		// Verify nonce
		check_ajax_referer( 'academy_nonce', 'security' );
		$csv_data = $this->get_lessons_for_export();
		if ( ! count( $csv_data ) ) {
			return false;
		}
		$filename = 'academy-' . $exportType;
		$filename .= '.' . gmdate( 'Y-m-d' ) . '.csv';
		$this->array_to_csv_download(
			$csv_data,
			$filename
		);
		exit();
	}

	public function course_export_data() {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
		$exportType = isset( $_GET['exportType'] ) ? sanitize_text_field( $_GET['exportType'] ) : '';
		if ( 'academy-tools' !== $page || 'course' !== $exportType || ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		// Verify nonce
		check_admin_referer( 'academy_nonce', 'security' );
		$CompletedCourseExport = new CourseExport();
		$csv_data = $CompletedCourseExport->get_courses_for_export();
		if ( ! count( $csv_data ) ) {
			return false;
		}
		$filename = 'academy-' . $exportType;
		$filename .= '.' . gmdate( 'Y-m-d' ) . '.csv';
		$CompletedCourseExport->array_to_csv_download(
			$csv_data,
			$filename,
			false
		);
		exit();
	}

	public function get_lessons_for_export() {
		$csv_data = [];
		$lessons = LessonApi::get( 1, -1 );

		if ( count( $lessons ) > 0 ) {
			foreach ( $lessons as $lesson ) {
				$lesson = $lesson->get_data();
				$author = get_userdata( $lesson['lesson_author'] );
				$csv_data[] = [
					'title'                     => $lesson['lesson_title'],
					'content'                   => $lesson['lesson_content'],
					'status'                    => $lesson['lesson_status'],
					'author'                    => $author->user_login,
					'is_previewable'            => $lesson['meta']['is_previewable'] ?? false,
					'video_duration'            => wp_json_encode( $lesson['meta'] ),
					'video_source_type'         => $lesson['meta']['video_source']['type'],
					'video_source_url'          => $lesson['meta']['video_source']['url'],
				];
			}
			return $csv_data;
		}
		return [
			array(
				'title'                     => '',
				'content'                   => '',
				'status'                    => '',
				'author'                    => '',
				'is_previewable'            => '',
				'video_duration'            => '',
				'video_source_type'         => '',
				'video_source_url'          => '',
			)
		];
	}
}
