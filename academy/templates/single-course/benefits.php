<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( count( $benefits ) === 0 ) {
	return;
}
?>
<div class="academy-single-course__content-item academy-single-course__content-item--benefits">
	<h4 class="benefits-title"><?php esc_html_e( 'What You\'ll Learn?', 'academy' ); ?></h4>   
	<div class="benefits-content">
		<?php
		if ( count( $benefits ) > 0 ) :
			?>
		<ul class="benefits-information-list">
			<?php
			foreach ( $benefits as $item ) :
				?>
			<li> <i class="academy-icon academy-icon--check"></i> <span> <?php echo esc_html( $item ); ?> </span> </li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>
	</div>
</div>
