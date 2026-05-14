<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$items = [];

// Build data array
if ( $skill ) {
	$items[] = [
		'icon'  => 'academy-icon--level',
		'label' => __( 'Course Level', 'academy' ),
		'value' => $skill,
	];
}

if ( $total_lessons ) {
	$items[] = [
		'icon'  => 'academy-icon--video-lesson',
		'label' => __( 'Lessons', 'academy' ),
		'value' => $total_lessons,
	];
}

if ( $duration ) {
	$items[] = [
		'icon'  => 'academy-icon--clock',
		'label' => __( 'Duration', 'academy' ),
		'value' => $duration,
	];
}

if ( $language ) {
	$items[] = [
		'icon'  => 'academy-icon--language',
		'label' => __( 'Language', 'academy' ),
		'value' => $language,
	];
}

if ( $total_enrolled && $total_enroll_count_status ) {
	$items[] = [
		'icon'  => 'academy-icon--group-profile',
		'label' => __( 'Enrolled', 'academy' ),
		'value' => $total_enrolled,
	];
}

if ( $max_students ) {
	$available_seats = max( (int) $max_students - (int) $total_enrolled, 0 );

	$items[] = [
		'icon'  => 'academy-icon--user',
		'label' => __( 'Available Seats', 'academy' ),
		'value' => $available_seats,
	];
}

// Always show
$items[] = [
	'icon'  => 'academy-icon--file',
	'label' => __( 'Additional Resource', 'academy' ),
	'value' => $total_resource ?? 0,
];

if ( $last_update ) {
	$items[] = [
		'icon'  => 'academy-icon--calender',
		'label' => __( 'Last Update', 'academy' ),
		'value' => $last_update,
	];
}

if ( $course_expired_date ) {
	$items[] = [
		'icon'  => 'academy-icon--clock',
		'label' => __( 'Expire Duration', 'academy' ),
		'value' => $course_expired_date,
	];
}
?>

<div class="academy-widget-enroll__content">
	<ul class="academy-widget-enroll__content-lists">
		<?php foreach ( $items as $item ) : ?>
			<li>
				<span class="label">
					<i class="academy-icon <?php echo esc_attr( $item['icon'] ); ?>"></i>
					<?php echo esc_html( $item['label'] ); ?>
				</span>
				<span class="data">
					<?php echo esc_html( $item['value'] ); ?>
				</span>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php do_action( 'academy/templates/single_course_enroll_content_after' ); ?>
</div>