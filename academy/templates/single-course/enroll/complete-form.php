<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! $is_completed_course ) :
	?>
<form id="academy_course_complete_form" class="academy-widget-enroll__complete-form" method="post" action="#">
	<?php wp_nonce_field( 'academy_nonce', 'security' ); ?>
	<input type="hidden" name="course_id" value="<?php echo esc_attr( get_the_ID() ); ?>">
	<button type="submit" class="academy-btn academy-btn--preset-light-purple ">
		<?php esc_html_e( 'Complete Course', 'academy' ); ?>
	</button>
</form>
<?php endif; ?>

<?php
	do_action( 'academy/templates/single_course/enroll_complete_form', $is_completed_course )
?>
