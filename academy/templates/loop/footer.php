<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="academy-course__footer academy-d-flex academy-justify-content-between academy-align-items-center">
	<?php
		do_action( 'academy/templates/before_course_loop_footer_inner' );
		/**
		 * Hook -
		 *
		 * @Hooked - academy_course_loop_footer_inner_rating - 11
		 * @Hooked - academy_course_loop_footer_inner_price - 10
		 */
		$card_style = Academy\Helper::get_settings( 'course_card_style' );
		do_action( 'academy/templates/course_loop_footer_inner', $card_style );
		do_action( 'academy/templates/after_course_loop_footer_inner' );
	?>
</div>
