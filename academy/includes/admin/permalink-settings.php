<?php
namespace Academy\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class PermalinkSettings {

	/**
	 * Permalink settings.
	 *
	 * @var array
	 */
	private $permalinks = array();

	public static function init() {
		$self = new self();
		$self->settings_init();
		$self->settings_save();
	}

	/**
	 * Init our settings.
	 */
	public function settings_init() {
		\add_settings_section( 'academy-permalink', __( 'Academy Course permalinks', 'academy' ), array( $this, 'settings' ), 'permalink' );

		\add_settings_field(
			'academy_course_category_slug',
			__( 'Course category base', 'academy' ),
			array( $this, 'course_category_slug_input' ),
			'permalink',
			'optional'
		);
		\add_settings_field(
			'academy_course_tag_slug',
			__( 'Course tag base', 'academy' ),
			array( $this, 'course_tag_slug_input' ),
			'permalink',
			'optional'
		);
		$this->permalinks = \Academy\Helper::get_permalink_structure();
	}

	/**
	 * Show a slug input box.
	 */
	public function course_category_slug_input() {
		?>
		<input name="academy_course_category_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $this->permalinks['category_base'] ); ?>" placeholder="<?php echo esc_attr_x( 'course-category', 'slug', 'academy' ); ?>" />
		<?php
	}

	/**
	 * Show a slug input box.
	 */
	public function course_tag_slug_input() {
		?>
		<input name="academy_course_tag_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $this->permalinks['tag_base'] ); ?>" placeholder="<?php echo esc_attr_x( 'course-tag', 'slug', 'academy' ); ?>" />
		<?php
	}

	/**
	 * Show the settings.
	 */
	public function settings() {
		/* translators: %s: Home URL */
		echo wp_kses_post( wpautop( sprintf( __( 'If you like, you may enter custom structures for your course URLs here. For example, using <code>course</code> would make your course links like <code>%scourse/sample-course/</code>. This setting affects course URLs only, not things such as course categories.', 'academy' ), esc_url( home_url( '/' ) ) ) ) );

		$courses_page_id = (int) \Academy\Helper::get_settings( 'course_page' );
		$base_slug    = urldecode( ( $courses_page_id > 0 && get_post( $courses_page_id ) ) ? get_page_uri( $courses_page_id ) : _x( 'courses', 'default-slug', 'academy' ) );
		$course_base = _x( 'course', 'default-slug', 'academy' );

		$structures = array(
			0 => '',
			1 => '/' . trailingslashit( $base_slug ),
			2 => '/' . trailingslashit( $base_slug ) . trailingslashit( '%course_category%' ),
		);

		?>
		<table class="form-table academy-permalink-structure">
			<tbody>
				<tr>
					<th><label><input name="course_permalink" type="radio" value="<?php echo esc_attr( $structures[0] ); ?>" class="academytog" <?php checked( $structures[0], $this->permalinks['course_base'] ); ?> /> <?php esc_html_e( 'Default', 'academy' ); ?></label></th>
					<td><code class="default-example"><?php echo esc_html( home_url() ); ?>/?course=sample-course</code> <code class="non-default-example"><?php echo esc_html( home_url() ); ?>/<?php echo esc_html( $course_base ); ?>/sample-course/</code></td>
				</tr>
				<?php if ( $courses_page_id ) : ?>
					<tr>
						<th><label><input name="course_permalink" type="radio" value="<?php echo esc_attr( $structures[1] ); ?>" class="academytog" <?php checked( $structures[1], $this->permalinks['course_base'] ); ?> /> <?php esc_html_e( 'Courses base', 'academy' ); ?></label></th>
						<td><code><?php echo esc_html( home_url() ); ?>/<?php echo esc_html( $base_slug ); ?>/sample-course/</code></td>
					</tr>
				<?php endif; ?>
				<tr>
					<th><label><input name="course_permalink" id="academy_custom_selection" type="radio" value="custom" class="tog" <?php checked( in_array( $this->permalinks['course_base'], $structures, true ), false ); ?> />
						<?php esc_html_e( 'Custom base', 'academy' ); ?></label></th>
					<td>
						<input name="course_permalink_structure" id="academy_permalink_structure" type="text" value="<?php echo esc_attr( $this->permalinks['course_base'] ? trailingslashit( $this->permalinks['course_base'] ) : '' ); ?>" class="regular-text code"> <span class="description"><?php esc_html_e( 'Enter a custom base to use. A base must be set or WordPress will use default instead.', 'academy' ); ?></span>
					</td>
				</tr>
			</tbody>
		</table>
		<?php wp_nonce_field( 'academy-permalinks', 'academy-permalinks-nonce' ); ?>
		<script type="text/javascript">
			jQuery( function() {
				jQuery('input.academytog').on( 'change', function() {
					jQuery('#academy_permalink_structure').val( jQuery( this ).val() );
				});
				jQuery('.permalink-structure input').on( 'change', function() {
					jQuery('.academy-permalink-structure').find('code.non-default-example, code.default-example').hide();
					if ( jQuery(this).val() ) {
						jQuery('.academy-permalink-structure code.non-default-example').show();
						jQuery('.academy-permalink-structure input').prop('disabled', false);
					} else {
						jQuery('.academy-permalink-structure code.default-example').show();
						jQuery('.academy-permalink-structure input:eq(0)').trigger( 'click' );
						jQuery('.academy-permalink-structure input').attr('disabled', 'disabled');
					}
				});
				jQuery('.permalink-structure input:checked').trigger( 'change' );
				jQuery('#academy_permalink_structure').on( 'focus', function(){
					jQuery('#academy_custom_selection').trigger( 'click' );
				} );
			} );
		</script>
		<?php
	}

	/**
	 * Save the settings.
	 */
	public function settings_save() {
		if ( ! is_admin() ) {
			return;
		}

		// We need to save the options ourselves; settings api does not trigger save for the permalinks page.
		if ( isset( $_POST['permalink_structure'], $_POST['academy-permalinks-nonce'], $_POST['academy_course_category_slug'], $_POST['academy_course_tag_slug'] ) && wp_verify_nonce( wp_unslash( $_POST['academy-permalinks-nonce'] ), 'academy-permalinks' ) ) { // WPCS: input var ok, sanitization ok.

			$permalinks                   = (array) get_option( 'academy_permalinks', array() );
			$permalinks['category_base']  = \Academy\Helper::sanitize_permalink( wp_unslash( $_POST['academy_course_category_slug'] ) ); // WPCS: input var ok, sanitization ok.
			$permalinks['tag_base']       = \Academy\Helper::sanitize_permalink( wp_unslash( $_POST['academy_course_tag_slug'] ) ); // WPCS: input var ok, sanitization ok.

			// Generate course base.
			$course_base = isset( $_POST['course_permalink'] ) ? sanitize_text_field( wp_unslash( $_POST['course_permalink'] ) ) : ''; // WPCS: input var ok, sanitization ok.

			if ( 'custom' === $course_base ) {
				if ( isset( $_POST['course_permalink_structure'] ) ) { // WPCS: input var ok.
					$course_base = preg_replace( '#/+#', '/', '/' . str_replace( '#', '', trim( wp_unslash( $_POST['course_permalink_structure'] ) ) ) ); // WPCS: input var ok, sanitization ok.
				} else {
					$course_base = '/';
				}

				// This is an invalid base structure and breaks pages.
				if ( '/%course_category%/' === trailingslashit( $course_base ) ) {
					$course_base = '/' . _x( 'course', 'slug', 'academy' ) . $course_base;
				}
			} elseif ( empty( $course_base ) ) {
				$course_base = _x( 'course', 'slug', 'academy' );
			}

			$permalinks['course_base'] = \Academy\Helper::sanitize_permalink( $course_base );

			// Shop base may require verbose page rules if nesting pages.
			$courses_page_id = (int) \Academy\Helper::get_settings( 'course_page' );
			$courses_permalink = ( $courses_page_id > 0 && get_post( $courses_page_id ) ) ? get_page_uri( $courses_page_id ) : _x( 'courses', 'default-slug', 'academy' );

			if ( $courses_page_id && stristr( trim( $permalinks['course_base'], '/' ), $courses_permalink ) ) {
				$permalinks['use_verbose_page_rules'] = true;
			}

			update_option( 'academy_permalinks', $permalinks );
		}//end if
	}

}
