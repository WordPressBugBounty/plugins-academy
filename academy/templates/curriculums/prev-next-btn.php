<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="academy-lesson-content-prev-next-btn">
	<?php if ( ! empty( $previous['link'] ) ) : ?>
	<a href="<?php echo esc_url( $previous['link'] ); ?>" class="academy-btn academy-btn--previous-lesson false" role="presentation">
		<span class="academy-btn__label"><?php echo esc_html( $previous['name'] ); ?></span>
		<div class="academy-btn__icon">
			<span class="academy-icon academy-icon--arrow-left"></span>
		</div>
	</a>
	<?php endif ?>
	<?php if ( ! empty( $next['link'] ) ) : ?>
	<a href="<?php echo esc_url( $next['link'] ); ?>" class="academy-btn academy-btn--next-lesson false" role='presentation'>
		<div class="academy-btn__icon">
			<span class="academy-icon academy-icon--arrow-right"></span>
		</div>
		<span class="academy-btn__label"><?php echo esc_html( $next['name'] ); ?></span>
	</a>
	<?php endif; ?>
</div>

