<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$user = get_userdata( get_current_user_id() );
$is_student = in_array( 'academy_student', (array) $user->roles, true );

?>
<div class="academy-reg-thankyou">
	<?php if ( $is_student ) : ?>
	<h2 class="academy-reg-thankyou__heading"><?php esc_html_e( 'Congratulations! You are now registered as a student.', 'academy' ); ?></h2>
	<p class="academy-reg-thankyou__description"><?php esc_html_e( 'Start learning from today', 'academy' ); ?></p>
	<a class="academy-btn academy-btn--inline-block academy-btn--bg-purple" href="<?php echo esc_url( $dashboard_url ); ?>"><?php esc_html_e( 'Go to Dashboard', 'academy' ); ?></a>
	<?php else : ?>
		<h2 class="academy-reg-thankyou__heading"><?php esc_html_e( 'You are logged in.', 'academy' ); ?></h2>
		<a class="academy-btn academy-btn--inline-block academy-btn--bg-purple" href="<?php echo esc_url( $dashboard_url ); ?>"><?php esc_html_e( 'Go to Dashboard', 'academy' ); ?></a>
	<?php endif ?>
</div>
