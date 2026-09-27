<?php
namespace Academy;

use Academy\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Migration {

	public static function init() {
		$self = new self();
		add_action( 'admin_init', [ $self, 'run_migration' ] );
	}

	public function run_migration() {
		$academy_version = get_option( 'academy_version' );

		// Migration for addon WooCommerce
		if ( version_compare( $academy_version, '2.0.7', '<=' ) && Helper::is_active_woocommerce() ) {
			$saved_addons = (array) json_decode( get_option( ACADEMY_ADDONS_SETTINGS_NAME ), true );
			$saved_addons['woocommerce'] = true;
			update_option( ACADEMY_ADDONS_SETTINGS_NAME, wp_json_encode( $saved_addons ) );
		}

		// Version-specific migrations
		$this->migrate_1_3_5( $academy_version );
		$this->migrate_1_4_0( $academy_version );
		$this->migrate_1_8_2( $academy_version );
		$this->migrate_1_9_0( $academy_version );
		$this->migrate_1_9_14( $academy_version );
		$this->migrate_2_3_1( $academy_version );

		// Quiz max question allowed
		if ( ! get_option( 'academy_quiz_question_max_allowed' ) ) {
			$this->migrate_3_3_2();
		}

		// Resume Mode quizzes can no longer use Random question order (no way
		// to keep a resumed attempt on the same sequence it started with) —
		// force any quiz already saved with that combination to Ascending.
		if ( ! Options::get( Options::MIGRATIONS, 'quiz_resume_random_order' ) ) {
			$this->migrate_lock_resume_random_order();
			Options::set( Options::MIGRATIONS, 'quiz_resume_random_order', true );
		}

		// Woo settings migration
		$this->migrate_woo_settings_3_3_6();

		// Add-ons that start switched on.
		$this->enable_default_addons();

		// Save version number, flash role, and permalinks
		if ( ACADEMY_VERSION !== $academy_version ) {
			Settings::save_settings();

			// Flag the one-time About ("What's New") redirect — but only on a
			// real update, not a fresh install ($academy_version is empty then).
			// Consumed once by Admin\Menu::maybe_redirect_about(). Version is the
			// single source of truth: bump ACADEMY_VERSION and this fires once.
			if ( ! empty( $academy_version ) ) {
				Options::set( Options::MIGRATIONS, 'show_whats_new', ACADEMY_VERSION );
			}

			update_option( 'academy_version', ACADEMY_VERSION );
			update_option( 'academy_flash_role_management', true );
			\Academy\Helper::flush_rewrite_rules();
			$this->loco_translate_sync();
		}

		// Flash Role
		if ( get_option( 'academy_flash_role_management' ) ) {
			$installer = new \Academy\Installer();
			$installer->add_role();
			delete_option( 'academy_flash_role_management' );
		}

		// Lesson Gutenberg editor support for instructor
		if ( version_compare( $academy_version, '3.2.2', '>' ) ) {
			$role = get_role( 'manage_academy_instructor' );
			if ( $role ) {
				$role->add_cap( 'edit_academy_lessons' );
				$role->add_cap( 'edit_others_academy_lessons' );
			}
		}
		// Quiz table columns/keys (negative score, image, explanation, title
		// type, audio, AI feedback, attempt/question key) are kept in sync by
		// the Quizzes addon itself — see \AcademyQuizzes\Database::sync_schema().

		// create attachment downloads table
		if ( version_compare( $academy_version, '4.0.0', '<' ) ) {
			$this->migrate_create_attachment_downloads_table();
		}

		if ( version_compare( $academy_version, '3.9.0', '<=' ) && ! Options::get( Options::MIGRATIONS, 'admin_caps_390' ) ) {
			$this->grant_admin_academy_caps();
			Options::set( Options::MIGRATIONS, 'admin_caps_390', 'yes' );
		}

		// Give existing sites' untouched bundled default certificates the new
		// builder's tree, matching what a fresh install already ships with.
		if ( \Academy\Helper::get_addon_active_status( 'certificates' ) && ! Options::get( Options::MIGRATIONS, 'certificate_defaults_v2' ) ) {
			$this->migrate_certificate_default_designs();
			Options::set( Options::MIGRATIONS, 'certificate_defaults_v2', true );
		}
	}

	public function grant_admin_academy_caps() {
		$admin      = get_role( 'administrator' );
		$instructor = get_role( 'academy_instructor' );

		if ( ! $admin || ! $instructor ) {
			return;
		}

		// Add instructor capabilities to administrator if missing.
		foreach ( $instructor->capabilities as $cap => $granted ) {
			if ( ! $admin->has_cap( $cap ) ) {
				$admin->add_cap( $cap, $granted );
			}
		}

		// Remove academy_instructor role from administrators.
		$users = get_users(
			[
				'role'   => 'administrator',
				'fields' => [ 'ID' ],
			]
		);

		foreach ( $users as $user ) {
			$user = new \WP_User( $user->ID );

			if ( in_array( 'academy_instructor', $user->roles, true ) ) {
				$user->remove_role( 'academy_instructor' );
			}
		}
	}

	public function loco_translate_sync(): void {
		if ( ! is_plugin_active( 'loco-translate/loco.php' ) ) {
			return;
		}

		if ( ! wp_next_scheduled( 'academy_loco_translate_sync' ) ) {
			wp_schedule_single_event(
				time() + 5,
				'academy_loco_translate_sync'
			);
		}
	}

	public function migrate_1_3_5( $academy_version ) {
		if ( version_compare( $academy_version, '1.2.15', '>=' ) && version_compare( $academy_version, '1.3.5', '<' ) ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->prefix}academy_lessons SET lesson_status=%s WHERE lesson_status=%s",
					'publish',
					'draft'
				)
			);
		}
	}

	public function migrate_1_4_0( $academy_version ) {
		$user_id = get_current_user_id();
		if ( get_user_meta( $user_id, 'academy_is_user_migrate_completed_topics', true ) ) {
			return;
		}

		global $wpdb;

		$enrolled_course_ids = \Academy\Helper::get_enrolled_courses_ids_by_user( $user_id );
		if ( empty( $enrolled_course_ids ) ) {
			update_user_meta( $user_id, 'academy_is_user_migrate_completed_topics', true );
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$topic_lists = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_key, meta_value FROM $wpdb->usermeta WHERE meta_key LIKE %s AND user_id = %d",
				'academy_completed_topic_id_%',
				$user_id
			)
		);

		$quiz   = [];
		$lesson = [];

		foreach ( $topic_lists as $topic_item ) {
			$topic_id = (int) str_replace( 'academy_completed_topic_id_', '', $topic_item->meta_key );
			if ( 'academy_quiz' === get_post_type( $topic_id ) ) {
				$quiz[ $topic_id ] = $topic_item->meta_value;
			} else {
				$lesson[ $topic_id ] = $topic_item->meta_value;
			}
		}

		if ( ! count( $quiz ) && ! count( $lesson ) ) {
			update_user_meta( $user_id, 'academy_is_user_migrate_completed_topics', true );
			return;
		}

		foreach ( $enrolled_course_ids as $enrolled_course_id ) {
			$curriculums = wp_list_pluck(
				get_post_meta( $enrolled_course_id, 'academy_course_curriculum', true ),
				'topics'
			);
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
		}//end foreach

		update_user_meta( $user_id, 'academy_is_user_migrate_completed_topics', true );
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

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$existing_lessons = $wpdb->get_results( "SELECT ID, lesson_title FROM {$wpdb->prefix}academy_lessons WHERE lesson_name IS NULL OR lesson_name = ''" );
			if ( count( $existing_lessons ) ) {
				foreach ( $existing_lessons as $lesson ) {
					$slug = Helper::generate_unique_lesson_slug( sanitize_title( $lesson->lesson_title ) );
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->update( "{$wpdb->prefix}academy_lessons", array( 'lesson_name' => $slug ), array( 'ID' => $lesson->ID ) );
				}
			}
			add_option( 'academy_is_migrate_lessons_slug', true );
			return;
		}
	}

	/**
	 * Switches on the add-ons that are on by default (Notes), once per site:
	 * on a fresh install, and on an existing site that never set them either
	 * way. One someone already turned off stays off, and turning it off later
	 * sticks, since this never runs again.
	 */
	public function enable_default_addons() {
		if ( Options::get( Options::MIGRATIONS, 'default_addons' ) ) {
			return;
		}

		$default_on   = array( 'notes' );
		$saved_addons = (array) json_decode( get_option( ACADEMY_ADDONS_SETTINGS_NAME, '{}' ), true );
		$enabled      = array();

		foreach ( $default_on as $addon_slug ) {
			if ( ! array_key_exists( $addon_slug, $saved_addons ) ) {
				$saved_addons[ $addon_slug ] = true;
				$enabled[]                   = $addon_slug;
			}
		}

		if ( $enabled ) {
			update_option( ACADEMY_ADDONS_SETTINGS_NAME, wp_json_encode( $saved_addons ) );
			// Later code in this request reads the add-ons from here.
			$GLOBALS['academy_addons'] = json_decode( wp_json_encode( $saved_addons ) );
			// Same as switching it on from the Add-ons screen: Notes creates
			// its table and moves legacy notes over on this hook.
			foreach ( $enabled as $addon_slug ) {
				do_action( "academy/addons/activated_{$addon_slug}", true );
			}
		}

		Options::set( Options::MIGRATIONS, 'default_addons', true );
	}

	public function migrate_2_3_1( $academy_version ) {
		if ( version_compare( $academy_version, '2.3.0', '<' ) ) {
			// Enable WooCommerce Addon
			$saved_addons = (array) json_decode( get_option( ACADEMY_ADDONS_SETTINGS_NAME ), true );
			$saved_addons['course-preview'] = true;
			update_option( ACADEMY_ADDONS_SETTINGS_NAME, wp_json_encode( $saved_addons ) );
		}
	}

	public function migrate_woo_settings_3_3_6() {
		// check WooCommerce engine
		if ( ! get_option( 'academy_store_dashboard_inside_link_migrate' ) && 'woocommerce' === \Academy\Helper::get_settings( 'monetization_engine' ) ) {
			$woo_label = \Academy\Helper::get_settings( 'woo_dashboard_fd_link_label' );
			$woo_label_status = \Academy\Helper::get_settings( 'is_enabled_fd_link_inside_woo_dashboard' );
			$GLOBALS['academy_settings']->store_link_label_inside_frontend_dashboard = $woo_label;
			$GLOBALS['academy_settings']->store_link_inside_frontend_dashboard = $woo_label_status;
			update_option( ACADEMY_SETTINGS_NAME, wp_json_encode( $GLOBALS['academy_settings'] ) );
			add_option( 'academy_store_dashboard_inside_link_migrate', true );
		}
	}

	public function migrate_3_3_2() {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one-time migration
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				SET pm.meta_value = %d
				WHERE p.post_type = %s
				AND pm.meta_key = %s
				AND CAST(pm.meta_value AS UNSIGNED) > %d",
				0,
				'academy_quiz',
				'academy_quiz_max_attempts_allowed',
				0
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		add_option( 'academy_quiz_question_max_allowed', true );
	}

	public function migrate_lock_resume_random_order() {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one-time migration
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				INNER JOIN {$wpdb->postmeta} pm2 ON pm2.post_id = pm.post_id
					AND pm2.meta_key = %s
					AND pm2.meta_value = %s
				SET pm.meta_value = %s
				WHERE p.post_type = %s
				AND pm.meta_key = %s
				AND pm.meta_value = %s",
				'academy_quiz_feedback_mode',
				'resume',
				'ASC',
				'academy_quiz',
				'academy_quiz_questions_order',
				'rand'
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Give existing sites' bundled default certificates ("Certificate 1"–"Certificate 7")
	 * the new tree-builder's polished design, matching what
	 * `AcademyCertificates\Installer::insert_default_certificate()` already gives a
	 * fresh install. Only touches a default that is both untouched (its
	 * post_content still matches the shipped template) and not already on the new
	 * builder (no `_academy_certificate_tree` meta yet) — anything customized, or
	 * already re-seeded/edited, is left alone and keeps using the existing
	 * on-demand `AcademyCertificates\LegacyMigrator` fallback. Never touches
	 * post_content or `_academy_certificate_html`, so it has no effect on
	 * already-issued PDFs; it only changes what the builder shows on next open.
	 */
	public function migrate_certificate_default_designs() {
		if ( ! class_exists( '\AcademyCertificates\Helper' ) ) {
			return;
		}

		$certificates = \AcademyCertificates\Helper::necessary_certificates();

		foreach ( $certificates as $index => $certificate ) {
			$title = $certificate['title'] ?? '';
			if ( '' === $title ) {
				continue;
			}

			$post = \Academy\Helper::get_page_by_slug( sanitize_title( $title ), 'academy_certificate' );
			if ( ! $post instanceof \WP_Post ) {
				$post = \Academy\Helper::get_page_by_title( $title, 'academy_certificate' );
			}
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			// Already on the new builder (fresh install, already reset, or already
			// edited) — leave it alone.
			if ( get_post_meta( $post->ID, '_academy_certificate_tree', true ) ) {
				continue;
			}

			// Customized via the old editor — leave it for the generic lazy
			// migrator rather than overwriting a real design with the shipped one.
			$pristine = \AcademyCertificates\Helper::get_default_certificate_content( $certificate['file'] ?? '' );
			if ( trim( (string) $post->post_content ) !== trim( (string) $pristine ) ) {
				continue;
			}

			update_post_meta(
				$post->ID,
				'_academy_certificate_tree',
				wp_slash( wp_json_encode( \AcademyCertificates\Helper::default_certificate_tree( \AcademyCertificates\Helper::default_certificate_image( $index + 1 ) ) ) )
			);
		}//end foreach
	}

	public function migrate_create_attachment_downloads_table() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		\Academy\Database\CreateAttachmentDownloadsTable::up( $wpdb->prefix, $wpdb->get_charset_collate() );
	}
}
