<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="academy-enroll-form-shortcode__price">
	<?php echo wp_kses_post( $price ); ?>
</div>
<div class="academy-enroll-form-shortcode__button">
	<a href="<?php echo $enroll_link; ?>"><?php esc_html_e( 'Enroll Now', 'academy' ); ?></a>
</div>
