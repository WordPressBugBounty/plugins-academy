<?php
namespace  Academy\Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AcademyPDF {

	public function __construct() {
		add_shortcode( 'academy_pdf', array( $this, 'render_shortcode' ) );
	}
	public function render_shortcode( $atts, $content = '' ) {
        // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract(shortcode_atts(array(
			'src'    => '',
			'width'  => '100%',
			'height' => '500px',
		), $atts));

		ob_start();
		?>
		<div class="academy-pdf-embedder">
			<iframe class="academy-pdf-embedder__iframe" src="<?php echo esc_url( $src ); ?>" width="<?php echo esc_attr( $width ); ?>" height="<?php echo esc_attr( $height ); ?>"></iframe>
		</div>
		<?php
		return apply_filters( 'academy/templates/shortcode/pdf', ob_get_clean() );
	}

}
