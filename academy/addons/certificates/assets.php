<?php
namespace AcademyCertificates;

use Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Assets {
	public static function init() {
		$self = new self();
		add_filter( 'ablocks/assets/editor_scripts_data', array( $self, 'add_academy_certificate_default_image' ) );
	}

	public function add_academy_certificate_default_image( $script_data ) {
		$certificate_image_array = array(
			'landscape' => array(
				'cert_1' => ACADEMY_ASSETS_URI . 'images/certificate/certificate-1.png',
				'cert_2' => ACADEMY_ASSETS_URI . 'images/certificate/certificate-2.png',
				'cert_3' => ACADEMY_ASSETS_URI . 'images/certificate/certificate-3.png',
				'cert_4' => ACADEMY_ASSETS_URI . 'images/certificate/certificate-4.png',
				'cert_5' => ACADEMY_ASSETS_URI . 'images/certificate/certificate-5.png',
				'cert_6' => ACADEMY_ASSETS_URI . 'images/certificate/certificate-6.png',
				'cert_7' => ACADEMY_ASSETS_URI . 'images/certificate/certificate-7.png',
				'custom_image' => ACADEMY_ASSETS_URI . 'images/certificate/place-holder.png',
			),
			'protrait' => array(
				'cert_1' => ACADEMY_ASSETS_URI . 'images/certificate/protrait-1.png',
				'cert_2' => ACADEMY_ASSETS_URI . 'images/certificate/protrait-2.png',
				'cert_3' => ACADEMY_ASSETS_URI . 'images/certificate/protrait-3.png',
				'cert_4' => ACADEMY_ASSETS_URI . 'images/certificate/protrait-4.png',
				'cert_5' => ACADEMY_ASSETS_URI . 'images/certificate/protrait-5-5.png',
				'cert_6' => ACADEMY_ASSETS_URI . 'images/certificate/protrait-6-6.png',
				'cert_7' => ACADEMY_ASSETS_URI . 'images/certificate/protrait-7-7.png',
				'custom_image' => ACADEMY_ASSETS_URI . 'images/certificate/place-holder.png',
			),
		);
		if ( ! isset( $script_data['certificate_image'] ) ) {
			$script_data['certificate_image'] = $certificate_image_array;
		}
		return $script_data;
	}
}
