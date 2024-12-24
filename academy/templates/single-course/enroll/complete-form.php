<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! $is_completed_course ) :
	$course_id = get_the_ID();
	?>
<form id="academy_course_complete_form" 
	class="academy-widget-enroll__complete-form" 
	method="post" 
	action="#"
	data-is-enabled-course-popup-review="<?php echo esc_attr( \Academy\Helper::get_settings( 'is_enabled_course_popup_review', false ) ); ?>"
	data-is-enabled-single-course-review="<?php echo get_post_meta( $course_id, 'academy_is_disabled_course_review', true ); ?>"
>
	<?php wp_nonce_field( 'academy_nonce', 'security' ); ?>
	<input type="hidden" name="course_id" value="<?php echo esc_attr( $course_id ); ?>">
	<button type="submit" class="academy-btn academy-btn--preset-light-purple ">
		<?php esc_html_e( 'Complete Course', 'academy' ); ?>
	</button>
</form>
<?php endif; ?>

<?php
	do_action( 'academy/templates/single_course/enroll_complete_form', $is_completed_course )
?>
