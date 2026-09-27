<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="academy-announcements-wrap">
	<?php if ( ! empty( $announcements ) ) : ?>
		<?php foreach ( $announcements as $announcement ) : ?>
			<div class="academy-announcement-item">
				<div class="academy-announcement-item__head">
					<span class="academy-icon academy-icon--announcement"></span>
					<h3><?php echo esc_html( $announcement->post_title ); ?></h3>
				</div>
				<?php if ( ! empty( $announcement->post_date ) ) : ?>
					<span class="academy-announcement-item__meta">
						<?php
						/* translators: %s: human-readable time difference, e.g. "2 days" */
						printf( esc_html__( '%s ago', 'academy' ), esc_html( human_time_diff( strtotime( $announcement->post_date ) ) ) );
						?>
					</span>
				<?php endif; ?>
				<div class="academy-announcement-item__content">
					<?php echo wp_kses_post( $announcement->post_content ); ?>
				</div>
			</div>
		<?php endforeach; ?>
	<?php else : ?>
		<div class="academy-announcement-item">
			<div class="academy-announcement-item__head">
				<h3><?php esc_html_e( 'No Announcements Found Yet!', 'academy' ); ?></h3>
			</div>
		</div>
	<?php endif; ?>
</div>
