<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

	academy_get_header( 'course' );
?>

<?php
	/**
	 * @hook - academy/templates/before_main_content
	 */
	do_action( 'academy/templates/before_main_content', 'instructor-public-profile.php' );
?>

<div class="academy-courses academy-courses--by-instructor">
	<?php
		do_action( 'academy/templates/instructor_public_profile_header' );
	?>
	<div class="academy-courses--by-instructor__content">
		<div class="academy-container">
			<div class="academy-row">
				<div class="academy-col-md-3">
					<?php
					do_action( 'academy/templates/instructor_public_profile_sidebar' );
					?>
				</div>
				<div class="academy-col-md-9">
					<?php
						do_action( 'academy/templates/instructor_public_profile_content' );
					?>
				</div>
				<?php
					do_action( 'academy/templates/instructor_public_profile_footer' );
				?>
			</div>
		</div>
	</div>
</div>

<?php
	/**
	 * @hook - academy/templates/after_main_content
	 */
	do_action( 'academy/templates/after_main_content', 'instructor-public-profile.php' );
?>

<?php
academy_get_footer( 'course' );
