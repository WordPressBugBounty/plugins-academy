<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course_type      = \Academy\Helper::get_course_type( $course_id );
$template_args    = [ 'course_id' => $course_id ];
$required_levels  = \Academy\Helper::is_plugin_active( 'paid-memberships-pro/paid-memberships-pro.php' ) ? \AcademyProPaidMembershipsPro\Helper::has_course_access( $course_id ) : [];

if ( 'free' !== $course_type || ! empty( $required_levels ) || $is_surecart_integration ) :
	if ( $is_surecart_integration ) {
		\Academy\Helper::get_template( 'shortcode/academy-enroll-form/surecart.php', $template_args );
	} elseif ( 'paid-memberships-pro' === $monetize_engine ) {
		\Academy\Helper::get_template(
			'shortcode/academy-enroll-form/paid-membership-pro.php',
			$template_args
		);
	} elseif ( 'rcp_membership' === $course_type ) {
		\Academy\Helper::get_template( 'shortcode/academy-enroll-form/restrict-content-pro.php', $template_args );
	} elseif ( 'storeengine' === $monetize_engine ) {
		\Academy\Helper::get_template( 'shortcode/academy-enroll-form/storeengine.php', $template_args );
	} elseif ( 'woocommerce' === $monetize_engine ) {
		\Academy\Helper::get_template( 'shortcode/academy-enroll-form/woocommerce.php', $template_args );
	} elseif ( 'edd' === $monetize_engine ) {
		\Academy\Helper::get_template( 'shortcode/academy-enroll-form/easy-digital-downloads.php', $template_args );
	}
	?>
<?php else : ?>
	<div class="academy-enroll-form-shortcode__price">
		<?php esc_html( ucwords( $course_type ) ); ?>
	</div>
	<form id="academy_course_enroll_form" class="academy-course-enroll-form" method="post" action="#">
		<?php wp_nonce_field( 'academy_nonce' ); ?>
		<input type="hidden" name="course_id" value="<?php echo esc_attr( $course_id ); ?>">
		<button type="submit" class="academy-btn academy-btn--bg-purple">
			<?php esc_html_e( 'Enroll Now', 'academy' ); ?>
		</button>
	</form>
<?php endif; ?>
