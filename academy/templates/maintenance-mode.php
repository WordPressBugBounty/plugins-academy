<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// $message and $end_time are passed in by
// Academy\Frontend\Template::maintenance_mode_template_redirect() via
// Helper::get_template()'s $args (extracted into local vars there).
$academy_maintenance_message  = ! empty( $message ) ? $message : esc_html__( "We're currently making improvements to our course platform. Please check back soon.", 'academy' );
$academy_maintenance_end_time = ! empty( $end_time ) ? $end_time : '';

academy_get_header();
?>

<div class="academy-maintenance-notice">
	<span class="academy-maintenance-notice__icon" aria-hidden="true">
		<span class="academy-icon academy-icon--settings"></span>
	</span>
	<h1 class="academy-maintenance-notice__title"><?php esc_html_e( 'Under Maintenance', 'academy' ); ?></h1>
	<div class="academy-maintenance-notice__message"><?php echo wp_kses_post( $academy_maintenance_message ); ?></div>

	<?php if ( $academy_maintenance_end_time ) : ?>
		<div class="academy-maintenance-notice__countdown" id="academy-maintenance-countdown" data-end-time="<?php echo esc_attr( strtotime( $academy_maintenance_end_time ) * 1000 ); ?>">
			<div class="academy-maintenance-notice__countdown-item">
				<span class="academy-maintenance-notice__countdown-value" data-unit="days">00</span>
				<span class="academy-maintenance-notice__countdown-label"><?php esc_html_e( 'Days', 'academy' ); ?></span>
			</div>
			<div class="academy-maintenance-notice__countdown-item">
				<span class="academy-maintenance-notice__countdown-value" data-unit="hours">00</span>
				<span class="academy-maintenance-notice__countdown-label"><?php esc_html_e( 'Hours', 'academy' ); ?></span>
			</div>
			<div class="academy-maintenance-notice__countdown-item">
				<span class="academy-maintenance-notice__countdown-value" data-unit="minutes">00</span>
				<span class="academy-maintenance-notice__countdown-label"><?php esc_html_e( 'Minutes', 'academy' ); ?></span>
			</div>
			<div class="academy-maintenance-notice__countdown-item">
				<span class="academy-maintenance-notice__countdown-value" data-unit="seconds">00</span>
				<span class="academy-maintenance-notice__countdown-label"><?php esc_html_e( 'Seconds', 'academy' ); ?></span>
			</div>
		</div>
		<script>
			(function () {
				var el = document.getElementById( 'academy-maintenance-countdown' );
				if ( ! el ) {
					return;
				}
				var endTime = parseInt( el.getAttribute( 'data-end-time' ), 10 );
				var valueEls = {
					days: el.querySelector( '[data-unit="days"]' ),
					hours: el.querySelector( '[data-unit="hours"]' ),
					minutes: el.querySelector( '[data-unit="minutes"]' ),
					seconds: el.querySelector( '[data-unit="seconds"]' ),
				};
				function pad( n ) {
					return n < 10 ? '0' + n : '' + n;
				}
				function tick() {
					var diff = endTime - Date.now();
					if ( diff <= 0 ) {
						// The lockout window has passed — reload so the
						// server-side gate lets the real page through.
						window.location.reload();
						return;
					}
					var seconds = Math.floor( diff / 1000 );
					var days = Math.floor( seconds / 86400 );
					seconds -= days * 86400;
					var hours = Math.floor( seconds / 3600 );
					seconds -= hours * 3600;
					var minutes = Math.floor( seconds / 60 );
					seconds -= minutes * 60;
					valueEls.days.textContent = pad( days );
					valueEls.hours.textContent = pad( hours );
					valueEls.minutes.textContent = pad( minutes );
					valueEls.seconds.textContent = pad( seconds );
				}
				tick();
				setInterval( tick, 1000 );
			})();
		</script>
	<?php endif; ?>
</div>

<?php academy_get_footer(); ?>
