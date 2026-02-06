<?php
namespace AcademyCertificates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyCertificates\PDF\Generator;

class Helper {

	public static function render_certificate( $course_id, $template_id, $student_id, $verification_id = '' ) {
		if ( ! get_option( 'academy_mpdf_fonts_downloaded', false ) ) {
			self::send_notice( __( 'Please download the fonts before generating the PDF.', 'academy' ) );
		}

		$certificate = get_post( $template_id );

		if ( empty( $certificate->post_content ) ) {
			return;
		}

		if ( ! $student_id && ! empty( $verification_id ) ) {
			global $wpdb;
			$student_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT user_id 
					FROM {$wpdb->comments} 
					WHERE comment_content = %s 
					LIMIT 1",
					$verification_id
				)
			);
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

		$course_completed = \Academy\Helper::is_completed_course( $course_id, $student_id, true, $verification_id );
		$completion_date  = ( $course_completed && ! empty( $course_completed->completion_date ) ) ? date_i18n( get_option( 'date_format' ), strtotime( $course_completed->completion_date ) ) : __( 'Completion Date Missing', 'academy' ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		$total_topics = \Academy\Helper::get_total_topic_title_by_course_id( $course_id );
		$course_requirement  = get_post_meta( $course_id, 'academy_course_materials_included', true );
		$course_materials  = get_post_meta( $course_id, 'academy_course_requirements', true );
		$what_learn       = get_post_meta( $course_id, 'academy_course_benefits', true );
		// Replace dynamic placeholders with available values or default messages
		$certificate_template_dynamic_code_args = apply_filters( 'academy_certificates/template_dynamic_codes', [ '{{learner}}', '{{course_title}}', '{{instructor}}', '{{course_place}}', '{{completion_date}}', '{{total_topics}}', '{{course_requirements}}', '{{course_materials}}', '{{what_you_will_learn}}' ] );
		$certificate_template_dynamic_variable_args = apply_filters( 'academy_certificates/template_dynamic_codes_variables', [ $student_name, $course_title, $instructor_name, $course_place, $completion_date, $total_topics, $course_requirement, $course_materials, $what_learn ], $student_id, $course_id );
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
		return $certificate_pdf->preview_certificate( get_the_title( $course_id ) );
	}

	protected static function send_notice( string $message ) {
		?>
		<p>
			<?php echo esc_html( $message ); ?>
			<a href="<?php echo esc_attr( home_url() ); ?>">Back to Home</a>
		</p>
		<?php
		exit;
	}

	public static function necessary_certificates() {
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
}
