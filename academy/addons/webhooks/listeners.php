<?php
namespace AcademyWebhooks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyWebhooks\Classes\BaseListeners;
use Exception;

class Listeners extends BaseListeners {

	public static function init() {
		$self = new self();
		$self->register_webhooks();
		add_action( 'academy_webhooks/async_delivery', array( $self, 'async_delivery' ), 10, 3 );
	}

	public function async_delivery( $event_name, $webhook, $payload ) {
		try {
			$this->dispatch_webhook( $event_name, $webhook, $payload );
		} catch ( Exception $e ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $e->getMessage();
		}
	}

	public function register_webhooks() {
		$listeners = $this->get_all_listeners();
		$webhooks  = $this->get_all_webhooks();

		foreach ( $webhooks as $webhook ) {
			$webhook_events = $this->get_webhook_events( (int) $webhook->ID );
			if ( $webhook_events ) {
				foreach ( $webhook_events as $event_name ) {
					if ( ! isset( $listeners[ $event_name ] ) ) {
						continue;
					}

					$listener = $listeners[ $event_name ];

					$callback = function( $webhook, $payload ) use ( $event_name ) {
						as_enqueue_async_action(
							'academy_webhooks/async_delivery',
							array(
								'event_name' => $event_name,
								'webhook'    => $webhook,
								'payload'    => $payload,
							),
							'academy-webhooks'
						);
					};

					call_user_func( array( $listener, 'dispatch' ), $callback, $webhook );
				}//end foreach
			}//end if
		}//end foreach
	}

	public function get_all_listeners() {
		$listeners = array_unique(
			apply_filters(
				'academy_webhooks/get_listeners',
				array(
					'course_publish'                => Listeners\CoursePublished::class,
					'lesson_publish'                => Listeners\LessonPublished::class,
					'quiz_publish'                  => Listeners\QuizPublished::class,
					'assignment_publish'            => Listeners\AssignmentPublish::class,
					'announcement_publish'          => Listeners\AnnouncementPublish::class,
					'tutor_booking_publish'         => Listeners\TutorBookingPublish::class,
					'new_student_registration'      => Listeners\NewStudentRegistration::class,
					'new_instructor_registration'   => Listeners\NewInstructorRegistration::class,
					'new_enroll'                    => Listeners\NewEnrollment::class,
					'course_completed'              => Listeners\CourseCompleted::class,
					'lesson_completed'              => Listeners\LessonCompleted::class,
					'quiz_completed'                => Listeners\QuizCompleted::class,
					'tutor_booking_completed'       => Listeners\TutorBookingCompleted::class,
					'assignment_completed'          => Listeners\AssignmentCompleted::class,
					'quiz_attempt_status_pending'   => Listeners\QuizAttemptStatusPending::class,
					'quiz_attempt_status_passed'    => Listeners\QuizAttemptStatusPassed::class,
					'quiz_attempt_status_failed'    => Listeners\QuizAttemptStatusFailed::class,
					'new_course_review'             => Listeners\NewCourseReview::class,
					'tutor_booking_review'          => Listeners\TutorBookingReview::class,
					'new_question_in_course'        => Listeners\InsertCourseQA::class,
					'new_Reply_to_question'         => Listeners\ReplyCourseQA::class,
					'submitted_assignment'          => Listeners\SubmittedAssignment::class,
					'evaluate_submitted_assignment' => Listeners\EvaluateSubmittedAssignment::class,
					'tutor_booking_booked'          => Listeners\TutorBookingBooked::class,
					'zoom_publish'                  => Listeners\ZoomPublish::class,
					'zoom_complete'                => Listeners\ZoomCompleted::class,
					'course-bundle_publish'         => Listeners\CourseBundlePublish::class,
				)
			)
		);

		return $listeners;
	}
}
