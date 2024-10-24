<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
global $authordata;
$course_id                = get_the_ID();
$user_id                  = get_current_user_id();
$is_enabled_academy_login = \Academy\Helper::get_settings( 'is_enabled_academy_login', true );
$force_login_before_enroll = $is_enabled_academy_login && \Academy\Helper::get_settings( 'woo_force_login_before_enroll', true );
$course_permalink         = get_permalink( $course_id );
$continue_learning        = apply_filters( 'academy/templates/start_course_url', \Academy\Helper::get_start_course_permalink( $course_id ) );
$total_completed_lessons  = \Academy\Helper::get_total_number_of_completed_course_topics_by_course_and_student_id( $course_id );
$is_enrolled              = \Academy\Helper::is_enrolled( $course_id, $user_id );
$product_id               = isset( $product_id ) ? $product_id : null;
$is_paid                  = isset( $is_paid ) ? $is_paid : false;
$card_style               = \Academy\Helper::get_settings( 'course_card_style' );
$required_levels          = isset( $required_levels ) ? $required_levels : [];
$course_type              = isset( $course_type ) ? $course_type : 'public';
$prices                   = Academy\Helper::is_plugin_active( 'surecart/surecart.php' ) && Academy\Helper::get_addon_active_status( 'surecart' ) ? ( new AcademyProSurecart\Integration() )->check_integration_and_price( array(), $course_id ) : '';
?>

<?php if ( 'layout_two' === $card_style ) :
	Academy\Helper::get_template(
		'loop/author.php'
	);
endif; ?>


<?php if ( 'public' === $course_type && empty( $required_levels ) ) : ?>
	<div class="academy-widget-enroll__continue">
		<a class="academy-btn academy-btn--bg-purple" href="<?php echo esc_url( $continue_learning ); ?>">
			<?php echo esc_html__( 'Start Course', 'academy' ); ?>
		</a>
	</div>
<?php elseif ( $is_enrolled ) : ?>
	<div class="academy-widget-enroll__continue">
		<a class="academy-btn academy-btn--bg-purple" href="<?php echo esc_url( $continue_learning ); ?>">
			<?php echo $total_completed_lessons ? esc_html__( 'Continue learning', 'academy' ) : esc_html__( 'Start Course', 'academy' ); ?> 
		</a>
	</div>
<?php elseif ( $prices ) :
	Academy\Helper::get_template(
		'loop/surecart-form.php',
		[
			'is_enabled_academy_login' => $is_enabled_academy_login,
			'card_style'   => $card_style,
			'prices'       => $prices
		]
	); ?>
<?php elseif ( $required_levels ) :
	Academy\Helper::get_template(
		'loop/pmp-form.php',
		[
			'is_enabled_academy_login' => $is_enabled_academy_login,
			'card_style'               => $card_style,
			'required_levels'          => $required_levels
		]
	); ?>
<?php elseif ( $is_paid && $product_id ) :
	Academy\Helper::get_template(
		'loop/woo-form.php',
		[
			'force_login_before_enroll' => $force_login_before_enroll,
			'card_style'                => $card_style,
			'product_id'               => $product_id
		]
	); ?>
<?php elseif ( $is_paid && ! empty( $download_id ) ) :
	Academy\Helper::get_template(
		'loop/edd-form.php',
		[
			'is_enabled_academy_login' => $is_enabled_academy_login,
			'card_style'               => $card_style,
			'download_id'              => $download_id
		]
	); ?>
<?php elseif ( 'free' === $course_type ) :
	Academy\Helper::get_template(
		'single-course/enroll/enroll-form.php',
		[
			'is_enabled_academy_login' => $is_enabled_academy_login,
		]
	); ?>
<?php endif; ?>
