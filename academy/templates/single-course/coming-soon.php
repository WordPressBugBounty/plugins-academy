<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$countdown_items = [
	'days'    => __( 'Days', 'academy' ),
	'hours'   => __( 'Hours', 'academy' ),
	'minutes' => __( 'Minutes', 'academy' ),
	'seconds' => __( 'Seconds', 'academy' ),
];

?>

<div class="academy-enroll-widget__coming-soon">

	<div class="academy-enroll-widget__coming-soon--status">
		<?php esc_html_e( 'Coming Soon', 'academy' ); ?>
	</div>

	<div class="academy-enroll-widget__coming-soon--countdown">
		<?php foreach ( $countdown_items as $key => $label ) : ?>
			<div class="countdown-box">
				<strong class="academy-js-<?php echo esc_attr( $key ); ?>">00</strong>
				<span><?php echo esc_html( $label ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="academy-enroll-widget__coming-soon--date">
		<span class="academy-icon academy-icon--calender"></span>
		<span class="academy-js-available-date"></span>
	</div>
</div>
