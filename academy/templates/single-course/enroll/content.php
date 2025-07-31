<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div class="academy-widget-enroll__content">
	<ul class="academy-widget-enroll__content-lists">
		<?php
		if ( $skill ) :
			?>
		<li>
			<span class="label">
				<span class="academy-icon academy-icon--level"></span>
			<?php esc_html_e( 'Course Level', 'academy' ); ?>
			</span>
			<span class="data"><?php echo esc_html( $skill ); ?></span>
		</li>
			<?php
			endif;
		if ( $total_lessons ) :
			?>
		<li>
			<span class="label">
				<i class="academy-icon academy-icon--video-lesson"></i>
			<?php esc_html_e( 'Lessons', 'academy' ); ?>
			</span>
			<span class="data"><?php echo esc_html( $total_lessons ); ?></span>
		</li>
			<?php
			endif;
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
		if ( $total_enrolled && $total_enroll_count_status ) :
			?>
		<li>
			<span class="label">
				<i class="academy-icon academy-icon--group-profile"></i>
				<?php esc_html_e( 'Enrolled', 'academy' ); ?>
			</span>
			<span class="data"><?php echo esc_html( $total_enrolled ?? 0 ); ?></span>
		</li>
			<?php
			endif;

		if ( $max_students ) :
			?>
		<li>
			<span class="label">
				<span class="academy-icon academy-icon--user"></span>
			<?php esc_html_e( 'Available Seats', 'academy' ); ?>
			</span>
			<span class="data"><?php echo esc_html( max( $max_students - $total_enrolled, 0 ) ); ?></span>
		</li>
			<?php
			endif;
		?>
		<li>
			<span class="label">
				<span class="academy-icon academy-icon--file"></span>
			<?php esc_html_e( 'Additional Resource', 'academy' ); ?>
			</span>
			<span class="data"><?php echo esc_html( $total_resource ?? 0 ); ?></span>
		</li>

		<?php
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
	<?php do_action( 'academy/templates/single_course_enroll_content_after' ); ?>
</div>
