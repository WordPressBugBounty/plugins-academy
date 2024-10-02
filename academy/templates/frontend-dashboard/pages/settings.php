<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
	$menu_items = array(
		'settings' => __( 'Profile', 'academy' ),
		'reset-password' => __( 'Reset Password', 'academy' ),
	);

	if ( \Academy\Helper::current_user_has_access_frontend_dashboard_menu( 'withdraw' ) ) {
		$menu_items['withdraw'] = __( 'Withdraw', 'academy' );
	}

	\Academy\Helper::get_template(
		'frontend-dashboard/pages/partials/sub-menu.php',
		[
			'menu' => apply_filters( 'academy/templates/frontend-dashboard/settings-content-menu', $menu_items )
		]
	);

	$academy_cover_photo = esc_html( get_user_meta( $user_id, 'academy_cover_photo', true ) );

	?>

<div id="tab-panel-1-profile-view" role="tabpanel" aria-labelledby="tab-panel-1-profile" class="components-tab-panel__tab-content">
	<div class="academy-tab-content">
		<div class="academy-dashboard-settings__profile-form">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'academy_nonce', 'security' ); ?>
				<input type="hidden" name="action" value="academy/save_frontend_dashboard_edit_profile_settings">
				<div class="academy-form-banner">
					<input name="academy_profile_photo" type="hidden" id="academy_profile_photo" value="">
					<input name="academy_cover_photo" type="hidden" id="academy_cover_photo" value="">
					<div class="academy-cover-photo" style="background-image: url(&quot;<?php echo esc_url( ! empty( $academy_cover_photo ) ? $academy_cover_photo : ACADEMY_ASSETS_URI . 'images/banner.jpg' ); ?>&quot;);">
						<button type="button" class="academy-delete-cover-photo">
							<i class="academy-icon academy-icon--trash" aria-hidden="true"></i>
						</button>
						<button type="button" class="academy-upload-cover-photo"><i class="academy-icon academy-icon--camera" aria-hidden="true"></i><?php echo esc_html__( 'Upload Cover Photo', 'academy' ); ?></button>
						<input type="hidden" id="academy-cover-photo-url" name="academy-cover-photo-url" value="">
						<div class="academy-profile-photo" style="background-image: url(&quot;<?php echo esc_url( get_user_meta( $user_id, 'academy_profile_photo', true ) ); ?>&quot;);">
							<button type="button" class="academy-upload-profile-photo"><i class="academy-icon academy-icon--camera" aria-hidden="true"></i></button>
							<input type="hidden" id="academy-profile-photo-url" name="academy-profile-photo-url" value="">
						</div>
					</div>
				</div>
				<div class="academy-dashboard-info">
					<i class="academy-icon academy--info-circle" aria-hidden="true"></i> 
					<?php
						echo esc_html__( 'Profile Photo Size: 200x200 pixels', 'academy' );
					?>

					<?php
						echo esc_html__( 'Cover Photo Size: 1200x450 pixels', 'academy' );
					?>
				</div>
				<div class="academy-form-group">
					<div>
						<label for="first_name"><?php esc_html_e( 'First Name', 'academy' ); ?></label>
						<input name="first_name" id="first_name" placeholder="" value="<?php echo esc_attr( get_user_meta( $user_id, 'first_name', true ) ); ?>">
					</div>
					<div>
						<label for="last_name"><?php esc_html_e( 'Last Name', 'academy' ); ?></label>
						<input name="last_name" id="last_name" placeholder="" value="<?php echo esc_attr( get_user_meta( $user_id, 'last_name', true ) ); ?>">
					</div>
				</div>
				<div class="academy-form-group">
					<div>
						<label for="designation"><?php esc_html_e( 'Designation', 'academy' ); ?></label>
						<input name="academy_profile_designation" id="designation" placeholder="" value="<?php echo esc_attr( get_user_meta( $user_id, 'academy_profile_designation', true ) ); ?>">
					</div>
					<div>
						<label for="phone_number"><?php esc_html_e( 'Phone Number', 'academy' ); ?></label>
						<input name="academy_phone_number" id="phone_number" placeholder="" value="<?php echo esc_attr( get_user_meta( $user_id, 'academy_phone_number', true ) ); ?>">
					</div>
				</div>
				<div class="academy-form-block">
				<label for="bio"><?php esc_html_e( 'Bio', 'academy' ); ?></label>
				<textarea name="academy_profile_bio" id="bio" placeholder=""><?php echo esc_html( get_user_meta( $user_id, 'academy_profile_bio', true ) ); ?></textarea>
				</div>
				<div class="academy-form-block">
					<label for="website_url"><?php esc_html_e( 'Website URL', 'academy' ); ?></label>
					<input name="academy_website_url" id="website_url" type="url" placeholder="" value="<?php echo esc_attr( get_user_meta( $user_id, 'academy_website_url', true ) ); ?>">
				</div>
				<div class="academy-form-block">
					<label for="github_url"><?php esc_html_e( 'Github URL', 'academy' ); ?></label>
					<input name="academy_github_url" id="github_url" type="url" placeholder="" value="<?php echo esc_attr( get_user_meta( $user_id, 'academy_github_url', true ) ); ?>">
				</div>
				<div class="academy-form-block">
					<label for="facebook_url"><?php esc_html_e( 'Facebook URL', 'academy' ); ?></label>
					<input name="academy_facebook_url" id="facebook_url" type="url" placeholder="" value="<?php echo esc_attr( get_user_meta( $user_id, 'academy_facebook_url', true ) ); ?>">
				</div>
				<div class="academy-form-block">
					<label for="twitter_url"><?php esc_html_e( 'Twitter URL', 'academy' ); ?></label>
					<input name="academy_twitter_url" id="twitter_url" type="url" placeholder="" value="<?php echo esc_attr( get_user_meta( $user_id, 'academy_twitter_url', true ) ); ?>">
				</div>
				<div class="academy-form-block">
					<label for="linkedin_url"><?php esc_html_e( 'LinkedIn URL', 'academy' ); ?></label>
					<input name="academy_linkedin_url" id="linkedin_url" type="url" placeholder="" value="<?php echo esc_attr( get_user_meta( $user_id, 'academy_linkedin_url', true ) ); ?>">
				</div>
				<br />
				<input class="academy-btn academy-btn--bg-purple" type="submit" value="<?php echo esc_html__( 'Save Settings', 'academy' ); ?>">
			</form>
		</div>
	</div>
</div>
