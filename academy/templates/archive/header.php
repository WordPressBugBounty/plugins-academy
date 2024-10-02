<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="academy-courses__header">
	<?php
	/**
	 * Hook: academy/templates/archive_course_description.
	 *
	 * @Hooked: academy_archive_course_header_filter - 10
	 */
	do_action( 'academy/templates/archive_course_description' );
	?>	
</div>
