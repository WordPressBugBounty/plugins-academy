<?php
namespace AcademyCertificates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Admin\Settings;
use Academy\Helper;

class Installer {

	public $academy_certificate_version;
	public static function init() {
		$self = new self();
		$self->academy_certificate_version = get_option( 'academy_certificate_version' );
		if ( ! $self->academy_certificate_version ) {
			$self->insert_default_certificate();
		}

		$self->save_option();
	}

	public function save_option() {
		if ( ! $this->academy_certificate_version ) {
			add_option( 'academy_certificate_version', ACADEMY_CERTIFICATE_VERSION );
		}
	}


	private function necessary_certificates() {
		$default_certificates = array(
			array(
				'title' => esc_html__( 'Certificate 1', 'academy' ),
				'file' => 'certificates/dummy-certificate/certificate-1.php',
			),
			array(
				'title' => esc_html__( 'Certificate 2', 'academy' ),
				'file' => 'certificates/dummy-certificate/certificate-2.php',
			),
			array(
				'title' => esc_html__( 'Certificate 3', 'academy' ),
				'file' => 'certificates/dummy-certificate/certificate-3.php',
			),
			array(
				'title' => esc_html__( 'Certificate 4', 'academy' ),
				'file' => 'certificates/dummy-certificate/certificate-4.php',
			),
			array(
				'title' => esc_html__( 'Certificate 5', 'academy' ),
				'file' => 'certificates/dummy-certificate/certificate-5.php',
			),
			array(
				'title' => esc_html__( 'Certificate 6', 'academy' ),
				'file' => 'certificates/dummy-certificate/certificate-6.php',
			),
			array(
				'title' => esc_html__( 'Certificate 7', 'academy' ),
				'file' => 'certificates/dummy-certificate/certificate-7.php',
			),
		);

		return $default_certificates;
	}

	public function insert_default_certificate() {
		$post_type = 'academy_certificate';

		$certificates = self::necessary_certificates();

		foreach ( $certificates as $certificate ) {
			$title = $certificate['title'];
			$file_path = ACADEMY_ADDONS_DIR_PATH . $certificate['file'];

			if ( file_exists( $file_path ) ) {
				ob_start();
				require_once $file_path;
				$post_content = ob_get_clean();
			}

				$have_certificate = \Academy\Helper::get_page_by_title( $title, $post_type );
			if ( $have_certificate ) {
				// check page status
				if ( 'publish' !== $have_certificate->post_status ) {
					$have_certificate->post_status = 'publish';
					wp_update_post( $have_certificate );
				}
			} else {
				$new_post = array(
					'post_title'   => $title,
					'post_content' => $post_content,
					'post_status'  => 'publish',
					'post_type'    => $post_type,
				);
				wp_insert_post( $new_post );
			}
		}//end foreach

	}

}
