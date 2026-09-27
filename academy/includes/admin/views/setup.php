<?php
namespace Academy\Admin\Views;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<title><?php echo esc_html__( 'Academy Setup Wizard', 'academy' ); ?></title>
		<base target="_parent">
		<?php do_action( 'admin_print_styles' ); ?>
	</head>
	<body>
		<div id="academysetupscreenwrap" class="academysetupscreenwrap">
			<?php
				$preloader = apply_filters( 'academy/preloader', academy_get_preloader_html() );
				echo wp_kses_post( $preloader );
			?>
		</div>
		<?php do_action( 'admin_print_footer_scripts' ); ?>
	</body>
</html>
