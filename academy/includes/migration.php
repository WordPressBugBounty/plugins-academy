<?php
namespace Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Admin\Settings;

class Migration {

	public static function init() {
		$self = new self();
		$self->run_migration();
	}

	public function run_migration() {
		$academy_version = get_option( 'academy_version' );
		// Migration for addon WooCommerce. when all users come to v2.0.7 then it will be deleted
		if ( version_compare( $academy_version, '2.0.7', '<=' ) && Helper::is_active_woocommerce() ) {
			// Enable WooCommerce Addon
			$saved_addons = (array) json_decode( get_option( ACADEMY_ADDONS_SETTINGS_NAME ), true );
			$saved_addons['woocommerce'] = true;
			update_option( ACADEMY_ADDONS_SETTINGS_NAME, wp_json_encode( $saved_addons ) );
		}

		// Fix Lesson Status Issue - when all users come to v1.3.5 then it will be deleted
		$this->migrate_1_3_5( $academy_version );

		// Fix Course Wishlist Issue - when all users come to v1.4.0 then it will be deleted
		$this->migrate_1_4_0( $academy_version );

		// Course Announcement data to migration global announcement -  when all users come to v1.8.2 then it will be deleted
		$this->migrate_1_8_2( $academy_version );

		// Customizer settings move to main settings
		$this->migrate_1_9_0( $academy_version );

		// Set default value for form customization
		$this->migrate_1_9_14( $academy_version );

		$this->migrate_2_0_0( $academy_version );

		// Save Version Number, flash role management and save permalink
		if ( ACADEMY_VERSION !== $academy_version ) {
			Settings::save_settings();
			update_option( 'academy_version', ACADEMY_VERSION );
			update_option( 'academy_flash_role_management', true );
			update_option( 'academy_required_rewrite_flush', Helper::get_time() );
		}
		// Flash Role
		if ( get_option( 'academy_flash_role_management' ) ) {
			$Installer = new \Academy\Installer();
			$Installer->add_role();
			delete_option( 'academy_flash_role_management' );
		}
		// current user have administrator role and not have instructor role then assign instructor role
		$user = new \WP_User( get_current_user_id() );
		if ( in_array( 'administrator', $user->roles, true ) && ! in_array( 'academy_instructor', $user->roles, true ) ) {
			$user->add_role( 'academy_instructor' );
		}
	}

	public function migrate_1_3_5( $academy_version ) {
		if ( version_compare( $academy_version, '1.2.15', '>=' ) && version_compare( $academy_version, '1.3.5', '<' ) ) {
			global $wpdb;
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}academy_lessons SET lesson_status=%s WHERE lesson_status= %s", 'publish', 'draft' ) );
		}
	}

	public function migrate_1_4_0( $academy_version ) {
		$user_id = get_current_user_id();
		if ( ! get_user_meta( $user_id, 'academy_is_user_migrate_completed_topics', true ) ) {
			global $wpdb;
			// if current user have no enrolled course then return it.
			$enrolled_course_ids = \Academy\Helper::get_enrolled_courses_ids_by_user( $user_id );
			if ( ! count( $enrolled_course_ids ) ) {
				update_user_meta( $user_id, 'academy_is_user_migrate_completed_topics', true );
				return;
			}

			$topic_lists = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT meta_key, meta_value FROM $wpdb->usermeta WHERE meta_key LIKE %s AND user_id = %d",
					'academy_completed_topic_id_%',
					$user_id
				)
			);

			$quiz = [];
			$lesson = [];
			foreach ( $topic_lists as $topic_item ) {
				$topic_id = (int) str_replace( 'academy_completed_topic_id_', '', $topic_item->meta_key );
				if ( 'academy_quiz' === get_post_type( $topic_id ) ) {
					$quiz[ $topic_id ] = $topic_item->meta_value;
				} else {
					$lesson[ $topic_id ] = $topic_item->meta_value;
				}
			}

			// if current user have no completed lesson or quiz then return it
			if ( ! count( $quiz ) && ! count( $lesson ) ) {
				update_user_meta( $user_id, 'academy_is_user_migrate_completed_topics', true );
				return;
			}

			// Saved user meta to get enrolled course id then update user meta.
			foreach ( $enrolled_course_ids as $enrolled_course_id ) {
				$curriculums = wp_list_pluck( get_post_meta( $enrolled_course_id, 'academy_course_curriculum', true ), 'topics' );
				$curriculums = call_user_func_array( 'array_merge', $curriculums );
				$option_name = 'academy_course_' . $enrolled_course_id . '_completed_topics';
				$saved_topics_lists = (array) json_decode( get_user_meta( $user_id, $option_name, true ), true );
				foreach ( $curriculums as $curriculum ) {
					if ( isset( $lesson[ $curriculum['id'] ] ) && 'lesson' === $curriculum['type'] ) {
						if ( ! isset( $saved_topics_lists['lesson'][ $curriculum['id'] ] ) ) {
							$saved_topics_lists['lesson'][ $curriculum['id'] ] = $lesson[ $curriculum['id'] ];
						}
					} elseif ( isset( $quiz[ $curriculum['id'] ] ) && 'quiz' === $curriculum['type'] ) {
						if ( ! isset( $saved_topics_lists['quiz'][ $curriculum['id'] ] ) ) {
							$saved_topics_lists['quiz'][ $curriculum['id'] ] = $quiz[ $curriculum['id'] ];
						}
					}
				}
				update_user_meta( $user_id, $option_name, wp_json_encode( $saved_topics_lists ) );
			}
			update_user_meta( $user_id, 'academy_is_user_migrate_completed_topics', true );
		}//end if
	}

	public function migrate_1_9_14( $academy_version ) {
		if ( ! get_option( 'academy_form_builder_settings' ) ) {
			$form_settings = array(
				'student' => [
					[
						'fields' => [
							[
								'is_required' => true,
								'label' => __( 'Email', 'academy' ),
								'name' => 'email',
								'placeholder' => __( 'Enter Email Address', 'academy' ),
								'type' => 'text'
							],
						],
					],
					[
						'fields' => [
							[
								'is_required' => true,
								'label' => __( 'Password', 'academy' ),
								'name' => 'password',
								'placeholder' => __( 'Enter Password', 'academy' ),
								'type' => 'password'
							],
							[
								'is_required' => true,
								'label' => __( 'Confirm Password', 'academy' ),
								'name' => 'confirm-password',
								'placeholder' => __( 'Enter Confirm Password', 'academy' ),
								'type' => 'password'
							]
						]
					],
					[
						'fields' => [
							[
								'is_required' => true,
								'label' => __( 'Register as Student', 'academy' ),
								'name' => 'button',
								'type' => 'button'
							],
						],
					],
				],
				'instructor' => [
					[
						'fields' => [
							[
								'is_required' => true,
								'label' => __( 'Email', 'academy' ),
								'name' => 'email',
								'placeholder' => __( 'Enter Email Address', 'academy' ),
								'type' => 'text'
							],
						],
					],
					[
						'fields' => [
							[
								'is_required' => true,
								'label' => __( 'Password', 'academy' ),
								'name' => 'password',
								'placeholder' => __( 'Enter Password', 'academy' ),
								'type' => 'password'
							],
							[
								'is_required' => true,
								'label' => __( 'Confirm Password', 'academy' ),
								'name' => 'confirm-password',
								'placeholder' => __( 'Enter Confirm Password', 'academy' ),
								'type' => 'password'
							]
						]
					],
					[
						'fields' => [
							[
								'is_required' => true,
								'label' => __( 'Register as Instructor', 'academy' ),
								'name' => 'button',
								'type' => 'button'
							],
						],
					],
				],
			);
			add_option( 'academy_form_builder_settings', wp_json_encode( $form_settings ) );
		}//end if
	}
	public function migrate_1_8_2( $academy_version ) {
		if ( version_compare( $academy_version, '1.8.2', '<' ) ) {
			global $wpdb;

			$course_announcements = $wpdb->get_results($wpdb->prepare(
				"SELECT post_id, meta_value 
				FROM {$wpdb->prefix}postmeta 
				WHERE meta_key = %s",
				'academy_course_announcements'
			));

			if ( is_array( $course_announcements ) ) {
				foreach ( $course_announcements as $course_announcement ) {
					$post_id = (int) $course_announcement->post_id;
					$post_title = get_the_title( $post_id );
					$announcements = maybe_unserialize( $course_announcement->meta_value );
					if ( is_array( $announcements ) && count( $announcements ) ) {
						foreach ( $announcements as $announcement ) {
							if ( empty( $announcement['title'] ) || \Academy\Helper::get_page_by_title( $announcement['title'], 'academy_announcement' ) ) {
								continue;
							}

							$inserted_announcement_id = wp_insert_post(
								array(
									'post_title' => $announcement['title'],
									'post_type' => 'academy_announcement',
									'post_status' => 'publish',
									'post_content' => '<!-- wp:paragraph --><p>' . $announcement['content'] . '</p><!-- /wp:paragraph -->'
								)
							);
							$announcements_course_ids = array(
								array(
									'label' => $post_title,
									'value' => $post_id
								)
							);
							update_post_meta( $inserted_announcement_id, 'academy_announcements_course_ids', $announcements_course_ids );
						}//end foreach
					}//end if
				}//end foreach
			}//end if
		}//end if
	}

	public function migrate_1_9_0( $academy_version ) {
		if ( version_compare( $academy_version, '1.9.0', '<' ) ) {
			$course_archive_filters = \Academy\Helper::get_customizer_settings(
				'archive_course_filters',
				array(
					'items' =>
						array(
							'search'   => 1,
							'category' => 1,
							'tags'     => 1,
							'levels'   => 1,
							'type'     => 1,
						),
				)
			);

			$course_archive_filters = $course_archive_filters['items'];
			$course_archive_filters = array_reduce(array_keys( $course_archive_filters ), function ( $carry, $key ) use ( $course_archive_filters ) {
				$carry[] = [ $key => $course_archive_filters[ $key ] ];
				return $carry;
			}, []);

			$is_enabled_course_wishlist = false;
			if ( (bool) \Academy\Helper::get_customizer_settings( 'course_wishlists_status' ) || \Academy\Helper::get_customizer_settings( 'single_course_wishlists_status' ) ) {
				$is_enabled_course_wishlist = true;
			}

			$is_enabled_course_review = false;
			if (
				(bool) \Academy\Helper::get_customizer_settings( 'course_reviews_status' ) ||
				(bool) \Academy\Helper::get_customizer_settings( 'single_course_student_reviews_status' )
			) {
				$is_enabled_course_review = true;
			}

			$is_enabled_course_share = false;
			if ( (bool) \Academy\Helper::get_customizer_settings( 'single_course_share_status' ) || \Academy\Helper::get_customizer_settings( 'single_course_share_status' ) ) {
				$is_enabled_course_share = true;
			}

			\Academy\Admin\Settings::save_settings( array(
				'course_archive_sidebar_position' => \Academy\Helper::get_customizer_settings( 'archive_course_sidebar' ),
				'archive_course_filters' => $course_archive_filters,
				'course_archive_courses_per_row' => \Academy\Helper::get_customizer_settings( 'course_per_row' ),
				'course_archive_courses_per_page' => \Academy\Helper::get_customizer_settings( 'course_per_page' ),
				'is_enabled_course_share' => $is_enabled_course_share,
				'is_enabled_course_wishlist' => $is_enabled_course_wishlist,
				'is_enabled_course_review' => $is_enabled_course_review,
				'is_enabled_instructor_review' => \Academy\Helper::get_customizer_settings( 'single_course_instructor_reviews_status' ),
				'is_enabled_course_single_enroll_count' => \Academy\Helper::get_customizer_settings( 'single_course_enroll_count_status' ),
				'is_opened_course_single_first_topic' => \Academy\Helper::get_customizer_settings( 'single_course_topics_first_item_open_status' ),
			) );
		}//end if
	}

	public function migrate_2_0_0( $academy_version ) {
		global $wpdb;
		if ( ! get_option( 'academy_is_migrate_lessons_slug' ) ) {
			// Lesson name issue solved
			$existing_lessons = $wpdb->get_results( "SELECT ID, lesson_title FROM {$wpdb->prefix}academy_lessons WHERE lesson_name IS NULL OR lesson_name = ''" );
			if ( count( $existing_lessons ) ) {
				foreach ( $existing_lessons as $lesson ) {
					$slug = Helper::generate_unique_lesson_slug( sanitize_title( $lesson->lesson_title ) );
					$wpdb->update( "{$wpdb->prefix}academy_lessons", array( 'lesson_name' => $slug ), array( 'ID' => $lesson->ID ) );
				}
			}
			add_option( 'academy_is_migrate_lessons_slug', true );
		}
	}
}
