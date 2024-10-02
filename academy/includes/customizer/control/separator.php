<?php
namespace Academy\Customizer\Control;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Separator extends \WP_Customize_Control {
	public $type = 'academy_separator';
	public function render_content() {
		?>
		<div class="academy-customize-separator-control">
			<h4 class="academy-customize-separator-control__heading"><?php echo esc_html( $this->label ); ?></h4>
			<?php
			if ( ! empty( $this->description ) ) :
				?>
			<p class="academy-customize-separator-control__info"><?php echo wp_kses_post( $this->description ); ?></p>
				<?php
				endif;
			?>
		</div>
		<?php
	}
}
