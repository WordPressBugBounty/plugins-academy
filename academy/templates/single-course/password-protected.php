<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<div class="academy-password-form-container">
	<form action="<?php echo esc_url( site_url( 'wp-login.php?action=postpass', 'login_post' ) ); ?>" method="post" class="academy-password-form">
		<label class="academy-password-form__label" for="password-form">
			<span class="academy-icon academy-icon--lock"></span>
			<span class="academy-password-form__heading">
				<?php esc_html_e( 'Course Is Protected', 'academy' ); ?>
			</span>
			<span class="academy-password-form__sub-title">
				<?php esc_html_e( 'This course is password protected. Please enter the password to access this course.', 'academy' ); ?>
			</span>
		</label>
	
		<div class="academy-password-form__wrapper">
			<label class="academy-password-form__title" for="subtitle">
				<?php esc_html_e( 'Password', 'academy' ); ?>
			</label>
	
			<div class="academy-password-form__input-wrapper">
				<input 
					type="password" name="post_password" id="password"
					class="academy-password-form__input" 
					placeholder="Enter course password"
				/>
				<span id="password-icon-show" class="toggle-password academy-icon academy-icon--eye"></span>
				<span id="password-icon-hide" class="toggle-password academy-icon academy-icon--lock"
					style="display: none;"></span>
			</div>
	
			<button type="submit" class="academy-btn academy-btn--bg-purple academy-btn--password-submit">
				<?php esc_html_e( 'Unlock', 'academy' ); ?>
			</button>
	
			<button type="button" class="academy-btn--back-to-courses">
				<a class="academy-btn--back-to-courses" href="<?php echo esc_url( home_url( '/courses' ) ); ?>">
					<?php esc_html_e( 'Return to Courses', 'academy' ); ?>
				</a>
			</button>
		</div>
	</form>
</div>
