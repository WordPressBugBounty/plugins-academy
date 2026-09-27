<?php
/**
 * Pattern: Course catalog with filters. Registered in Academy\Blocks::register_patterns().
 *
 * @package Academy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"40px"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"280px"} -->
<div class="wp-block-column" style="flex-basis:280px"><!-- wp:academy/course-filters /--></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"blockGap":"24px"}}} -->
<div class="wp-block-column"><!-- wp:academy/course-sort /-->

<!-- wp:query {"query":{"perPage":12,"pages":0,"offset":0,"postType":"academy_courses","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"namespace":"academy/course-grid"} -->
<div class="wp-block-query"><!-- wp:post-template {"layout":{"type":"grid","minimumColumnWidth":"16rem"}} -->
<!-- wp:group {"className":"academy-course-card","style":{"border":{"radius":"12px","width":"1px","style":"solid","color":"var(--academy-border-color)"},"spacing":{"padding":{"top":"0","right":"0","bottom":"0","left":"0"},"blockGap":"0"},"dimensions":{"minHeight":"100%"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
<div class="wp-block-group academy-course-card has-border-color" style="border-color:var(--academy-border-color);border-style:solid;border-width:1px;border-radius:12px;min-height:100%;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/9","style":{"border":{"radius":{"topLeft":"11px","topRight":"11px","bottomLeft":"0","bottomRight":"0"}}}} /-->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"16px","right":"18px","bottom":"18px","left":"18px"},"blockGap":"10px"},"layout":{"selfStretch":"fill","flexSize":null}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
<div class="wp-block-group" style="padding-top:16px;padding-right:18px;padding-bottom:18px;padding-left:18px"><!-- wp:post-terms {"term":"academy_courses_category","style":{"typography":{"fontSize":"0.8125rem"}}} /-->

<!-- wp:post-title {"level":3,"isLink":true,"style":{"typography":{"fontSize":"1.125rem","lineHeight":"1.35"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} /-->

<!-- wp:post-excerpt {"excerptLength":18,"className":"academy-course-card__excerpt","style":{"typography":{"fontSize":"0.875rem"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} /-->

<!-- wp:academy/course-instructors {"style":{"typography":{"fontSize":"0.875rem"}}} /-->

<!-- wp:academy/course-meta {"style":{"typography":{"fontSize":"0.8125rem"}}} /-->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"auto"},"padding":{"top":"10px"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group" style="margin-top:auto;padding-top:10px"><!-- wp:academy/course-rating {"hideWhenEmpty":true,"style":{"typography":{"fontSize":"0.875rem"}}} /-->

<!-- wp:academy/course-price {"style":{"typography":{"fontSize":"1rem"}}} /--></div>
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
<!-- /wp:query --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
