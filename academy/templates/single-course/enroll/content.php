<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! $enrolled ) :
	?>
<div class="academy-widget-enroll__head">
	<?php
	if ( $is_paid ) {
		if ( $price ) {
			echo '<div class="academy-course-price">' . wp_kses_post( $price ) . '</div>';
		} else {
			echo '<div class="academy-course-type">' . esc_html__( 'Paid', 'academy' ) . '</div>';
		}
	} elseif ( $is_public ) {
		echo '<div class="academy-course-type">' . esc_html__( 'Public', 'academy' ) . '</div>';
	} else {
		echo '<div class="academy-course-type">' . esc_html__( 'Free', 'academy' ) . '</div>';
	}
	?>
</div>
<?php endif; ?>
<div class="academy-widget-enroll__content">
	<ul class="academy-widget-enroll__content-lists">
		<?php
		if ( $duration ) :
			?>
		<li>
			<span class="label">
				<span class="academy-icon academy-icon--clock"></span>
			<?php esc_html_e( 'Duration', 'academy' ); ?>
			</span>
			<span class="data"><?php echo esc_html( $duration ); ?></span>
		</li>
			<?php
			endif;
		?>
		<?php
		if ( $total_lessons ) :
			?>
		<li>
			<span class="label">
				<i class="academy-icon academy-icon--lesson"></i>
			<?php esc_html_e( 'Lessons', 'academy' ); ?>
			</span>
			<span class="data"><?php echo esc_html( $total_lessons ); ?></span>
		</li>
			<?php
			endif;
		?>
		<?php
		if ( $total_enrolled && $total_enroll_count_status ) :
			?>
		<li>
			<span class="label">
				<i class="academy-icon academy-icon--group-profile"></i>
			<?php esc_html_e( 'Enrolled', 'academy' ); ?>
			</span>
			<span class="data"><?php echo esc_html( $total_enrolled ); ?></span>
		</li>
			<?php
			endif;
		?>
		<?php
		if ( $language ) :
			?>
		<li>
			<span class="label">
				<i class="academy-icon academy-icon--language"></i>
			<?php esc_html_e( 'Language', 'academy' ); ?>
			</span>
			<span class="data"><?php echo esc_html( $language ); ?></span>
		</li>
			<?php
			endif;
		?>
		<?php
		if ( $skill ) :
			?>
		<li>
			<span class="label">
				<span class="academy-icon academy-icon--skill"></span>
			<?php esc_html_e( 'Skill', 'academy' ); ?>
			</span>
			<span class="data"><?php echo esc_html( $skill ); ?></span>
		</li>
			<?php
			endif;

		if ( $max_students ) :
			?>
		<li>
			<span class="label">
				<span class="academy-icon academy-icon--group-profile"></span>
			<?php esc_html_e( 'Available Seats', 'academy' ); ?>
			</span>
			<span class="data"><?php echo esc_html( max( $max_students - $total_enrolled, 0 ) ); ?></span>
		</li>
			<?php
			endif;

		if ( $last_update ) :
			?>
		<li>
			<span class="label">
				<i class="academy-icon academy-icon--calender"></i>
			<?php esc_html_e( 'Last Update', 'academy' ); ?>
			</span>
			<span class="data"><?php echo esc_html( $last_update ); ?></span>
		</li>
			<?php
			endif;
		?>
	</ul>
</div>

<?php
if ( $enrolled ) :
	?>
<div class="academy-widget-enroll__enrolled-info">
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo sprintf(
		// translators: %s: Enrollment date
		esc_html__( 'You have been enrolled on %s', 'academy' ),
		wp_kses_post('<span>' . date_i18n(
			get_option( 'date_format' ),
			strtotime( $enrolled->post_date )
		) . '</span>')
	);
	if ( $completed ) {
		echo sprintf(
			// translators: %s: Completed date
			esc_html__( 'and completed on %s', 'academy' ),
			wp_kses_post('<span> ' . date_i18n(
				get_option( 'date_format' ),
				strtotime( $completed->completion_date )
			) . '</span>')
		);
	}
	?>
</div>
<?php endif; ?>
