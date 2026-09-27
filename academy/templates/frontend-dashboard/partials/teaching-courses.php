<?php
/**
 * The instructor's courses, with enrollments and ratings.
 *
 * @var int[] $course_ids Course IDs.
 *
 * @package Academy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="academy-table academy-table--dashboard-course">
<div class="academy-table__container">
		<div class="academy-table__table academy-table--has-slider">
			<div class="academy-table__head">
				<div class="academy-table__head-row">
						<div class="academy-table__row-cell academy-table__header-row-cell">
							<?php echo esc_html__( 'Course Name', 'academy' ); ?> 
						</div>
						<div class="academy-table__row-cell academy-table__header-row-cell">
							<?php echo esc_html__( 'Enrolled Course', 'academy' ); ?>
						</div>
						<div class="academy-table__row-cell academy-table__header-row-cell">
							<?php echo esc_html__( 'Course Review', 'academy' ); ?>
						</div>
					</div>
				</div>
				<div class="academy-table__body">
				<?php if ( ! empty( $course_ids ) ) : ?>
					<?php  // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					foreach ( $course_ids as $id ) : ?>
						<div class="academy-table__body-row">

						<div class="academy-table__row-cell">
							<div class=""><?php echo esc_html( get_the_title( $id ) ); ?></div>
						</div>

						<div class="academy-table__row-cell">
							<?php echo esc_html( \Academy\Helper::count_course_enrolled( $id ) ); ?>
						</div>
						<div class="academy-table__row-cell">
							<?php
							$rating = \Academy\Helper::get_course_rating( $id );
							$rating_markup = \Academy\Helper::star_rating_generator( $rating->rating_avg );
							echo wp_kses_post( $rating_markup );
							?>
						</div>
				</div>
				<?php endforeach; ?>
				<?php else : ?>
					<div class="academy-oops academy-oops__message">
						<div class="academy-oops__icon">
							
						<?php
							// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
							echo '<img src="' . esc_url( ACADEMY_ASSETS_URI . 'images/NoDataAvailable.svg' ) . '" alt="oops">'; ?>
							
							</div>
							<h3 class="academy-oops__heading"><?php esc_html_e( 'Nothing here yet', 'academy' ); ?></h3>
							<h3 class="academy-oops__text"><?php esc_html_e( 'Nothing to show yet.', 'academy' ); ?></h3>
						</div>
				<?php endif; ?>
		</div>
</div>
</div>
