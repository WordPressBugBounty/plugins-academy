<?php
/**
 * Guardian ("My Family") dashboard page.
 *
 * This template can be overridden by copying it to
 * yourtheme/academy/frontend-dashboard/pages/guardian.php
 *
 * @var int   $guardian_id    Current guardian user id.
 * @var array $children       List of child snapshots (see Store::child_snapshot()).
 * @var array $course_options Assignable courses [ id, title ].
 * @var array $summary        [ children, enrolled, completed, certs ].
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$summary = isset( $summary ) ? $summary : array(
	'children' => count( $children ),
	'enrolled' => 0,
	'completed' => 0,
	'certs' => 0
);
// Pro adds a "Parental controls" column via the `academy/guardian/table_controls`
// seam; render that column only when something is hooked to it.
$academy_guardian_has_controls = has_action( 'academy/guardian/table_controls' );
$academy_guardian_stats        = array(
	array(
		'label' => __( 'Children', 'academy' ),
		'value' => $summary['children']
	),
	array(
		'label' => __( 'Enrolled', 'academy' ),
		'value' => $summary['enrolled']
	),
	array(
		'label' => __( 'Completed', 'academy' ),
		'value' => $summary['completed']
	),
	array(
		'label' => __( 'Certificates', 'academy' ),
		'value' => $summary['certs']
	),
);
?>
<div class="academy-guardian-dashboard">
	<div class="academy-guardian-dashboard__head">
		<div class="academy-guardian-dashboard__heading">
			<h3 class="academy-guardian-dashboard__title"><?php esc_html_e( 'My Family', 'academy' ); ?></h3>
			<p class="academy-guardian-dashboard__subtitle"><?php esc_html_e( 'Track and manage your children’s learning.', 'academy' ); ?></p>
		</div>
		<form class="academy-guardian-add">
			<input type="text" name="name" class="academy-guardian-input" placeholder="<?php esc_attr_e( 'Child name (optional)', 'academy' ); ?>" />
			<input type="email" name="email" class="academy-guardian-input" required placeholder="<?php esc_attr_e( "Child's email", 'academy' ); ?>" />
			<button type="submit" class="academy-guardian-btn academy-guardian-btn--primary">
				<span class="academy-icon academy-icon--plus"></span>
				<?php esc_html_e( 'Add child', 'academy' ); ?>
			</button>
		</form>
	</div>

	<?php if ( ! empty( $children ) ) : ?>
		<div class="academy-guardian-summary">
			<?php foreach ( $academy_guardian_stats as $stat ) : ?>
				<div class="academy-guardian-summary__item">
					<span class="academy-guardian-summary__value"><?php echo (int) $stat['value']; ?></span>
					<span class="academy-guardian-summary__label"><?php echo esc_html( $stat['label'] ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<p class="academy-guardian-msg"></p>

	<?php
	/**
	 * Pro pending-enrollment-approval notice renders here — above the children
	 * table. See Academy Pro's AcademyProGuardian\Frontend::render_approvals().
	 */
	do_action( 'academy/guardian/dashboard_before_children', $guardian_id, $children );
	?>

	<div class="academy-guardian-children">
		<?php if ( empty( $children ) ) : ?>
			<div class="academy-guardian-empty">
				<span class="academy-icon academy-icon--profile-two"></span>
				<p><?php esc_html_e( 'No children linked yet. Add a child by email to start tracking their learning.', 'academy' ); ?></p>
			</div>
		<?php else : ?>
			<div class="academy-guardian-table-wrap">
				<table class="academy-guardian-table">
					<thead>
						<tr>
							<th class="academy-guardian-table__col-child"><?php esc_html_e( 'Child', 'academy' ); ?></th>
							<th class="academy-guardian-table__col-courses"><?php esc_html_e( 'Courses', 'academy' ); ?></th>
							<th class="academy-guardian-table__col-assign"><?php esc_html_e( 'Assign a course', 'academy' ); ?></th>
							<?php if ( $academy_guardian_has_controls ) : ?>
								<th class="academy-guardian-table__col-controls"><?php esc_html_e( 'Parental controls', 'academy' ); ?></th>
							<?php endif; ?>
							<th class="academy-guardian-table__col-remove"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'academy' ); ?></span></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $children as $child ) : ?>
							<?php
							\Academy\Helper::get_template(
								'frontend-dashboard/pages/partials/guardian-child-row.php',
								array(
									'child'          => $child,
									'course_options' => $course_options,
									'has_controls'   => $academy_guardian_has_controls,
								)
							);
							?>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>

	<?php
	/** Pro page-level scripts (approve/reject + save controls) render here. */
	do_action( 'academy/guardian/dashboard_after_children', $guardian_id, $children );
	?>
</div>

<script>
( function () {
	var wrap = document.querySelector( '.academy-guardian-dashboard' );
	if ( ! wrap ) { return; }
	var msg = wrap.querySelector( '.academy-guardian-msg' );
	var form = wrap.querySelector( '.academy-guardian-add' );
	function api( path, method, body ) {
		/* Read the localized global lazily: it is printed in the footer, after
			this inline script parses, so it is only reliably set at call time.
			Block comment, not `//` — an HTML minifier collapses this script onto
			one line and a line comment would swallow the rest of it. */
		var G = window.AcademyGlobal || {};
		return fetch( G.rest_url + <?php echo wp_json_encode( ACADEMY_PLUGIN_SLUG . '/v1/family/' ); ?> + path, {
			method: method,
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': G.nonce },
			body: body ? JSON.stringify( body ) : undefined
		} ).then( function ( r ) { return r.json().then( function ( d ) { return { ok: r.ok, data: d }; } ); } );
	}
	if ( form ) {
		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			msg.textContent = '';
			var fd = new FormData( form );
			api( 'children', 'POST', { name: fd.get( 'name' ), email: fd.get( 'email' ) } ).then( function ( res ) {
				if ( res.ok ) { window.location.reload(); }
				else { msg.textContent = ( res.data && res.data.message ) || 'Could not add child.'; }
			} ).catch( function () { msg.textContent = 'Network error.'; } );
		} );
	}
	wrap.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest( '[data-unlink]' );
		if ( ! btn ) { return; }
		if ( ! window.confirm( 'Remove this child from your family?' ) ) { return; }
		api( 'children/' + btn.getAttribute( 'data-unlink' ), 'DELETE' ).then( function ( res ) {
			if ( res.ok ) { window.location.reload(); }
		} );
	} );
	/* Assign a course to a child. */
	wrap.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest( '.academy-guardian-assign-btn' );
		if ( ! btn ) { return; }
		var row = btn.closest( '.academy-guardian-row' );
		var sel = row && row.querySelector( '.academy-guardian-assign-select' );
		if ( ! sel || ! sel.value ) { return; }
		btn.disabled = true;
		msg.textContent = '';
		api( 'assign', 'POST', { course_id: sel.value, student_id: btn.getAttribute( 'data-student' ) } ).then( function ( res ) {
			btn.disabled = false;
			if ( res.ok && res.data && res.data.assigned ) { window.location.reload(); return; }
			msg.style.color = res.ok && res.data && res.data.pending ? 'inherit' : '';
			msg.textContent = ( res.data && res.data.message ) || 'Could not assign the course.';
		} ).catch( function () { btn.disabled = false; msg.textContent = 'Network error.'; } );
	} );
} )();
</script>
