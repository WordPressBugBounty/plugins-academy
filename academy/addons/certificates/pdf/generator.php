<?php
namespace AcademyCertificates\PDF;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Mpdf\HTMLParserMode;
use Academy\Mpdf\Mpdf;
use Academy\Mpdf\Output\Destination;
use Academy\Classes\FileUpload;

class Generator extends FileUpload {
	public $mpdf;
	protected $course_id;
	protected $student_id;
	protected $template;
	protected $styles = array();
	protected $html = array();
	protected $preview = false;
	protected $page_size;
	protected $page_orientation;
	public function __construct( $course_id, $student_id, $template, $styles, $pageSize, $pageOrientation ) {
		$this->course_id = $course_id;
		$this->student_id = $student_id;
		$this->template = $template;
		$this->styles = $styles;
		$this->page_size = $pageSize;
		$this->page_orientation = $pageOrientation;
	}

	public function init_mpdf() {
		if ( $this->mpdf instanceof Mpdf ) {
			return;
		}

		$upload_dir = wp_upload_dir();

		$font_dirs   = ( new \Academy\Mpdf\Config\ConfigVariables() )->getDefaults()['fontDir'];
		$font_dirs[] = $upload_dir['basedir'] . '/academy/certificate-fonts';

		$default_font_config = ( new \Academy\Mpdf\Config\FontVariables() )->getDefaults();
		$fontdata            = $default_font_config['fontdata'];

		$this->mpdf = new Mpdf(
			array(
				'tempDir'          => $this->get_upload_dir() . '/mpdf',
				'fontDir'          => $font_dirs,
				'format'           => $this->page_size,
				'orientation'      => $this->page_orientation,
				'margin_left'      => 0,
				'margin_right'     => 0,
				'margin_top'       => 0,
				'margin_bottom'    => 0,
				'default_font'     => 'Arial, sans-serif',
				'autoScriptToLang' => true,
				'autoLangToFont'   => true,
				'fontdata'         => $fontdata + array(
					'cinzel'              => array(
						'R' => 'Cinzel-VariableFont_wght.ttf',
					),
					'dejavusanscondensed' => array(
						'R' => 'DejaVuSansCondensed.ttf',
						'B' => 'DejaVuSansCondensed-Bold.ttf',
					),
					'dmsans'              => array(
						'R' => 'DMSans-Regular.ttf',
						'B' => 'DMSans-Bold.ttf',
						'I' => 'DMSans-Italic.ttf',
					),
					'greatvibes'          => array(
						'R' => 'GreatVibes-Regular.ttf',
					),
					'grenzegotisch'       => array(
						'R' => 'GrenzeGotisch-VariableFont_wght.ttf',
					),
					'librebaskerville'    => array(
						'R' => 'LibreBaskerville-Regular.ttf',
						'B' => 'LibreBaskerville-Bold.ttf',
						'I' => 'LibreBaskerville-Italic.ttf',
					),
					'lora'                => array(
						'R' => 'Lora-VariableFont_wght.ttf',
						'I' => 'Lora-Italic-VariableFont_wght.ttf',
					),
					'poppins'             => array(
						'R' => 'Poppins-Regular.ttf',
						'B' => 'Poppins-Bold.ttf',
						'I' => 'Poppins-Italic.ttf',
					),
					'roboto'              => array(
						'R' => 'Roboto-Regular.ttf',
						'B' => 'Roboto-Bold.ttf',
						'I' => 'Roboto-Italic.ttf',
					),
					'abhayalibre'         => array(
						'R' => 'AbhayaLibre-Regular.ttf',
						'B' => 'AbhayaLibre-Bold.ttf',
					),
					'adinekirnberg'       => array(
						'R' => 'AdineKirnberg.ttf',
					),
					'alexbrush'           => array(
						'R' => 'AlexBrush-Regular.ttf',
					),
					'allura'              => array(
						'R' => 'Allura-Regular.ttf',
					),
				),
			)
		);
		$this->mpdf->setMBencoding( 'UTF-8' );

	}

	public function prepare_pdf() {
		$this->init_mpdf();

		$template = $this->template;

		$this->mpdf->WriteHTML( $this->styles, HTMLParserMode::HEADER_CSS );
		$this->mpdf->WriteHTML( $this->custom_default_css(), HTMLParserMode::HEADER_CSS );
		$this->mpdf->WriteHTML( $template );
	}

	public function custom_default_css() {
		$file_path = ACADEMY_ADDONS_DIR_PATH . '/certificates/assets/css/gutenberg-styles.css';
		$css = '';
		if ( file_exists( $file_path ) && is_readable( $file_path ) ) {
			$css = file_get_contents( $file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		} else {
			$css = esc_html__( "The File doesn't Exist", 'academy' );
		}

		return $css;
	}

	public function preview_certificate() {
		$result = $this->prepare_pdf( true );

		$this->mpdf->Output( 'test.pdf', Destination::INLINE );
		die;
	}

}
