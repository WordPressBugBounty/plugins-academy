<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<?php
	do_action( 'academy/templates/before_student_reg_form' );
?>
<form id="academy_student_reg_form" class="academy-reg-form academy-reg-form--student" method="post" action="#">
<?php
	do_action( 'academy/templates/student_reg_form_start' );
?>
	<?php wp_nonce_field( 'academy_student_registration_nonce', '_wpnonce' ); ?>
	<?php
	Academy\Helper::get_template( 'shortcode/registration-form-fields.php', array(
		'type' => 'student',
		'fields' => $form_fields,
		'allow_fields' => $allow_fields,
		'common_fields' => $common_fields,
	)); ?>
	<div class="academy-register-form-status"></div>
<?php
	do_action( 'academy/templates/student_reg_form_end' );
?>
</form>
<?php
	do_action( 'academy/templates/after_student_reg_form' );
?>
