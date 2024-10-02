<?php

namespace AcademyEasyDigitalDownloads;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

class Hooks {
	public static function init() {
		$self = new self();
		add_filter( 'academy/course/change_enrollment_status', array( $self, 'change_enrollment_status' ) );
		add_filter( 'academy/template/loop/price_args', array( $self, 'loop_price_args' ), 10, 2 );
	}

	public function change_enrollment_status( $enrollment_status ): string {
		if ( 'edd' === \Academy\Helper::get_settings( 'monetization_engine' ) ) {
			$enrollment_status = 'completed';
		}
		return $enrollment_status;
	}

	public function loop_price_args( $args, $course_id ) {
		$download_id = get_post_meta( $course_id, 'academy_course_download_id', true );
		if ( $download_id ) {
			$download = new \EDD_Download( $download_id );
			if ( $download->ID ) {
				$args['price'] = $download->get_price() . ' ' . edd_get_currency();
			}
		}

		return $args;
	}
}
