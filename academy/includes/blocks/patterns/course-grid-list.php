<?php
/**
 * Pattern: Course list. Registered in Academy\Blocks::register_patterns().
 *
 * @package Academy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:query {"query":{"perPage":5,"pages":0,"offset":0,"postType":"academy_courses","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"namespace":"academy/course-grid"} -->
<div class="wp-block-query"><!-- wp:post-template {"style":{"spacing":{"blockGap":"24px"}},"layout":{"type":"default"}} -->
<!-- wp:group {"className":"academy-course-card","style":{"border":{"radius":"12px","width":"1px","style":"solid","color":"var(--academy-border-color)"},"spacing":{"padding":{"top":"16px","right":"16px","bottom":"16px","left":"16px"},"blockGap":"24px"}},"layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"top"}} -->
<div class="wp-block-group academy-course-card has-border-color" style="border-color:var(--academy-border-color);border-style:solid;border-width:1px;border-radius:12px;padding-top:16px;padding-right:16px;padding-bottom:16px;padding-left:16px"><!-- wp:post-featured-image {"isLink":true,"aspectRatio":"4/3","width":"280px","style":{"border":{"radius":"8px"},"layout":{"selfStretch":"fixed","flexSize":"280px"}}} /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"10px"},"layout":{"selfStretch":"fill","flexSize":null}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
<div class="wp-block-group"><!-- wp:group {"style":{"spacing":{"blockGap":"10px"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group"><!-- wp:academy/course-badge /-->

<!-- wp:post-terms {"term":"academy_courses_category","className":"is-style-academy-with-images","style":{"typography":{"fontSize":"0.875rem"}}} /--></div>
<!-- /wp:group -->

<!-- wp:post-title {"level":3,"isLink":true,"style":{"typography":{"fontSize":"1.25rem","lineHeight":"1.3"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} /-->

<!-- wp:post-excerpt {"excerptLength":30,"style":{"typography":{"fontSize":"0.9375rem"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} /-->

<!-- wp:academy/course-instructors {"style":{"typography":{"fontSize":"0.875rem"}}} /-->

<!-- wp:academy/course-meta {"showEnrolled":true,"style":{"typography":{"fontSize":"0.8125rem"}}} /-->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"auto"},"padding":{"top":"6px"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
<div class="wp-block-group" style="margin-top:auto;padding-top:6px"><!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group"><!-- wp:academy/course-price {"style":{"typography":{"fontSize":"1.125rem"}}} /-->

<!-- wp:academy/course-rating {"hideWhenEmpty":true,"style":{"typography":{"fontSize":"0.875rem"}}} /--></div>
<!-- /wp:group -->

<!-- wp:academy/course-enroll-button /--></div>
<!-- /wp:group --></div>
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
