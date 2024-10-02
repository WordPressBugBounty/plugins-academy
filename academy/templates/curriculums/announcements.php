<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="academy-lesson-tab">
	<div class="academy-lesson-tab__body">
		<div class="academy-announcements-wrap">
		<?php if ( ! empty( $announcements ) ) : ?>
			<?php foreach ( $announcements as $announcement ) : ?>
				<div class="academy-announcement-item">
					<h3><?php echo esc_html( $announcement->post_title ); ?></h3>
					<?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo $announcement->post_content;
					?>
				</div>
		<?php endforeach; ?>
		<?php else : ?>
			<div class="academy-announcement-item">
				<h3>
					<?php echo esc_html( 'No Announcements Found Yet!' ); ?>
				</h3>
			</div>
		<?php endif; ?>
		</div>
	</div>
</div>
