<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$button_text = $is_free ? __( 'Enroll Now', 'academy-pro' ) : __( 'Add to Cart', 'academy-pro' );
?>
<div class="academy-enroll-form-shortcode__price">
	<?php echo wp_kses_post( ucwords( $course_type ) ); ?>
</div>

<div class="academy-enroll-form-shortcode__prerequisite" id="prerequisite-message">
	<div class="academy-shortcode-archive-prerequisite">	
		<p class="academy-shortcode-prerequisites-message"><?php esc_html_e( 'NOTE: Complete prerequisite courses to begin this.', 'academy-pro' ); ?></p>
		<ul class="academy-shortcode-prerequisites-lists">
			<?php
			foreach ( $required_courses as $course_id ) :
				?>
			<li><a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>"><?php echo esc_html( get_the_title( $course_id ) ); ?></a></li>
				<?php
				endforeach;
			?>
		</ul>
	</div>
</div>
<!-- here is button text -->
<button 
	type="button" 
	class="academy-enroll-form-shortcode__prerequisite-button" 
	id="enroll-prerequisite-btn"
>
	<?php echo esc_html( $button_text ); ?>
</button>
