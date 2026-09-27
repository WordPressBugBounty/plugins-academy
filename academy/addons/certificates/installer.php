<?php
namespace AcademyCertificates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyCertificates\Helper;

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

	public function insert_default_certificate() {
		$post_type = 'academy_certificate';

		$certificates = Helper::necessary_certificates();

		foreach ( $certificates as $index => $certificate ) {
			$title = $certificate['title'];
			// Shared with the reset tool, and deliberately not `require_once`:
			// see Helper::get_default_certificate_content(). Reading it per
			// iteration also stops a missing template silently reusing the
			// previous certificate's markup.
			$post_content = Helper::get_default_certificate_content( $certificate['file'] );

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
				$new_id = wp_insert_post( $new_post );
				// Ships with BOTH representations: the classic markup prints the
				// PDF until someone saves it, and the tree is what the block
				// builder opens — without it the editor shows a blank page.
				if ( $new_id && ! is_wp_error( $new_id ) ) {
					update_post_meta(
						$new_id,
						'_academy_certificate_tree',
						wp_slash( wp_json_encode( Helper::default_certificate_tree( Helper::default_certificate_image( $index + 1 ) ) ) )
					);
				}
			}//end if
		}//end foreach
	}
}
