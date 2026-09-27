<?php
namespace AcademyCertificates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Assets {
	/**
	 * Certificate builder fonts. Family name (matching the editor manifest and
	 * mPDF's registered keys) => TTF filename under the downloaded ttfonts dir.
	 */
	const BUILDER_FONTS = array(
		'Poppins'           => 'Poppins-Regular.ttf',
		'Roboto'            => 'Roboto-Regular.ttf',
		'Lora'              => 'Lora-VariableFont_wght.ttf',
		'DM Sans'           => 'DMSans-Regular.ttf',
		'Libre Baskerville' => 'LibreBaskerville-Regular.ttf',
		'Cinzel'            => 'Cinzel-VariableFont_wght.ttf',
		'Great Vibes'       => 'GreatVibes-Regular.ttf',
		'Alex Brush'        => 'AlexBrush-Regular.ttf',
		'Allura'            => 'Allura-Regular.ttf',
		'Abhaya Libre'      => 'AbhayaLibre-Regular.ttf',
	);

	public static function init() {
		$self = new self();
		add_filter( 'ablocks/assets/editor_scripts_data', array( $self, 'add_academy_certificate_default_image' ) );
		add_filter( 'academy/assets/backend_scripts_data', array( $self, 'add_scripts_data' ) );
		add_action( 'admin_enqueue_scripts', array( $self, 'enqueue_builder_assets' ) );
	}

	/**
	 * On the certificate builder page: register the same fonts the PDF uses as
	 * font-face rules so the canvas is WYSIWYG, and load the media library for
	 * the image/background pickers.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_builder_assets( $hook ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page check, no state change.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'academy-certificates' !== $page ) {
			return;
		}

		wp_enqueue_media();

		$css = $this->builder_fontface_css();
		if ( '' === $css ) {
			return;
		}
		wp_register_style( 'academy-certificate-fonts', false, array(), ACADEMY_CERTIFICATE_VERSION );
		wp_enqueue_style( 'academy-certificate-fonts' );
		wp_add_inline_style( 'academy-certificate-fonts', $css );
	}

	/** Build @font-face rules for the downloaded TTFs that exist on disk. */
	protected function builder_fontface_css() {
		$upload  = wp_upload_dir();
		$dir     = trailingslashit( $upload['basedir'] ) . 'academy_uploads/mpdf/ttfonts/';
		$dir_url = trailingslashit( $upload['baseurl'] ) . 'academy_uploads/mpdf/ttfonts/';
		$css     = '';

		foreach ( self::BUILDER_FONTS as $family => $file ) {
			if ( ! file_exists( $dir . $file ) ) {
				continue;
			}
			$css .= sprintf(
				"@font-face{font-family:'%s';src:url('%s') format('truetype');font-display:swap;}",
				$family,
				esc_url( $dir_url . $file )
			);
		}
		return $css;
	}

	public function add_academy_certificate_default_image( $script_data ) {
		$certificate_image_array = array(
			'landscape' => array(
				'cert_1' => ACADEMY_ASSETS_URI . 'images/certificate/certificate-1.png',
				'cert_2' => ACADEMY_ASSETS_URI . 'images/certificate/certificate-2.png',
				'cert_3' => ACADEMY_ASSETS_URI . 'images/certificate/certificate-3.png',
				'cert_4' => ACADEMY_ASSETS_URI . 'images/certificate/certificate-4.png',
				'cert_5' => ACADEMY_ASSETS_URI . 'images/certificate/certificate-5.png',
				'cert_6' => ACADEMY_ASSETS_URI . 'images/certificate/certificate-6.png',
				'cert_7' => ACADEMY_ASSETS_URI . 'images/certificate/certificate-7.png',
				'custom_image' => ACADEMY_ASSETS_URI . 'images/certificate/place-holder.png',
			),
			'protrait' => array(
				'cert_1' => ACADEMY_ASSETS_URI . 'images/certificate/protrait-1.png',
				'cert_2' => ACADEMY_ASSETS_URI . 'images/certificate/protrait-2.png',
				'cert_3' => ACADEMY_ASSETS_URI . 'images/certificate/protrait-3.png',
				'cert_4' => ACADEMY_ASSETS_URI . 'images/certificate/protrait-4.png',
				'cert_5' => ACADEMY_ASSETS_URI . 'images/certificate/protrait-5-5.png',
				'cert_6' => ACADEMY_ASSETS_URI . 'images/certificate/protrait-6-6.png',
				'cert_7' => ACADEMY_ASSETS_URI . 'images/certificate/protrait-7-7.png',
				'custom_image' => ACADEMY_ASSETS_URI . 'images/certificate/place-holder.png',
			),
		);
		if ( ! isset( $script_data['certificate_image'] ) ) {
			$script_data['certificate_image'] = $certificate_image_array;
		}
		return $script_data;
	}

	public function add_scripts_data( array $data ): array {
		return array_merge( $data, [
			'certificates' => [
				'fonts_downloaded' => (bool) get_option( 'academy_mpdf_fonts_downloaded', false ),
				'presets'          => $this->get_presets(),
				// The builder's font picker offers exactly what the PDF can embed.
				'fonts'            => array_keys( self::BUILDER_FONTS ),
			],
		] );
	}

	/**
	 * Certificate background presets — the bundled decorative backgrounds, used
	 * by the "new certificate" modal to start a design from a ready page.
	 */
	public function get_presets() {
		$base = ACADEMY_ASSETS_URI . 'images/certificate/';

		$sets = array(
			'landscape' => array(
				'certificate-1.png',
				'certificate-2.png',
				'certificate-3.png',
				'certificate-4.png',
				'certificate-5.png',
				'certificate-6.png',
				'certificate-7.png',
			),
			'portrait'  => array(
				'protrait-1.png',
				'protrait-2.png',
				'protrait-3.png',
				'protrait-4.png',
				'protrait-5-5.png',
				'protrait-6-6.png',
				'protrait-7-7.png',
			),
		);

		$presets = array();
		foreach ( $sets as $orientation => $files ) {
			foreach ( $files as $i => $file ) {
				$presets[] = array(
					'id'          => $orientation[0] . ( $i + 1 ), // l1..l7 / p1..p7
					/* translators: %d: preset number. */
					'name'        => sprintf( 'landscape' === $orientation ? __( 'Landscape %d', 'academy' ) : __( 'Portrait %d', 'academy' ), $i + 1 ),
					'orientation' => $orientation,
					'image'       => $base . $file,
				);
			}
		}

		return apply_filters( 'academy_certificates/builder_presets', $presets );
	}
}
