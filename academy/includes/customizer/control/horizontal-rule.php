<?php
namespace Academy\Customizer\Control;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class HorizontalRule extends \WP_Customize_Control {
	public $type = 'academy_horizontal_rule';
	public function render_content() {
		?>
		<div class="academy-customize-horizontal-rule-control">
			<hr />
		</div>
		<?php
	}
}
