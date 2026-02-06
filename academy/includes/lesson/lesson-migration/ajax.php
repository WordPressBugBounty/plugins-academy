<?php
namespace Academy\Lesson\LessonMigration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Exception;
use Academy\Classes\Sanitizer;
use Academy\Classes\AbstractAjaxHandler;
use Academy\Lesson\LessonApi\Lesson as LessonApi;
class Ajax extends AbstractAjaxHandler {
	public function __construct() {
		$this->actions = array(
			'lesson/do_migration' => [
				'callback' => [ $this, 'migrate' ],
				'capability'    => 'manage_options'
			],
		);
	}


	public function migrate() : void {
		header( 'Content-Type: text/event-stream' );
		header( 'Cache-Control: no-cache' );
		header( 'Connection: keep-alive' );
		$flow = sanitize_title( strtolower( $_GET['lesson_migrator_flow'] ?? '' ) );
		if ( ! in_array(
			$flow,
			[
				'lesson-to-post',
				'post-to-lesson'
			]
		)
		) {
			echo 'data: ' . wp_json_encode( [
				'type' => 'error',
				'msg' => __( 'Migration flow can be either  lesson-to-post or post-to-lesson.', 'academy' )
			] ) . "\n\n";
			wp_die();
		}

		$GLOBALS['academy_settings']->lesson_migrator_flow = $flow;
		$GLOBALS['academy_settings']->lesson_migrator_id   = wp_generate_uuid4();
		update_option( ACADEMY_SETTINGS_NAME, wp_json_encode( $GLOBALS['academy_settings'] ) );

		while ( true ) {
			try {
				$ins = new Migrator();
				$count = $ins->migrate();
				$stats = $ins->stats();
				echo 'data: ' . wp_json_encode( [
					'type' => 'migrating',
					'data' => $stats,
					$GLOBALS['academy_settings']->lesson_migrator_id
				] ) . "\n\n";
				if ( 0 === $count ) {
					do {
						$count = ( new CourseLessonUpdater() )->update(
							fn( CourseLessonUpdater $obj ): string =>  'data: ' . wp_json_encode( [
								'type' => 'course_updating',
								'left' => $obj->left(),
								'updated' => $obj->updated(),
							] ) . "\n\n",
							fn( CourseLessonUpdater $obj ): string => '',
						);

					} while ( $count > 0 );

					echo 'data: ' . wp_json_encode( [ 'type' => 'complete' ] ) . "\n\n";
					break;
				}
			} catch ( Exception $e ) {
				echo 'data: ' . wp_json_encode( [
					'type' => 'error',
					'msg' => $e->getMessage()
				] ) . "\n\n";
				break;
			}//end try
		}//end while
		wp_die();
	}
}
