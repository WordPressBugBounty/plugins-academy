<?php
/**
 * Pattern: Course page: two columns. Registered in Academy\Blocks::register_patterns().
 *
 * @package Academy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:columns {"align":"wide","className":"is-style-academylms","style":{"spacing":{"blockGap":{"left":"48px"}}}} -->
<div class="wp-block-columns alignwide is-style-academylms"><!-- wp:column {"width":"66.66%","style":{"spacing":{"blockGap":"24px"}}} -->
<div class="wp-block-column" style="flex-basis:66.66%"><!-- wp:academy/course-media /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"10px"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group"><!-- wp:academy/course-badge /-->

<!-- wp:post-terms {"term":"academy_courses_category","className":"is-style-academy-with-images"} /--></div>
<!-- /wp:group -->

<!-- wp:post-title {"level":1} /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"12px 28px"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group"><!-- wp:academy/course-instructors {"avatarSize":32} /-->

<!-- wp:academy/course-rating {"hideWhenEmpty":true} /-->

<!-- wp:academy/course-meta {"showEnrolled":true} /--></div>
<!-- /wp:group -->

<!-- wp:academy/course-description /-->

<!-- wp:academy/course-additional-info /-->

<!-- wp:academy/course-curriculum /-->

<!-- wp:academy/course-attachments /-->

<!-- wp:academy/course-instructor-profiles /-->

<!-- wp:academy/course-rating-summary /-->

<!-- wp:academy/course-review-form /-->

<!-- wp:academy/course-reviews /--></div>
<!-- /wp:column -->

<!-- wp:column {"width":"33.33%","className":"is-style-academylmssticky"} -->
<div class="wp-block-column is-style-academylmssticky" style="flex-basis:33.33%"><!-- wp:academy/course-enroll-card /-->

<!-- wp:academy/course-wishlist {"showLabel":true} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
