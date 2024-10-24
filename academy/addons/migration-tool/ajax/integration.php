<?php
namespace  AcademyMigrationTool\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Classes\Sanitizer;
use Academy\Classes\AbstractAjaxHandler;
use AcademyMigrationTool\Classes\Learnpress;
use AcademyMigrationTool\Classes\Tutor;
use AcademyMigrationTool\Classes\Learndash;
use AcademyMigrationTool\Classes\Masterstudy;
use AcademyMigrationTool\Classes\Lifterlms;

class Integration extends AbstractAjaxHandler {
	protected $namespace = ACADEMY_PLUGIN_SLUG . '_migration_tool';
	public function __construct() {
		$this->actions = array(
			'prepare_other_lms_to_alms_migration' => array(
				'callback' => array( $this, 'prepare_other_lms_to_alms_migration' ),
			),
			'learnpress_to_academy_migration' => array(
				'callback' => array( $this, 'learnpress_to_academy_migration' ),
			),
			'tutor_to_academy_migration' => array(
				'callback' => array( $this, 'tutor_to_academy_migration' ),
			),
			'learndash_to_academy_migration' => array(
				'callback' => array( $this, 'learndash_to_academy_migration' ),
			),
			'masterstudy_to_academy_migration' => array(
				'callback' => array( $this, 'masterstudy_to_academy_migration' ),
			),
			'lifter_to_academy_migration' => array(
				'callback' => array( $this, 'lifter_to_academy_migration' ),
			),
		);
	}

	public function prepare_other_lms_to_alms_migration( $payload_data ) {
		global $wpdb;
		$payload = Sanitizer::sanitize_payload([
			'pluginName' => 'string',
		], $payload_data );

		$pluginName = $payload['pluginName'];
		if ( empty( $pluginName ) ) {
			wp_send_json_error( __( 'Sorry, you haven\'t select any plugin to migrate.', 'academy' ) );
		}

		$pluginBaseName = '';
		$course_post_type = '';
		switch ( $pluginName ) {
			case 'learnpress':
				$pluginBaseName = 'learnpress/learnpress.php';
				$course_post_type = 'lp_course';
				break;
			case 'tutor':
				$pluginBaseName = 'tutor/tutor.php';
				$course_post_type = 'courses';
				break;
			case 'learndash':
				$pluginBaseName = 'sfwd-lms/sfwd_lms.php';
				$course_post_type = 'sfwd-courses';
				break;
			case 'masterstudy':
				$pluginBaseName = 'masterstudy-lms-learning-management-system/masterstudy-lms-learning-management-system.php';
				$course_post_type = 'stm-courses';
				break;
			case 'lifter':
				$pluginBaseName = 'lifterlms/lifterlms.php';
				$course_post_type = 'course';
				break;
		}//end switch

		if ( ! \Academy\Helper::is_plugin_active( $pluginBaseName ) ) {
			wp_send_json_error( sprintf( __( 'You need to Activated %s plugin to run this migration.', 'academy' ), $pluginName ) );
		}

		if ( ! \Academy\Helper::is_active_woocommerce() ) {
			wp_send_json_error( sprintf( __( 'You need to Activated WooCommerce to run this migration.', 'academy' ), $pluginName ) );
		}

		$courses = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish';", $course_post_type ) );

		if ( ! count( $courses ) ) {
			wp_send_json_error( __( 'Sorry, You have no courses to migrate.', 'academy' ) );
		}

		wp_send_json_success( $courses );
	}

	public function learnpress_to_academy_migration( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'course_id' => 'integer',
		], $payload_data );

		$course_id = $payload['course_id'];
		$LpToAlmsMigration = new Learnpress( $course_id );
		$LpToAlmsMigration->run_migration();
		$response = $LpToAlmsMigration->get_logs();
		wp_send_json_success( $response );
	}

	public function tutor_to_academy_migration( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'course_id' => 'integer',
		], $payload_data );

		$course_id = $payload['course_id'];
		$TrToAlmsMigration = new Tutor( $course_id );
		$TrToAlmsMigration->run_migration();
		$response = $TrToAlmsMigration->get_logs();
		wp_send_json_success( $response );
	}

	public function learndash_to_academy_migration( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'course_id' => 'integer',
		], $payload_data );

		$course_id = $payload['course_id'];
		$LDToAlmsMigration = new Learndash( $course_id );
		$LDToAlmsMigration->run_migration();
		$response = $LDToAlmsMigration->get_logs();
		wp_send_json_success( $response );
	}
	public function masterstudy_to_academy_migration( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'course_id' => 'integer',
		], $payload_data );

		$course_id = $payload['course_id'];
		$MasterstudyToAlmsMigration = new Masterstudy( $course_id );
		$MasterstudyToAlmsMigration->run_migration();
		$response = $MasterstudyToAlmsMigration->get_logs();
		wp_send_json_success( $response );
	}

	public function lifter_to_academy_migration( $payload_data ) {
		$payload = Sanitizer::sanitize_payload([
			'course_id' => 'integer',
		], $payload_data );

		$course_id = $payload['course_id'];
		$LifterToAlmsMigration = new Lifterlms( $course_id );
		$LifterToAlmsMigration->run_migration();
		$response = $LifterToAlmsMigration->get_logs();
		wp_send_json_success( $response );
	}

}
