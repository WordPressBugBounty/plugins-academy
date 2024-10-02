<?php
namespace Academy\Customizer\Control;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Tab extends \WP_Customize_Control {
	public $type = 'academy_tab';
	public function render_content() {
		?>
			<div class="academy-customize-tab-control">
				<button type="button" class="academy-customize-tab-general selected"><?php esc_html_e( 'General', 'academy' ); ?></a>
				<button type="button" class="academy-customize-tab-design"><?php esc_html_e( 'Design', 'academy' ); ?></a>
			</div>
		<?php
	}
}
