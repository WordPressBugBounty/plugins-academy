<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="academy-single-course__content-item academy-single-course__content-item--description">
	<h2 class="academy-single-course__content-item--description-title"><?php esc_html_e( 'Course Overview', 'academy' ); ?></h2>
	<?php
		the_content();
	?>
</div>
