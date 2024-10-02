<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<?php
foreach ( $fields as $field ) {
	?>
	<div class="academy-row">
	<?php
	foreach ( $field['fields'] as $col ) {
		// Check if the field is marked as pro and Academy Pro is not active
		if ( count( $allow_fields ) && ! in_array( $col['name'], $allow_fields, true ) ) {
			continue; // Skip this field
		}
		?>
			<div class="academy-col academy-form-group">
			<?php if ( isset( $col['type'] ) && in_array( $col['type'], $common_fields, true ) ) : ?>
					<label for="academy_<?php echo esc_attr( $col['name'] ); ?>"><?php echo esc_html( $col['label'] ); ?></label>
					<div>
						<input
								id="academy_<?php echo esc_attr( $col['name'] ); ?>"
								class="academy-form-control<?php esc_attr( 'file' === $col['type'] ? '-file' : '' ); ?>"
								type="<?php echo esc_attr( $col['type'] ); ?>"
								name="<?php echo esc_attr( $col['name'] ); ?>"
								placeholder="<?php echo esc_html( $col['placeholder'] ); ?>"
							<?php if ( $col['is_required'] ) :
								?> required <?php endif; ?>
						/>
						<?php if ( ( 'password' === $col['type'] ) && ( 'password' === $col['name'] ) ) : ?>
							<span id="password-icon" class="toggle-password academy-icon academy-icon--eye"></span>
						<?php endif; ?>
						<?php if ( ( 'password' === $col['type'] ) && ( 'confirm-password' === $col['name'] ) ) : ?>
							<span id="confirm-password-icon" class="toggle-password academy-icon academy-icon--eye"></span>
						<?php endif; ?>
					</div>
				<?php elseif ( isset( $col['type'] ) && 'textarea' === $col['type'] ) : ?>
					<label for="academy_<?php echo esc_attr( $col['name'] ); ?>"><?php echo esc_html( $col['label'] ); ?></label>
					<textarea
							id="academy_<?php echo esc_attr( $col['name'] ); ?>"
							class="academy-form-control"
							name="<?php echo esc_attr( $col['name'] ); ?>"
							placeholder="<?php echo esc_html( $col['placeholder'] ); ?>"
							<?php if ( $col['is_required'] ) :
								?> required <?php endif; ?>
						></textarea>

				<?php elseif ( isset( $col['type'] ) && 'checkbox' === $col['type'] ) : ?>
					<label for="academy_<?php echo esc_attr( $col['name'] ); ?>"><?php echo esc_html( $col['label'] ); ?></label>
					<?php foreach ( $col['options'] as $option ) : ?>
						<div class="academy-form-check">
							<label class="academy-form-check-label">
								<input class="academy-form-check-input"
								type="checkbox"
								value="<?php echo esc_attr( $option['value'] ); ?>"
								id="<?php echo esc_attr( $option['value'] ); ?>"
								name="<?php echo esc_attr( $col['name'] ); ?>"
								>
								<?php echo esc_attr( $option['label'] ); ?>
							</label>
						</div>
					<?php endforeach; ?>


				<?php elseif ( isset( $col['type'] ) && 'radio' === $col['type'] ) : ?>
					<label for="academy_<?php echo esc_attr( $col['name'] ); ?>"><?php echo esc_html( $col['label'] ); ?></label>
					<?php foreach ( $col['options'] as $option ) : ?>
						<div class="academy-form-check">
							<label class="academy-form-check-label">
								<input class="academy-form-check-input"
								type="radio"
								value="<?php echo esc_attr( $option['value'] ); ?>"
								id="<?php echo esc_attr( $option['value'] ); ?>"
								name="<?php echo esc_attr( $col['name'] ); ?>"
								>
								<?php echo esc_attr( $option['label'] ); ?>
							</label>
						</div>
					<?php endforeach; ?>

				<?php elseif ( isset( $col['type'] ) && 'select' === $col['type'] ) : ?>
					<label for="academy_<?php echo esc_attr( $col['name'] ); ?>"><?php echo esc_html( $col['label'] ); ?></label>
					<select class="academy-custom-select"
							name="<?php echo esc_attr( $col['name'] ); ?>"
							>
						<?php foreach ( $col['options'] as $option ) : ?>
							<option value="<?php echo esc_attr( $option['value'] ); ?>">
								<?php echo esc_attr( $option['label'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				<?php elseif ( isset( $col['type'] ) && 'button' === $col['type'] ) : ?>
					<?php do_action( 'academy/templates/' . $type . '_reg_form_before_submit' ); ?>
					<button class="academy-btn academy-btn--bg-purple"
					type="submit"><?php echo esc_html( $col['label'] ); ?></button>
				<?php endif; ?>
			</div>
			<?php
	}//end foreach
	?>
	</div>
	<?php
}//end foreach
