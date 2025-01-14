<?php
namespace Academy\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class CourseExport extends ExportBase {
	public function get_courses_for_export( $status = '' ) {
		$course_array = [];
		$empty_data = array(
			'post_title'       => '',
			'post_author'      => '',
			'post_date'        => '',
			'post_content'     => '',
			'post_excerpt'     => '',
			'post_status'      => '',
			'comment_status'   => '',
			'post_name'        => '',
			'post_parent'      => '',
			'post_type'        => '',
			'comment_count'    => '',
		);

		$empty_meta = array(
			'course_expire_enrollment' => '',
			'course_type' => '',
			'course_product_id' => '',
			'course_download_id	' => '',
			'course_max_students' => '',
			'course_language' => '',
			'course_difficulty_level' => '',
			'course_benefits' => '',
			'course_requirements' => '',
			'course_audience' => '',
			'course_materials' => '',
			'is_enabled_course_qa' => '',
			'is_enabled_course_announcements' => '',
			'course_duration' => '',
			'course_intro_video' => '',
			'course_curriculum' => '',
			'course_certificate_id' => '',
			'academy_rcp_membership_levels',
			'academy_course_enable_certificate',
			'academy_is_disabled_course_review',
		);

		$courses = $this->get_all_courses( 'course_completed' );
		$course_array = [];
		if ( empty( $courses ) ) {
			return [ $empty_data, $empty_meta ];
		}
		foreach ( $courses as $course ) {
			$meta = get_post_meta( $course->ID );
			if ( 'paid' === $meta['academy_course_type'][0] ) {
				$product_id = $meta['academy_course_product_id'][0] ?? null;
				$download_id = $meta['academy_course_download_id'][0] ?? null;
			}
			$curriculums = maybe_unserialize( $meta['academy_course_curriculum'][0] ?? array() );
			$course_array[] = $this->extract_post_data( $course );
			$course_array[] = array_merge(
				$this->extract_meta_data( $meta ),
				[
					'course_curriculum' => $meta['academy_course_curriculum'][0] ?? array(),
					'course_download_id' => $download_id,
					'edd_price' => get_post_meta( $download_id, 'edd_price', true ),
					'course_product_id' => $product_id,
					'regular_price' => get_post_meta( $product_id, '_regular_price', true ),
					'sale_price' => get_post_meta( $product_id, '_sale_price', true ),
				],
			);

			if ( ! empty( $curriculums ) ) {
				foreach ( $curriculums as $curriculum ) {
					$is_topics = ! empty( $curriculum['topics'] ) ? true : false;
					if ( $is_topics ) {
						foreach ( $curriculum['topics'] as $topics ) {
							if ( 'sub-curriculum' !== $topics['type'] ) {
								$data = $this->topics_make_for_csv( $topics );
								if ( 'quiz' === $topics['type'] && $data ) {
									foreach ( $data as $quiz ) {
										$course_array[] = $quiz;
									}
								} else {
									$course_array[] = $data;
								}
							} elseif ( isset( $topics['topics'] ) && ! empty( $topics['topics'] ) ) {
								foreach ( $topics['topics'] as $topic ) {
									$data = $this->topics_make_for_csv( $topic );
									if ( 'quiz' === $topic['type'] && $data ) {
										foreach ( $data as $quiz ) {
											$course_array[] = $quiz;
										}
									} else {
										$course_array[] = $data;
									}
								}
							}//end if
						}//end foreach
					}//end if
				}//end foreach
			}//end if
		}//end foreach
		return $course_array;
	}

	private function extract_post_data( $course ) {
		return [
			'post_title' => $course->post_title,
			'post_author' => $course->post_author,
			'post_date' => $course->post_date,
			'post_content' => $course->post_content,
			'post_excerpt' => $course->post_excerpt,
			'post_status' => $course->post_status,
			'comment_status' => $course->comment_status,
			'post_parent' => $course->post_parent,
			'comment_count' => $course->comment_count,
		];
	}

	private function extract_meta_data( $meta ) {
		return [
			'course_expire_enrollment' => $meta['academy_course_expire_enrollment'][0] ?? '',
			'course_type' => $meta['academy_course_type'][0] ?? 'free',
			'course_product_id' => $meta['academy_course_product_id'][0] ?? 0,
			'course_download_id' => $meta['academy_course_download_id'][0] ?? 0,
			'course_max_students' => $meta['academy_course_max_students'][0] ?? 0,
			'course_language' => $meta['academy_course_language'][0] ?? '',
			'course_difficulty_level' => $meta['academy_course_difficulty_level'][0] ?? '',
			'course_benefits' => $meta['academy_course_benefits'][0] ?? '',
			'course_requirements' => $meta['academy_course_requirements'][0] ?? '',
			'course_audience' => $meta['academy_course_audience'][0] ?? '',
			'course_materials' => $meta['academy_course_materials_included'][0] ?? '',
			'is_enabled_course_qa' => $meta['academy_is_enabled_course_qa'][0] ?? '',
			'is_enabled_course_announcements' => $meta['academy_is_enabled_course_announcements'][0] ?? '',
			'course_duration' => $meta['academy_course_duration'][0] ?? array(),
			'course_intro_video' => $meta['academy_course_intro_video'][0] ?? array(),
			'course_certificate_id' => $meta['academy_course_certificate_id'][0] ?? 0,
			'is_disabled_course_review' => $meta['academy_is_disabled_course_review'][0] ?? false,
			'rcp_membership_levels' => $meta['academy_rcp_membership_levels'][0] ?? array(),
			'course_enable_certificate' => $meta['academy_course_enable_certificate'][0] ?? false,
		];
	}

	public function get_all_courses( $status ) {
		global $wpdb;
		// phpcs::ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT *
			FROM {$wpdb->posts}
			WHERE post_type = %s
			AND post_status = %s",
			'academy_courses',
			'publish',
		) );
		return $results;
	}
	public function topics_make_for_csv( $topic ) {
		switch ( $topic['type'] ) {
			case 'lesson':
				return $this->get_lesson_by_topic( $topic ); // phpcs::ignore Squiz.PHP.NonExecutableCode.Unreachable
			case 'quiz':
				return apply_filters( 'academy_pro/export-import/get_quiz_data', $topic ); // phpcs::ignore Squiz.PHP.NonExecutableCode.Unreachable
			case 'assignment':
				return apply_filters( 'academy_pro/export-import/get_assignment_data', $topic ); // phpcs::ignore Squiz.PHP.NonExecutableCode.Unreachable
		}
	}

	public function get_lesson_by_topic( $topic ) {
		$lesson = \Academy\Helper::get_lesson( $topic['id'] );
		if ( ! empty( $lesson ) ) {
			$meta = \Academy\Helper::get_lesson_meta_data( $topic['id'] );
			$author = get_userdata( $lesson->lesson_author );
			$csv_data = [
				'lesson_title'              => $lesson->lesson_title,
				'lesson_content'            => $lesson->lesson_content,
				'lesson_status'             => $lesson->lesson_status,
				'lesson_author'             => $author->user_login,
				'is_previewable'            => $meta['is_previewable'],
				'video_duration'            => wp_json_encode( $meta['video_duration'] ),
				'video_source_type'         => $meta['video_source']['type'],
				'video_source_url'          => $meta['video_source']['url'],
			];
			return $csv_data;
		}
	}

	/**
	 * Method Overwrite
	 *
	 * Overwrite export base class method
	 *
	 * @param array          $array
	 * @param resource|false $fp
	 * @return void
	 */
	public function write_nested_csv( $array, $fp ) {
		$previousItem = array();
		foreach ( $array as $row ) {
			$flattenRow = $this->flatten_array( $row );
			if ( isset( $flattenRow['post_title'] )
			|| isset( $flattenRow['course_expire_enrollment'] )
			|| isset( $flattenRow['lesson_title'] )
			|| isset( $flattenRow['quiz_title'] )
			|| isset( $flattenRow['question_title'] )
			|| isset( $flattenRow['assignment_title'] )
			|| ( isset( $flattenRow['answer_title'] ) && ! isset( $previousItem['answer_title'] ) ) ) {
				$row_header = array_keys( $flattenRow );
				fputcsv( $fp, $row_header );
			}
			fputcsv( $fp, $flattenRow );
			$previousItem = $row;
		}
	}
}
