<?php
/**
 * A single child row inside the "My Family" table.
 *
 * This template can be overridden by copying it to
 * yourtheme/academy/frontend-dashboard/pages/partials/guardian-child-row.php
 *
 * @var array $child          One child snapshot (see Store::child_snapshot()).
 * @var array $course_options Assignable courses [ id, title ].
 * @var bool  $has_controls   Whether the Pro parental-controls column is shown.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$has_controls = ! empty( $has_controls );
?>
<tr class="academy-guardian-row">
	<td class="academy-guardian-table__col-child" data-label="<?php esc_attr_e( 'Child', 'academy' ); ?>">
		<div class="academy-guardian-row__child">
			<img class="academy-guardian-row__avatar" src="<?php echo esc_url( $child['avatar'] ); ?>" width="38" height="38" alt="" />
			<div class="academy-guardian-row__meta">
				<strong class="academy-guardian-row__name"><?php echo esc_html( $child['name'] ); ?></strong>
				<span class="academy-guardian-row__email"><?php echo esc_html( $child['email'] ); ?></span>
				<span class="academy-guardian-row__chips">
					<span class="academy-guardian-chip"><b><?php echo (int) $child['completed_count']; ?></b> <?php esc_html_e( 'done', 'academy' ); ?></span>
					<span class="academy-guardian-chip"><b><?php echo (int) $child['certificate_count']; ?></b> <?php esc_html_e( 'certs', 'academy' ); ?></span>
				</span>
			</div>
		</div>
	</td>

	<td class="academy-guardian-table__col-courses" data-label="<?php esc_attr_e( 'Courses', 'academy' ); ?>">
		<?php if ( empty( $child['courses'] ) ) : ?>
			<span class="academy-guardian-row__none"><?php esc_html_e( 'Not enrolled yet', 'academy' ); ?></span>
		<?php else : ?>
			<div class="academy-guardian-courses">
				<?php foreach ( $child['courses'] as $course ) : ?>
					<div class="academy-guardian-course">
						<a class="academy-guardian-course__title" href="<?php echo esc_url( $course['permalink'] ); ?>">
							<?php echo esc_html( $course['title'] ); ?>
							<?php if ( ! empty( $course['has_certificate'] ) ) : ?>
								<span class="academy-guardian-course__cert" title="<?php esc_attr_e( 'Certificate earned', 'academy' ); ?>">🎓</span>
							<?php endif; ?>
						</a>
						<span class="academy-guardian-progress">
							<span class="academy-guardian-progress__bar" style="width:<?php echo (int) $course['progress']; ?>%;"></span>
						</span>
						<span class="academy-guardian-course__percent"><?php echo (int) $course['progress']; ?>%</span>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</td>

	<td class="academy-guardian-table__col-assign" data-label="<?php esc_attr_e( 'Assign a course', 'academy' ); ?>">
		<?php if ( ! empty( $course_options ) ) : ?>
			<div class="academy-guardian-assign">
				<select class="academy-guardian-select academy-guardian-assign-select">
					<option value=""><?php esc_html_e( 'Assign a course…', 'academy' ); ?></option>
					<?php foreach ( $course_options as $co ) : ?>
						<option value="<?php echo esc_attr( $co['id'] ); ?>"><?php echo esc_html( $co['title'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="button" class="academy-guardian-btn academy-guardian-btn--primary academy-guardian-assign-btn" data-student="<?php echo esc_attr( $child['id'] ); ?>">
					<?php esc_html_e( 'Assign', 'academy' ); ?>
				</button>
			</div>
		<?php else : ?>
			<span class="academy-guardian-row__none">—</span>
		<?php endif; ?>
	</td>

	<?php if ( $has_controls ) : ?>
		<td class="academy-guardian-table__col-controls" data-label="<?php esc_attr_e( 'Parental controls', 'academy' ); ?>">
			<?php
			/** Pro per-child controls (require-approval, spending limit). */
			do_action( 'academy/guardian/table_controls', $child );
			?>
		</td>
	<?php endif; ?>

	<td class="academy-guardian-table__col-remove">
		<button type="button" class="academy-guardian-child__remove" data-unlink="<?php echo esc_attr( $child['id'] ); ?>" title="<?php esc_attr_e( 'Remove child', 'academy' ); ?>" aria-label="<?php esc_attr_e( 'Remove child', 'academy' ); ?>">
			<span class="academy-icon academy-icon--delete"></span>
		</button>
	</td>
</tr>
