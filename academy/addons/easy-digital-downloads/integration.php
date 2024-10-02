<?php

namespace AcademyEasyDigitalDownloads;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Academy\Helper;
use Academy\Traits\Earning;
use EDD_Payment;

class Integration {
	use Earning;
	public static function init() {

		$self        = new self();
		$monetize_by = Helper::get_settings( 'monetization_engine' );
		if ( 'edd' !== $monetize_by ) {
			return;
		}
		add_action( 'rest_after_insert_academy_courses', array( $self, 'save_course_meta' ), 10, 1 );

		add_action( 'edd_update_payment_status', array( $self, 'edd_update_payment_status' ), 10, 3 );

		if ( \Academy\Helper::get_addon_active_status( 'multi_instructor' ) ) {
			add_action( 'edd_complete_purchase', array( $self, 'save_earning_data' ), 10, 3 );
		}
	}
	public function save_course_meta( $post ) {
		$download_id = (int) get_post_meta( $post->ID, 'academy_course_download_id', true );
		if ( $download_id ) {
			update_post_meta( $post, 'academy_course_download_id', $download_id );
		}
	}

	public function edd_update_payment_status( int $payment_id, string $new_status, $old_status ) {

		if ( 'complete' !== $new_status ) {
			return;
		}

		$payment      = new EDD_Payment( $payment_id );
		$cart_details = $payment->cart_details;
		$user_id      = $payment->user_info['id'];

		if ( is_array( $cart_details ) ) {
			foreach ( $cart_details as $cart_index => $download ) {
				$if_has_course = Helper::download_belongs_to_course( $download['id'] );
				if ( $if_has_course ) {
					$course_id = $if_has_course->post_id;
					Helper::do_enroll( $course_id, $user_id, $payment_id );
				}
			}
		}
	}

	public function save_earning_data( $order_id, $payment, $customer ) {
		$is_enabled_earning = (bool) \Academy\Helper::get_settings( 'is_enabled_earning' );
		if ( ! \Academy\Helper::get_addon_active_status( 'multi_instructor' ) || ! $is_enabled_earning ) {
			return;
		}

		if ( empty( $payment->cart_details ) ) {
			return;
		}

		$download_details = current( $payment->cart_details );
		$download_id = $download_details['id'];
		$course = \Academy\Helper::download_belongs_with_course( $download_id );

		if ( $course ) {
			\Academy\Helper::save_instructor_earnings( $course, $download_details, $download_id );
		}//end if
	}
}
