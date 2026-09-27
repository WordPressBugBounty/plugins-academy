<?php
/**
 * Course Instructors block.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$academy_course = \Academy\Blocks\CourseData::get( isset( $block->context['postId'] ) ? $block->context['postId'] : get_the_ID() );
if ( ! $academy_course || empty( $academy_course['instructors'] ) ) {
	return;
}

$academy_people = array_slice( $academy_course['instructors'], 0, max( 1, (int) $attributes['maxShown'] ) );
$academy_extra  = count( $academy_course['instructors'] ) - count( $academy_people );
$academy_size   = max( 16, min( 96, (int) $attributes['avatarSize'] ) );
$academy_prefix = isset( $attributes['prefix'] ) ? $attributes['prefix'] : __( 'By', 'academy' );

$academy_names = [];
foreach ( $academy_people as $academy_person ) {
	$academy_names[] = $academy_person['url']
		? '<a href="' . esc_url( $academy_person['url'] ) . '">' . esc_html( $academy_person['name'] ) . '</a>'
		: '<span>' . esc_html( $academy_person['name'] ) . '</span>';
}
if ( $academy_extra > 0 ) {
	/* translators: %s: number of other instructors. */
	$academy_names[] = esc_html( sprintf( _n( '%s other', '%s others', $academy_extra, 'academy' ), number_format_i18n( $academy_extra ) ) );
}
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core. ?>>
	<?php if ( ! empty( $attributes['showAvatar'] ) ) : ?>
		<span class="academy-course-instructors__avatars" aria-hidden="true">
			<?php foreach ( $academy_people as $academy_person ) : ?>
				<img src="<?php echo esc_url( $academy_person['avatar'] ); ?>" alt="" width="<?php echo esc_attr( $academy_size ); ?>" height="<?php echo esc_attr( $academy_size ); ?>" loading="lazy" decoding="async" />
			<?php endforeach; ?>
		</span>
	<?php endif; ?>
	<span class="academy-course-instructors__names">
		<?php if ( '' !== $academy_prefix ) : ?>
			<span class="academy-course-instructors__prefix"><?php echo esc_html( $academy_prefix ); ?></span>
		<?php endif; ?>
		<?php echo wp_kses_post( implode( ', ', $academy_names ) ); ?>
	</span>
</div>
