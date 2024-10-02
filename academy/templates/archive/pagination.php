<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="academy-col-12">
	<div class="academy-courses__pagination">
		<?php
		global $wp_query;
		$big = 999999999;

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo paginate_links(array(
			'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
			'format' => '?paged=%#%',
			'current' => max( 1, ( isset( $paged ) ? $paged : get_query_var( 'paged' ) ) ),
			'total' => isset( $max_num_pages ) ? $max_num_pages : $wp_query->max_num_pages,
			'prev_text' => '<i class="academy-icon academy-icon--angle-left" aria-hidden="true"></i>',
			'next_text' => '<i class="academy-icon academy-icon--angle-right" aria-hidden="true"></i>',
		));
		?>
	</div>
</div>
