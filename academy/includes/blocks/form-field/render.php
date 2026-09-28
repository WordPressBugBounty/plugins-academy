<?php
/**
 * Form Field: one field of a Registration Form.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content.
 * @var WP_Block $block      Block.
 *
 * @package Academy
 */

use Academy\RegistrationForms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$academy_field = RegistrationForms::field( $attributes );
if ( ! $academy_field ) {
	return;
}
// Profile fields beyond the account are an Academy Pro feature.
if ( ! \Academy\Helper::is_active_academy_pro() && ! in_array( $academy_field['name'], RegistrationForms::BASIC_FIELDS, true ) ) {
	return;
}

$academy_type     = $academy_field['type'];
$academy_name     = $academy_field['name'];
$academy_id       = 'academy_field_' . $academy_name . '_' . wp_unique_id();
$academy_required = $academy_field['is_required'];
$academy_help     = (string) ( $attributes['help'] ?? '' );
$academy_help_id  = $academy_id . '_help';
$academy_describe = '' !== $academy_help ? ' aria-describedby="' . esc_attr( $academy_help_id ) . '"' : '';
$academy_label    = '' !== $academy_field['label'] ? $academy_field['label'] : $academy_name;
$academy_classes  = 'academy-form-group academy-reg-form__field academy-reg-form__field--' . ( 'half' === ( $attributes['width'] ?? 'full' ) ? 'half' : 'full' ) . ' academy-reg-form__field--' . $academy_type;
$academy_group    = in_array( $academy_type, [ 'radio', 'checkbox' ], true ) && ( 'radio' === $academy_type || count( $academy_field['options'] ) > 0 );

$academy_star     = $academy_required ? ' <span class="academy-reg-form__required" aria-hidden="true">*</span>' : '';
$academy_wrapper  = get_block_wrapper_attributes(
	[
		'class' => $academy_classes,
		'style' => \Academy\Blocks::academy_colors_style( $attributes ),
	]
);
?>
<div <?php echo $academy_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $academy_group ) : ?>
		<fieldset class="academy-reg-form__choices">
			<legend class="academy-reg-form__label"><?php echo esc_html( $academy_label ); ?><?php echo $academy_star; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></legend>
			<?php foreach ( $academy_field['options'] as $academy_index => $academy_option ) : ?>
				<label class="academy-form-check-label academy-reg-form__choice">
					<input
						class="academy-form-check-input"
						type="<?php echo esc_attr( $academy_type ); ?>"
						name="<?php echo esc_attr( $academy_name . ( 'checkbox' === $academy_type ? '[]' : '' ) ); ?>"
						value="<?php echo esc_attr( $academy_option['value'] ); ?>"
						<?php echo ( 'radio' === $academy_type && $academy_required && 0 === $academy_index ) ? 'required' : ''; ?>
					/>
					<span><?php echo esc_html( $academy_option['label'] ); ?></span>
				</label>
			<?php endforeach; ?>
		</fieldset>
	<?php elseif ( 'checkbox' === $academy_type ) : ?>
		<?php // One checkbox, such as "I agree to the terms": its label is the field's. ?>
		<label class="academy-form-check-label academy-reg-form__choice" for="<?php echo esc_attr( $academy_id ); ?>">
			<input
				id="<?php echo esc_attr( $academy_id ); ?>"
				class="academy-form-check-input"
				type="checkbox"
				name="<?php echo esc_attr( $academy_name ); ?>"
				value="1"
				<?php echo $academy_required ? 'required' : ''; ?>
				<?php echo $academy_describe; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			/>
			<span><?php echo esc_html( $academy_label ); ?><?php echo $academy_star; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		</label>
	<?php else : ?>
		<label class="academy-reg-form__label" for="<?php echo esc_attr( $academy_id ); ?>"><?php echo esc_html( $academy_label ); ?><?php echo $academy_star; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
		<?php if ( 'textarea' === $academy_type ) : ?>
			<textarea
				id="<?php echo esc_attr( $academy_id ); ?>"
				class="academy-form-control"
				name="<?php echo esc_attr( $academy_name ); ?>"
				rows="4"
				placeholder="<?php echo esc_attr( $academy_field['placeholder'] ); ?>"
				<?php echo $academy_required ? 'required' : ''; ?>
				<?php echo $academy_describe; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			></textarea>
		<?php elseif ( 'select' === $academy_type ) : ?>
			<select
				id="<?php echo esc_attr( $academy_id ); ?>"
				class="academy-form-control academy-reg-form__select"
				name="<?php echo esc_attr( $academy_name ); ?>"
				<?php echo $academy_required ? 'required' : ''; ?>
				<?php echo $academy_describe; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			>
				<option value=""><?php echo esc_html( '' !== $academy_field['placeholder'] ? $academy_field['placeholder'] : __( 'Choose…', 'academy' ) ); ?></option>
				<?php foreach ( $academy_field['options'] as $academy_option ) : ?>
					<option value="<?php echo esc_attr( $academy_option['value'] ); ?>"><?php echo esc_html( $academy_option['label'] ); ?></option>
				<?php endforeach; ?>
			</select>
		<?php else : ?>
			<div class="academy-reg-form__control">
				<input
					id="<?php echo esc_attr( $academy_id ); ?>"
					class="academy-form-control"
					type="<?php echo esc_attr( $academy_type ); ?>"
					name="<?php echo esc_attr( $academy_name ); ?>"
					placeholder="<?php echo esc_attr( $academy_field['placeholder'] ); ?>"
					<?php echo 'email' === $academy_type ? 'autocomplete="email"' : ''; ?>
					<?php echo 'password' === $academy_type ? 'autocomplete="new-password"' : ''; ?>
					<?php echo $academy_required ? 'required' : ''; ?>
					<?php echo $academy_describe; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				/>
				<?php if ( 'password' === $academy_type ) : ?>
					<button type="button" class="academy-reg-form__reveal" aria-controls="<?php echo esc_attr( $academy_id ); ?>" aria-pressed="false" aria-label="<?php esc_attr_e( 'Show password', 'academy' ); ?>">
						<span class="academy-icon academy-icon--eye" aria-hidden="true"></span>
					</button>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>
	<?php if ( '' !== $academy_help ) : ?>
		<p id="<?php echo esc_attr( $academy_help_id ); ?>" class="academy-reg-form__help"><?php echo esc_html( $academy_help ); ?></p>
	<?php endif; ?>
</div>
