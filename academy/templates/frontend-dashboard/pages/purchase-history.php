<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="kzui-list-table kzui-list-table--purchase ">
	<div class="kzui-list-table__container">
		<div class="kzui-list-table__table kzui-list-table--has-slider">
			<div class="kzui-list-table__table-head">
				<div class="kzui-list-table__table-head-row">
					<div class="kzui-list-table__table-row-cell kzui-list-table__table-row-cell-checkbox">
						<input type="checkbox"></div>
						<div class="kzui-list-table__table-row-cell kzui-list-table__table-header-row-cell"><?php echo esc_html__( 'ID', 'academy' ); ?></div>
						<div class="kzui-list-table__table-row-cell kzui-list-table__table-header-row-cell"><?php echo esc_html__( 'Courses', 'academy' ); ?></div>
						<div class="kzui-list-table__table-row-cell kzui-list-table__table-header-row-cell"><?php echo esc_html__( 'Amount', 'academy' ); ?></div>
						<div class="kzui-list-table__table-row-cell kzui-list-table__table-header-row-cell"><?php echo esc_html__( 'Status', 'academy' ); ?></div>
						<div class="kzui-list-table__table-row-cell kzui-list-table__table-header-row-cell"><?php echo esc_html__( 'Date', 'academy' ); ?></div>
					</div>
				</div>
				<div class="kzui-list-table__table-body">
				<?php if ( ! empty( $orders ) ) : ?>
					<?php  // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					foreach ( $orders as $order ) : ?>

						<div class="kzui-list-table__table-body-row">

						<div class="kzui-list-table__table-row-cell">
							<div class=""><?php echo esc_html( $order['ID'] ); ?> </div>
						</div>
						<div class="kzui-list-table__table-row-cell">
						<div class="academy-table-title">
								<?php foreach ( $order['courses'] as $course ) : ?>
								<p><a href="<?php echo esc_html( $course['permalink'] ); ?> "><?php echo esc_html( $course['title'] ); ?></a></p>
								<?php endforeach; ?>
							</div>
						</div>
						<div class="kzui-list-table__table-row-cell"><?php echo wp_kses_post( $order['price'] ); ?></div>
						<div class="kzui-list-table__table-row-cell"><?php echo wp_kses_post( $order['status'] ); ?></div>
							<div class="kzui-list-table__table-row-cell">
							<div class=""><?php echo esc_html( $order['date'] ); ?></div>
						</div>
				</div>
				<?php endforeach; ?>
				<?php else : ?>
					<div class="academy-oops academy-oops__message">
						<div class="academy-oops__icon">
							<img src="<?php echo esc_url( ACADEMY_ASSETS_URI . 'images/NoDataAvailable.svg' ); ?>" alt=""></div>
							<h3 class="academy-oops__heading"><?php echo esc_html__( 'No data Available!!', 'academy' ); ?></h3>
							<h3 class="academy-oops__text"><?php echo esc_html__( 'No purchase data was found to see the available list here.', 'academy' ); ?></h3>
						</div>
				<?php endif; ?>
		</div>
	</div>
</div>