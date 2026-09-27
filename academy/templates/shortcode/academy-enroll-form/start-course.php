<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


$continue_learning  = apply_filters( 'academy/templates/start_course_url', \Academy\Helper::get_start_course_permalink( $course_id ), $course_id );
$start_course_link_attributes = apply_filters(
	'academy/templates/start_course_link_attributes',
	array(
		'class' => 'academy-btn academy-btn--bg-purple',
		'href'  => $continue_learning,
	),
	$course_id
);
$total_completed_lessons = \Academy\Helper::get_total_number_of_completed_course_topics_by_course_and_student_id( $course_id );
?>
<div class="academy-enroll-form-shortcode__price">
	<?php echo wp_kses_post( ucwords( $course_type ) ); ?>
</div>
<?php if ( $is_public_course ) : ?>
	<div class="academy-enroll-form-shortcode__continue">
		<a<?php foreach ( $start_course_link_attributes as $attribute_name => $attribute_value ) :
			if ( '' === $attribute_value || null === $attribute_value || false === $attribute_value ) {
				continue;
			}
			printf(
				' %1$s="%2$s"',
				esc_attr( $attribute_name ),
				'href' === $attribute_name ? esc_url( $attribute_value ) : esc_attr( $attribute_value )
			);
		endforeach; ?>>
		<?php esc_html_e( 'Start Course', 'academy' ); ?> 
		</a>
	</div>
<?php elseif ( ( $enrolled && 'completed' === $enrolled->enrolled_status ) ) : ?>
	<div class="academy-enroll-form-shortcode__continue">
		<a<?php foreach ( $start_course_link_attributes as $attribute_name => $attribute_value ) :
			if ( '' === $attribute_value || null === $attribute_value || false === $attribute_value ) {
				continue;
			}
			printf(
				' %1$s="%2$s"',
				esc_attr( $attribute_name ),
				'href' === $attribute_name ? esc_url( $attribute_value ) : esc_attr( $attribute_value )
			);
		endforeach; ?>>
		<?php echo $total_completed_lessons ? esc_html__( 'Continue learning', 'academy' ) : esc_html__( 'Start Course', 'academy' ); ?> 
		</a>
	</div>
<?php elseif ( $enrolled && ( 'on-hold' === $enrolled->enrolled_status || 'processing' === $enrolled->enrolled_status ) ) : ?>
	<div class="academy-enroll-form-shortcode__notice <?php echo 'academy-enroll-form-shortcode__notice--' . esc_attr( $enrolled->enrolled_status ); ?>">
	<?php
		/* translators: %s is a placeholder for enrollment status */
		printf( esc_html__( 'Your Enrollment Status is %s. Wait for admin approval.', 'academy' ), wp_kses_post( '<strong>' . $enrolled->enrolled_status . '</strong>' ) );
	?>
	</div>
<?php endif; ?>
