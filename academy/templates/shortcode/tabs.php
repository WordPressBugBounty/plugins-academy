<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="academy-lesson-tab__head">
	<?php foreach ( $title_lists as $label ) :
		$tabClassName = 'Q&A' === $label ? 'QnA' : $label;
		?>
	<span role="presentation" class="academy-lesson-tab-nav <?php echo esc_attr( 'academy-lesson-tab-' . $tabClassName ); ?>">
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
