<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="academy-analytics-cards">
	<?php foreach ( $data as $key => $item ) : ?>
		<a class="academy-analytics-cards--card" href="<?php echo isset( $item['link'] ) ? esc_html( $item['link'] ) : ''; ?>">
			<div class="academy-analytics-card--icon icon-<?php echo esc_html( $item['color'] ); ?>"><span class="<?php echo esc_html( $item['icon'] ); ?>"></span></div>
			<div class="academy-analytics-card--data">
				<h2 class="academy-analytics-card--value"><?php echo esc_html( $item['value'] ); ?></h2>
				<p class="academy-analytics-card--label"><?php echo esc_html( $item['label'] ); ?><span></span></p>
			</div>
		</a>
	<?php endforeach; ?>
	<?php do_action( 'academy/templates/frontend_dashboard/after_analytics_card_item' ); ?>
</div>

<?php do_action( 'academy/templates/frontend_dashboard/after_analytics_cards' ); ?>

<?php if ( current_user_can( 'manage_academy_instructor' ) ) :
	; ?>

<div class="kzui-table kzui-table--dashboard-course">
<div class="kzui-table__container">
		<div class="kzui-table__table kzui-table--has-slider">
			<div class="kzui-table__head">
				<div class="kzui-table__head-row">
						<div class="kzui-table__row-cell kzui-table__header-row-cell">
							<?php echo esc_html__( 'Course Name', 'academy' ); ?> 
						</div>
						<div class="kzui-table__row-cell kzui-table__header-row-cell">
							<?php echo esc_html__( 'Enrolled Course', 'academy' ); ?>
						</div>
						<div class="kzui-table__row-cell kzui-table__header-row-cell">
							<?php echo esc_html__( 'Course Review', 'academy' ); ?>
						</div>
					</div>
				</div>
				<div class="kzui-table__body">
				<?php if ( ! empty( $course_ids ) ) : ?>
					<?php  // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					foreach ( $course_ids as $id ) : ?>
						<div class="kzui-table__body-row">

						<div class="kzui-table__row-cell">
							<div class=""><?php echo esc_html( get_the_title( $id ) ); ?></div>
						</div>

						<div class="kzui-table__row-cell">
							<?php echo esc_html( \Academy\Helper::count_course_enrolled( $id ) ); ?>
						</div>
						<div class="kzui-table__row-cell">
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
							<img src="<?php echo esc_url( ACADEMY_ASSETS_URI . 'images/NoDataAvailable.svg' ); ?>" alt="oops"></div>
							<h3 class="academy-oops__heading"><?php esc_html_e( 'No data Available!!', 'academy' ); ?></h3>
							<h3 class="academy-oops__text"><?php esc_html_e( 'No purchase data was found to see the available list here.', 'academy' ); ?></h3>
						</div>
				<?php endif; ?>
		</div>
</div>
</div>
<?php endif; ?>
