<?php
namespace AcademyCertificates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyCertificates\PDF\Generator;
use Academy\Helper;

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
		if ( ! $is_complete || ! $is_enable_certificate ) {
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
			$certificate_template_id = apply_filters( 'academy_certificates/certificate_template_id', Helper::get_settings( 'academy_primary_certificate_id' ), $course_id );
			$student_id = apply_filters( 'academy_certificates/certificate_student_id', get_current_user_id() );
			$this->render_certificate( $course_id, $certificate_template_id, $student_id );
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

			$this->render_certificate( $course_id, $certificate_preview_id, get_current_user_id() );

		}//end if

		return $template;
	}
	public function render_certificate( $course_id, $template_id, $student_id ) {
		$certificate = get_post( $template_id );
		if ( ! $certificate->post_content ) {
			return;
		}
		$user_data = get_userdata( $student_id );
		$fname = $user_data->first_name ?? '';
		$lname = $user_data->last_name ?? '';
		$student_name = $user_data->display_name ?? '';

		// Set student name if first and last names are available
		if ( ! empty( $fname ) && ! empty( $lname ) ) {
			$student_name = $fname . ' ' . $lname;
		}

		// Optional values, set to empty strings if data is unavailable
		$course_title = get_the_title( $course_id );
		$instructors = \Academy\Helper::get_instructors_by_course_id( $course_id );
		$instructor_name = ! empty( $instructors ) ? $instructors[0]->display_name : 'Instructor Missing';
		$course_place = get_bloginfo( 'name' ) ?? 'Course Place Missing';

		$course_completed = \Academy\Helper::is_completed_course( $course_id, $student_id, true );
		$completion_date = $course_completed ? date( 'd F Y', strtotime( $course_completed->completion_date ) ) : 'Completion Date Missing'; // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

		// Replace dynamic placeholders with available values or default messages
		$certificate_template_dynamic_code_args = apply_filters( 'academy_certificates/template_dynamic_codes', [ '{{learner}}', '{{course_title}}', '{{instructor}}', '{{course_place}}', '{{completion_date}}' ] );
		$certificate_template_dynamic_variable_args = apply_filters( 'academy_certificates/template_dynamic_codes_variables', [ $student_name, $course_title, $instructor_name, $course_place, $completion_date ], $student_id, $course_id );
		$certificate_template = str_replace(
			$certificate_template_dynamic_code_args,
			$certificate_template_dynamic_variable_args,
			$certificate->post_content
		);

		$blocks = parse_blocks( $certificate_template );
		if ( ! empty( $blocks ) && 'ablocks/academy-certificate' === $blocks[0]['blockName'] ) {
			$attrs = $blocks[0]['attrs'];
			$pageSize = $attrs['pageSize'] ?? 'A4';
			$pageOrientation = $attrs['pageOrientation'] ?? 'L';
		}

		// Extract CSS from block content
		$cssContent = '';
		if ( ! empty( $blocks ) ) {
			foreach ( $blocks as $block ) {
				$htmlContent = apply_filters( 'the_content', render_block( $block ) );
				preg_match_all( '/<style>(.*?)<\/style>/is', $htmlContent, $matches );
				if ( ! empty( $matches[1] ) ) {
					foreach ( $matches[1] as $cssBlock ) {
						$cssContent .= $cssBlock;
					}
				}
			}
		}

		// Sanitize CSS content
		$cssContent = str_replace( '>', ' ', $cssContent );

		// Generate PDF preview
		$certificate_pdf = new Generator( $course_id, $student_id, $certificate_template, $cssContent, $pageSize, $pageOrientation );
		return $certificate_pdf->preview_certificate();
	}
}
