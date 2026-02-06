<?php
namespace AcademyCertificates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use Academy\Helper;
use AcademyCertificates\Helper as CertificateHelper;

class Frontend {
	public static function init() {
		$self = new self();
		add_action( 'academy/templates/single_course/enroll_complete_form', [ $self, 'download_certificate_link' ] );
		add_filter( 'template_include', array( $self, 'download_certificate' ), 40 );
		add_action( 'template_include', array( $self, 'preview_certificate' ) );
	}

	public function download_certificate_link( $is_complete ) {
		$post_id = get_the_id();
		$certificate_id = get_post_meta( $post_id, 'academy_course_certificate_id', true );
		if ( ! $certificate_id ) {
			$certificate_id = \Academy\Helper::get_settings( 'academy_primary_certificate_id' );
		}

		$is_enable_certificate = get_post_meta( $post_id, 'academy_course_enable_certificate', true );
		if ( ! $is_complete || ! $is_enable_certificate || ! $certificate_id ) {
			return;
		}

		?>
		<div class="academy-widget-enroll__continue">
			<a class="academy-btn academy-btn--bg-light-purple" href="<?php echo esc_url( add_query_arg( array( 'source' => 'certificate' ), get_the_permalink() ) ); ?>"><?php esc_html_e( 'Download Certificate', 'academy' ); ?></a>
		</div>
		<?php
	}
	public function download_certificate( $template ) {
		if ( get_query_var( 'post_type' ) === 'academy_courses' && get_query_var( 'source' ) === 'certificate' ) {
			add_filter( 'ablocks/is_allow_block_inline_assets', '__return_true' );
			$course_id = get_the_ID();
			$verification_id = isset( $_GET['verify'] ) ? sanitize_text_field( wp_unslash( $_GET['verify'] ) ) : '';
			$certificate_id = get_post_meta( $course_id, 'academy_course_certificate_id', true );
			if ( ! $certificate_id ) {
				$certificate_id = Helper::get_settings( 'academy_primary_certificate_id' );
			}
			$certificate_template_id = apply_filters( 'academy_certificates/certificate_template_id', $certificate_id, $course_id );
			$student_id = apply_filters( 'academy_certificates/certificate_student_id', get_current_user_id() );
			CertificateHelper::render_certificate( $course_id, $certificate_template_id, $student_id, $verification_id );
		}
		return $template;
	}
	public function preview_certificate( $template ) {
		if ( is_singular( 'academy_certificate' ) && ! is_admin() ) {
			add_filter( 'ablocks/is_allow_block_inline_assets', '__return_true' );

			$certificate_preview_id = get_the_id();
			$course_id = Helper::get_last_course_id();

			if ( ! $course_id ) {
				wp_die( esc_html_e( 'Sorry, you have no course', 'academy' ) );
				exit;
			}

			CertificateHelper::render_certificate( $course_id, $certificate_preview_id, get_current_user_id() );

		}//end if

		return $template;
	}
}
