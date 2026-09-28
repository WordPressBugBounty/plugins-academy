<?php
/**
 * Registration Form: a sign-up form built from Form Field blocks.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    The fields.
 * @var WP_Block $block      Block.
 *
 * @package Academy
 */

use Academy\RegistrationForms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$academy_role    = RegistrationForms::role( $attributes );
$academy_preview = defined( 'REST_REQUEST' ) && REST_REQUEST;

// Signed in already: the same message the classic form shows.
if ( is_user_logged_in() && ! $academy_preview ) {
	$academy_dashboard = get_permalink( (int) \Academy\Helper::get_settings( 'frontend_dashboard_page' ) );
	ob_start();
	if ( 'instructor' === $academy_role ) {
		$academy_status = get_user_meta( get_current_user_id(), 'is_academy_instructor', true ) ? get_user_meta( get_current_user_id(), 'academy_instructor_status', true ) : '';
		\Academy\Helper::get_template(
			'shortcode/logged-in-instructor.php',
			[
				'dashboard_url'     => $academy_dashboard,
				'instructor_status' => $academy_status,
			]
		);
	} else {
		\Academy\Helper::get_template( 'shortcode/logged-in-student.php', [ 'dashboard_url' => $academy_dashboard ] );
	}
	printf( '<div %1$s>%2$s</div>', get_block_wrapper_attributes(), ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

\Academy\Blocks::use_course_assets();

// The first form for a role keeps the classic form's ID, which its script
// (and add-ons such as reCAPTCHA) look for.
static $academy_seen = [];
$academy_seen[ $academy_role ] = ( $academy_seen[ $academy_role ] ?? 0 ) + 1;
$academy_dom_id                = 'academy_' . $academy_role . '_reg_form' . ( $academy_seen[ $academy_role ] > 1 ? '_' . $academy_seen[ $academy_role ] : '' );

$academy_submit = '' !== trim( (string) $attributes['submitLabel'] )
	? $attributes['submitLabel']
	: ( 'instructor' === $academy_role ? __( 'Register as Instructor', 'academy' ) : __( 'Register as Student', 'academy' ) );

$academy_wrapper = get_block_wrapper_attributes(
	[
		'class' => 'academy-reg-block',
		'style' => \Academy\Blocks::academy_colors_style( $attributes ),
	]
);
?>
<div <?php echo $academy_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php do_action( 'academy/templates/before_' . $academy_role . '_reg_form' ); ?>
	<form
		id="<?php echo esc_attr( $academy_dom_id ); ?>"
		class="academy-reg-form academy-reg-form--<?php echo esc_attr( $academy_role ); ?> academy-reg-form--block"
		data-role="<?php echo esc_attr( $academy_role ); ?>"
		method="post"
		action="#"
	>
		<?php do_action( 'academy/templates/' . $academy_role . '_reg_form_start' ); ?>
		<?php wp_nonce_field( 'academy_' . $academy_role . '_registration_nonce', '_wpnonce', true, true ); ?>
		<input type="hidden" name="academy_form_id" value="<?php echo esc_attr( RegistrationForms::form_id( $attributes ) ); ?>" />
		<div class="academy-reg-form__fields">
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the fields, escaped by their own blocks. ?>
		</div>
		<div class="academy-reg-form__footer">
			<?php do_action( 'academy/templates/' . $academy_role . '_reg_form_before_submit' ); ?>
			<button
				class="academy-btn academy-btn--bg-purple academy-reg-form__submit"
				type="submit"
				<?php if ( ! empty( $attributes['buttonColor'] ) ) : ?>
					style="background-color:<?php echo esc_attr( $attributes['buttonColor'] ); ?>"
				<?php endif; ?>
			><?php echo esc_html( $academy_submit ); ?></button>
			<?php if ( ! empty( $attributes['showLoginLink'] ) ) : ?>
				<p class="academy-reg-form__login">
					<?php esc_html_e( 'Already have an account?', 'academy' ); ?>
					<a href="<?php echo esc_url( wp_login_url( get_permalink() ? get_permalink() : home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Log in', 'academy' ); ?></a>
				</p>
			<?php endif; ?>
		</div>
		<div class="academy-register-form-status" role="status" aria-live="polite"></div>
		<?php do_action( 'academy/templates/' . $academy_role . '_reg_form_end' ); ?>
	</form>
	<?php do_action( 'academy/templates/after_' . $academy_role . '_reg_form' ); ?>
</div>
