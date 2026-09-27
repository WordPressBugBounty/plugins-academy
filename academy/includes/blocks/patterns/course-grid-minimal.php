<?php
/**
 * Pattern: Course grid: minimal. Registered in Academy\Blocks::register_patterns().
 *
 * @package Academy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:query {"query":{"perPage":6,"pages":0,"offset":0,"postType":"academy_courses","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"namespace":"academy/course-grid"} -->
<div class="wp-block-query"><!-- wp:post-template {"style":{"spacing":{"blockGap":"32px"}},"layout":{"type":"grid","columnCount":3}} -->
<!-- wp:group {"className":"academy-course-card","style":{"spacing":{"blockGap":"8px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
<div class="wp-block-group academy-course-card"><!-- wp:post-featured-image {"isLink":true,"aspectRatio":"3/2","style":{"border":{"radius":"14px"},"spacing":{"margin":{"bottom":"6px"}}}} /-->

<!-- wp:post-title {"level":3,"isLink":true,"style":{"typography":{"fontSize":"1.0625rem","lineHeight":"1.35"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} /-->

<!-- wp:academy/course-instructors {"showAvatar":false,"style":{"typography":{"fontSize":"0.875rem"}}} /-->

<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group"><!-- wp:academy/course-price /-->

<!-- wp:academy/course-rating {"showCount":false,"hideWhenEmpty":true,"style":{"typography":{"fontSize":"0.875rem"}}} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
<!-- /wp:post-template -->

<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} /-->

<!-- wp:query-no-results -->
<!-- wp:paragraph -->
<p><?php esc_html_e( 'No courses found.', 'academy' ); ?></p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results --></div>
<!-- /wp:query -->
