<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $authordata;
?>

<div class="academy-course__author-meta">
	<img src="<?php echo esc_url( get_avatar_url( $authordata->ID, [ 'size' => '40' ] ) ); ?>" />
	<div class="academy-course__author">
		<span class="author"><?php esc_html_e( 'BY -', 'academy' ); ?>
			<?php
			if ( Academy\Helper::get_settings( 'is_show_public_profile' ) ) :
				?>
			<a href="<?php echo esc_url( home_url( '/author/' . $authordata->user_nicename ) ); ?>">
				<?php echo get_the_author(); ?>
			</a>
			<?php else : ?>
				<?php echo get_the_author(); ?>
			<?php endif; ?>
		</span>
	</div>
</div>
