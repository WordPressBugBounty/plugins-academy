<?php
/**
 * Notice shown to a logged-out visitor in place of the enrolled course grid.
 *
 * This template can be overridden by copying it to
 * yourtheme/academy/shortcode/academy-enrolled-courses/login-required.php
 *
 * @var string $message Notice text.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="academy-message academy-message--info">
	<?php echo esc_html( $message ); ?>
</div>
