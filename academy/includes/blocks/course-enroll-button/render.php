<?php
/**
 * Course Button block.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Helper as AcademyHelper;

$academy_course = \Academy\Blocks\CourseData::get( isset( $block->context['postId'] ) ? $block->context['postId'] : get_the_ID() );
if ( ! $academy_course ) {
	return;
}

// Enroll mode: Academy's own enroll, add-to-cart or membership form for this course.
if ( 'enroll' === $attributes['action'] && 'course' === $academy_course['type'] ) {
	$academy_id        = (int) $academy_course['id'];
	$academy_is_paid   = AcademyHelper::is_course_purchasable( $academy_id );
	$academy_engine    = AcademyHelper::monetization_engine();
	$academy_form_args = array(
		'is_paid'         => $academy_is_paid,
		'course_type'     => AcademyHelper::get_course_type( $academy_id ),
		'product_id'      => $academy_is_paid ? AcademyHelper::get_course_product_id( $academy_id ) : 0,
		'download_id'     => $academy_is_paid ? get_post_meta( $academy_id, 'academy_course_download_id', true ) : 0,
		'required_levels' => '',
		'engine'          => $academy_engine,
	);
	if ( 'paid-memberships-pro' === $academy_engine && class_exists( '\AcademyProPaidMembershipsPro\Helper' ) ) {
		$academy_levels = \AcademyProPaidMembershipsPro\Helper::has_course_access( $academy_id );
		if ( is_array( $academy_levels ) && $academy_levels ) {
			$academy_form_args['required_levels'] = $academy_levels;
		}
	}

	\Academy\Blocks::use_course_assets();

	global $post;
	$academy_previous = $post;
	$post             = get_post( $academy_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restored below.
	setup_postdata( $post );

	// The form template adds an author row for the "layout two" card; this block is only the form.
	$academy_settings  = isset( $GLOBALS['academy_settings'] ) && is_object( $GLOBALS['academy_settings'] ) ? $GLOBALS['academy_settings'] : null;
	$academy_card_type = $academy_settings && isset( $academy_settings->course_card_style ) ? $academy_settings->course_card_style : null;
	if ( $academy_settings ) {
		$academy_settings->course_card_style = 'default';
	}

	ob_start();
	AcademyHelper::get_template( 'loop/footer-form.php', apply_filters( 'academy/template/loop/footer_form', $academy_form_args, $academy_id ) );
	$academy_form = ob_get_clean();

	if ( $academy_settings ) {
		$academy_settings->course_card_style = $academy_card_type;
	}
	$post = $academy_previous; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring.
	if ( $academy_previous ) {
		setup_postdata( $academy_previous );
	}

	if ( '' !== trim( $academy_form ) ) {
		printf( '<div %1$s>%2$s</div>', get_block_wrapper_attributes( array( 'class' => 'is-enroll-form' ) ), $academy_form ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core and the template.
	}
	return;
}//end if

$academy_mode = 'view';
if ( ! empty( $attributes['showContinue'] ) && $academy_course['continue_url'] ) {
	if ( $academy_course['is_complete'] ) {
		$academy_mode = 'complete';
	} elseif ( $academy_course['is_enrolled'] ) {
		$academy_mode = 'continue';
	} elseif ( $academy_course['is_public'] ) {
		$academy_mode = 'start';
	}
}

switch ( $academy_mode ) {
	case 'complete':
		$academy_text = ! empty( $attributes['completeText'] ) ? $attributes['completeText'] : __( 'Review course', 'academy' );
		$academy_url  = $academy_course['continue_url'];
		break;
	case 'continue':
		$academy_text = ! empty( $attributes['continueText'] ) ? $attributes['continueText'] : __( 'Continue learning', 'academy' );
		$academy_url  = $academy_course['continue_url'];
		break;
	case 'start':
		$academy_text = __( 'Start learning', 'academy' );
		$academy_url  = $academy_course['continue_url'];
		break;
	default:
		$academy_text = ! empty( $attributes['text'] ) ? $attributes['text'] : __( 'View course', 'academy' );
		$academy_url  = $academy_course['url'];
}

$academy_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'wp-element-button is-' . $academy_mode . ( 'full' === $attributes['width'] ? ' is-full-width' : '' ),
		'href'  => esc_url( $academy_url ),
	)
);
?>
<a <?php echo $academy_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core. ?>><?php echo esc_html( wp_strip_all_tags( $academy_text ) ); ?></a>
