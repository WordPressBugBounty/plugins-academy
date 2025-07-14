<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$course_id = \Academy\Helper::get_the_current_course_id();
$qa = get_post_meta( $course_id, 'academy_is_enabled_course_qa', true );
$announcement = get_post_meta( $course_id, 'academy_is_enabled_course_announcements', true );
$is_enable_comments = \Academy\Helper::get_settings( 'is_enabled_academy_lessons_comment', true );
$is_hp_enable = \Academy\Helper::get_settings( 'academy_is_hp_lesson_active', false );
$is_show_comment = $is_enable_comments && ! $is_hp_enable ? true : false;
?>

<div class="academy-lesson-tab__head" data-is-enable-qa="<?php echo esc_attr( $qa ); ?>" data-is-enable-announcement="<?php echo esc_attr( $announcement ); ?>" data-is-enable-lesson-comments="<?php echo esc_attr( $is_show_comment ); ?>">
	<?php foreach ( $title_lists as $key => $label ) :
		$tabClassName = [ 'Course Content', 'QnA', 'Announcement', 'Lesson Comments' ];
		$label = 'Q&A' === $label ? __( 'Q&A', 'academy' ) : $label;
		?>
	<span role="presentation" class="academy-lesson-tab-nav <?php echo esc_attr( 'academy-lesson-tab-' . $tabClassName[ $key ] ); ?>">
		<span class="academy-btn--label">
			<?php echo esc_html( $label ); ?>
		</span>
	</span>
	<?php endforeach; ?>
</div>
<?php
foreach ( $shortcode_lists_with_title as $shortcode_with_title ) {
	$className = 'Q&A' === $shortcode_with_title['title'] ? 'QnA' : $shortcode_with_title['title'];
	?>
<div class="academy-lesson-tab__content <?php echo esc_attr( $className ); ?>">
	<?php echo do_shortcode( '[' . $shortcode_with_title['shortcode'] . ']' ); ?>
</div>
	<?php
}
