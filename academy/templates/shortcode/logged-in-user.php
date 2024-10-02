<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="academy-logged-in-message">
	<?php
		echo sprintf(
			/* translators: %1$s: User name, %2$s: Opening <a> tag, %3$s: Closing </a> tag */
			esc_html__( 'You are Logged in as %1$s (%2$sLogout%3$s)', 'academy' ),
			wp_kses_post( $user_name ),
			wp_kses_post( $a_tag ),
			wp_kses_post( $close_a_tag )
		);
		?>
</div>
