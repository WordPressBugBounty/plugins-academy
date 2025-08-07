<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="academy-dashboard-enrolled-courses academy-dashboard__content">
	<div class="academy-row">
		<?php if ( ! empty( $completed_courses ) ) :
			$has_certificate_course = false; // Flag to check if any course has a certificate
			foreach ( $completed_courses as $course_id ) :
				$certificate_id = get_post_meta( $course_id, 'academy_course_certificate_id', true ) ??
				\Academy\Helper::get_settings( 'academy_primary_certificate_id' );
				$enable_certificate = get_post_meta( $course_id, 'academy_course_enable_certificate', true );

				if ( $certificate_id && $enable_certificate ) :
					$has_certificate_course = true; // Set flag when a certificate course is found
					$course_title      = get_the_title( $course_id );
					$thumbnail_url     = Academy\Helper::get_the_course_thumbnail_url_by_id( $course_id );
					$course_permalink  = get_permalink( $course_id );
					?>
					<div class="academy-col-lg-4 academy-col-md-6 academy-col-sm-12">
						<div class="academy-mycourse academy-mycourse-12">
							<div class="academy-mycourse__thumbnail">
								<a href="<?php echo esc_url( $course_permalink ); ?>">
									
								<?php
								// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
								echo '<img class="academy-course__thumbnail-image" src="' . esc_url( $thumbnail_url ) . '" alt="' . esc_attr( $course_title ) . '">'; ?>
								</a>
							</div>
							<div class="academy-mycourse__content">
								<h3>
									<a href="<?php echo esc_url( $course_permalink ); ?>">
										<?php echo esc_html( $course_title ); ?>
									</a>
								</h3>
								<div class="academy-widget-enroll__continue">
									<a class="academy-btn academy-btn--bg-light-purple" href="<?php echo esc_url( add_query_arg( array( 'source' => 'certificate' ), $course_permalink ) ); ?>">
										<?php esc_html_e( 'Download Certificate', 'academy' ); ?>
									</a>
								</div>
							</div>
						</div>
					</div>
					<?php
				endif;
			endforeach;

			// Display message if no courses with certificates are found
			if ( ! $has_certificate_course ) : ?>
				<h3 class="academy-not-found"><?php esc_html_e( 'You have not downloadable certificate course yet.', 'academy' ); ?></h3>
			<?php endif; ?>
		<?php else : ?>
			<h3 class="academy-not-found"><?php esc_html_e( 'You have not downloadable certificate course yet.', 'academy' ); ?></h3>
		<?php endif; ?>
	</div>
</div>
