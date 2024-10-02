<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue Media Scripts
 */
wp_enqueue_media();
?>
<div class="academy-extend-user-profile-wrap">
	<h2><?php esc_html_e( 'Academy Info', 'academy' ); ?></h2>
	<table class="form-table">
		<tr class="user-designation-wrap">
			<th><label for="academy_profile_designation"><?php esc_html_e( 'Profile Designation', 'academy' ); ?></label></th>
			<td>
				<input type="text" name="academy_profile_designation" id="academy_profile_designation" value="<?php echo esc_attr( get_user_meta( $user->ID, 'academy_profile_designation', true ) ); ?>" class="regular-text" />
			</td>
		</tr>
		<tr class="user-description-wrap">
			<th><label for="description"><?php esc_html_e( 'Profile Bio', 'academy' ); ?></label></th>
			<td>
				<?php
				wp_editor(
					get_user_meta( $user->ID, 'academy_profile_bio', true ),
					'academy_profile_bio',
					array(
						'teeny'         => true,
						'media_buttons' => false,
						'quicktags'     => false,
						'editor_height' => 200,
					)
				);
				?>
				<p class="description"><?php esc_html_e( 'Share a little biographical information to fill out your profile. This may be shown publicly.', 'academy' ); ?></p>
			</td>
		</tr>

		<tr class="user-photo-wrap">
			<th><label for="description"><?php esc_html_e( 'Profile Photo', 'academy' ); ?></label></th>
			<td>
				<div class="video-wrap">
					<p class="video-img" style="max-width: 300px;">
						<?php
							$profile_photo = get_user_meta( $user->ID, 'academy_profile_photo', true );
						if ( $profile_photo ) {
							echo '<img src="' . esc_url( $profile_photo ) . '" alt="" style="max-width:100%" /> ';
						}
						?>
					</p>
					<input type="hidden" id="academy_profile_photo" name="academy_profile_photo" value="<?php echo esc_attr( $profile_photo ); ?>">
					<button type="button" class="academy_profile_photo_remove_btn button button-primary <?php echo( empty( $profile_photo ) ? 'hidden' : '' ); ?>"><?php esc_html_e( 'Remove this image', 'academy' ); ?></button>
					<button type="button" class="academy_profile_photo_upload_btn button button-primary <?php echo( ! empty( $profile_photo ) ? 'hidden' : '' ); ?>"><?php esc_html_e( 'Upload', 'academy' ); ?></button>
				</div>
			</td>
		</tr>
	</table>
</div>
<script>
jQuery(document).ready(function () {
	if (jQuery('#academy_profile_photo').length) {
		imageUploader();
	}

	function imageUploader() {
		// Set all variables to be used in scope
		var frame,
		addImgLink = jQuery('.academy_profile_photo_upload_btn'),
		delImgLink = jQuery('.academy_profile_photo_remove_btn'),
		imgContainer = jQuery('.academy-extend-user-profile-wrap .video-wrap .video-img'),
		imgIdInput = jQuery('#academy_profile_photo');

		// ADD IMAGE LINK
		addImgLink.on('click', function (event) {
			event.preventDefault();
			event.stopPropagation(); // Prevent event bubbling

			// If the media frame already exists, reopen it.
			if (frame) {
				frame.open();
				return;
			}

			// Create a new media frame
			frame = wp.media({
				title: 'Select or Upload Media',
				button: {
					text: 'Use this media',
				},
				multiple: false, // Set to true to allow multiple files to be selected
			});

			// When an image is selected in the media frame...
			frame.on('select', function () {
				// Get media attachment details from the frame state
				var attachment = frame.state().get('selection').first().toJSON();
				// Send the attachment URL to our custom image input field.
				imgContainer.append('<img src="' + attachment.url + '" alt="" style="max-width:100%;"/>');
				// Send the attachment id to our hidden input
				imgIdInput.val(attachment.url);
				// Hide the add image link
				addImgLink.addClass('hidden');
				// Unhide the remove image link
				delImgLink.removeClass('hidden');
			});
			// Finally, open the modal on click
			frame.open();
		});

		// DELETE IMAGE LINK
		delImgLink.on('click', function (event) {
			event.preventDefault();
			event.stopPropagation(); // Prevent event bubbling

			// Clear out the preview image
			imgContainer.html('');
			// Un-hide the add image link
			addImgLink.removeClass('hidden');
			// Hide the delete image link
			delImgLink.addClass('hidden');
			// Delete the image id from the hidden input
			imgIdInput.val('');
		});
	}
});

</script>
