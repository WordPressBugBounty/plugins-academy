<?php
/**
 * Course Attachments block. Renders the [academy_single_course_attachment_files] section for the course in context.
 *
 * @var WP_Block $block Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$academy_html = \Academy\Blocks::render_course_section(
	'academy_single_course_attachment_files',
	$block,
	// The editor preview: the course's files as a student sees them, or
	// example files (and why) when there are none to show yet.
	static function ( $course_id ) {
		$files = array_filter( array_map( 'absint', (array) get_post_meta( $course_id, 'academy_course_attachments', true ) ) );
		$ready = \Academy\Helper::is_active_academy_pro() && \Academy\Helper::get_addon_active_status( 'multimedia-attachment' );
		if ( $ready && $files ) {
			\AcademyPro\Helper::get_template( 'multimedia-attachment/attachment-file.php', [ 'attachments' => $files ] );
			return;
		}

		if ( ! \Academy\Helper::is_active_academy_pro() ) {
			$note = __( 'Example files. Course attachments come with Academy Pro.', 'academy' );
		} elseif ( ! \Academy\Helper::get_addon_active_status( 'multimedia-attachment' ) ) {
			$note = __( 'Example files. Turn on the Multimedia Attachment add-on to show the course’s own files.', 'academy' );
		} else {
			$note = __( 'Example files. Add attachments to the course and enrolled students see them here.', 'academy' );
		}
		$examples = [
			[ __( 'Course workbook.pdf', 'academy' ), '2.4 MB' ],
			[ __( 'Starter files.zip', 'academy' ), '18.1 MB' ],
			[ __( 'Cheat sheet.png', 'academy' ), '640 KB' ],
		];
		?>
		<div class="academy-single-course__content-item academy-single-course__content-item--attachments">
			<h4 class="academy-single-course__attachments-title"><?php esc_html_e( 'Attachments Source', 'academy' ); ?></h4>
			<p class="academy-attachments-preview-note"><?php echo esc_html( $note ); ?></p>
			<div class="academy-single-course__attachment">
				<?php foreach ( $examples as $academy_example ) : ?>
					<div class="academy-single-course__attachment-list">
						<span class="academy-resource academy-attachment-file academy-single-course__attachment-download">
							<span class="academy-attachments-preview-file">
								<svg class="academy-attachments-preview-icon" viewBox="0 0 24 24" width="26" height="26" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z M14 3v5h5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
								<span class="academy-single-course__attachment-details">
									<span class="academy-single-course__attachment-name"><?php echo esc_html( $academy_example[0] ); ?></span>
									<span class="academy-single-course__attachment-size">
										<?php
										/* translators: %s: file size. */
										echo esc_html( sprintf( __( 'Size: %s', 'academy' ), $academy_example[1] ) );
										?>
									</span>
								</span>
							</span>
						</span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
);

echo $academy_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the shortcode templates and core.
