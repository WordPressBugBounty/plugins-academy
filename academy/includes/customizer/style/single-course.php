<?php
namespace Academy\Customizer\Style;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Interfaces\DynamicStyleInterface;

class SingleCourse extends Base implements DynamicStyleInterface {
	public static function get_css() {
		$css = '';
		$settings = self::get_settings();

		// General Options
		$single_course_wrapper_bg_color = ( isset( $settings['single_course_wrapper_bg_color'] ) ? $settings['single_course_wrapper_bg_color'] : '' );
		$single_course_wrapper_margin = ( isset( $settings['single_course_wrapper_margin'] ) ? $settings['single_course_wrapper_margin'] : '' );
		$single_course_wrapper_padding = ( isset( $settings['single_course_wrapper_padding'] ) ? $settings['single_course_wrapper_padding'] : '' );
		$single_course_category_text_color = ( isset( $settings['single_course_category_text_color'] ) ? $settings['single_course_category_text_color'] : '' );
		$single_course_title_color = ( isset( $settings['single_course_title_color'] ) ? $settings['single_course_title_color'] : '' );

		if ( $single_course_wrapper_bg_color ) {
			$css .= ".academy-single-course {
                background: $single_course_wrapper_bg_color;
            }";
		}

		if ( $single_course_wrapper_margin ) {
			$css .= self::generate_dimensions_css( '.academy-single-course', $single_course_wrapper_margin, 'margin' );
		}

		if ( $single_course_wrapper_padding ) {
			$css .= self::generate_dimensions_css( '.academy-single-course', $single_course_wrapper_padding );
		}

		if ( $single_course_category_text_color ) {
			$css .= ".academy-single-course .academy-single-course__categroy a {
                color: $single_course_category_text_color;
            }";
		}

		if ( $single_course_title_color ) {
			$css .= ".academy-single-course .academy-single-course__title {
                color: $single_course_title_color;
            }";
		}

		// Instructor Area
		$instructor_title_color = ( isset( $settings['single_course_instructor_title_color'] ) ? $settings['single_course_instructor_title_color'] : '' );
		$instructor_name_color = ( isset( $settings['single_course_instructor_name_color'] ) ? $settings['single_course_instructor_name_color'] : '' );
		$review_title_color = ( isset( $settings['single_course_review_title_color'] ) ? $settings['single_course_review_title_color'] : '' );
		$rating_icon_color = ( isset( $settings['single_course_rating_icon_color'] ) ? $settings['single_course_rating_icon_color'] : '' );
		$rating_text_color = ( isset( $settings['single_course_rating_text_color'] ) ? $settings['single_course_rating_text_color'] : '' );
		$instructor_padding = ( isset( $settings['single_course_instructor_padding'] ) ? $settings['single_course_instructor_padding'] : '' );

		if ( $instructor_title_color ) {
			$css .= ".academy-single-course__content-item--instructors .course-single-instructor .instructor-info__content .instructor-title {
                color: $instructor_title_color;
            }";
		}

		if ( $instructor_name_color ) {
			$css .= ".academy-single-course__content-item--instructors .course-single-instructor .instructor-info__content .instructor-name a {
                color: $instructor_name_color;
            }";
		}

		if ( $review_title_color ) {
			$css .= ".academy-single-course .course-single-instructor .instructor-review .instructor-review__title {
                color: $review_title_color;
            }";
		}

		if ( $rating_icon_color ) {
			$css .= ".academy-single-course .course-single-instructor .instructor-review .instructor-review__rating .academy-group-star i:before {
                color: $rating_icon_color;
            }";
		}

		if ( $rating_text_color ) {
			$css .= ".academy-single-course .course-single-instructor .instructor-review .instructor-review__rating .instructor-review__rating-number, .academy-single-course .course-single-instructor .instructor-review .instructor-review__rating .instructor-review__rating-number span {
                color: $rating_text_color;
            }";
		}

		if ( $instructor_padding ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-single-course__content-item--instructors .course-single-instructor', $instructor_padding );
		}

		// Content Area
		$single_course_description_heading_text_color = ( isset( $settings['single_course_description_heading_text_color'] ) ? $settings['single_course_description_heading_text_color'] : '' );
		$single_course_description_text_color = ( isset( $settings['single_course_description_text_color'] ) ? $settings['single_course_description_text_color'] : '' );

		if ( $single_course_description_heading_text_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--description .academy-single-course__content-item--description-title {
                color: $single_course_description_heading_text_color;
            }";
		}

		if ( $single_course_description_text_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--description p {
                color: $single_course_description_text_color;
            }";
		}

		// Benefits
		$single_course_benefits_heading_text_color = ( isset( $settings['single_course_benefits_heading_text_color'] ) ? $settings['single_course_benefits_heading_text_color'] : '' );
		$single_course_benefits_description_icon_color = ( isset( $settings['single_course_benefits_description_icon_color'] ) ? $settings['single_course_benefits_description_icon_color'] : '' );
		$single_course_benefits_description_text_color = ( isset( $settings['single_course_benefits_description_text_color'] ) ? $settings['single_course_benefits_description_text_color'] : '' );

		if ( $single_course_benefits_heading_text_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--benefits .benefits-title {
                color: $single_course_benefits_heading_text_color;
            }";
		}

		if ( $single_course_benefits_description_icon_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--benefits .benefits-content ul li i {
                color: $single_course_benefits_description_icon_color;
            }";
		}

		if ( $single_course_benefits_description_text_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--benefits .benefits-content ul li span{
                color: $single_course_benefits_description_text_color;
            }";
		}

		// Additional Info
		$single_course_additional_info_tab_heading_text_color = ( isset( $settings['single_course_additional_info_tab_heading_text_color'] ) ? $settings['single_course_additional_info_tab_heading_text_color'] : '' );
		$single_course_additional_info_tab_heading_text_color = ( isset( $settings['single_course_additional_info_tab_heading_text_color'] ) ? $settings['single_course_additional_info_tab_heading_text_color'] : '' );
		$single_course_additional_info_tab_heading_active_text_color = ( isset( $settings['single_course_additional_info_tab_heading_active_text_color'] ) ? $settings['single_course_additional_info_tab_heading_active_text_color'] : '' );
		$single_course_additional_info_tab_heading_active_border_color = ( isset( $settings['single_course_additional_info_tab_heading_active_border_color'] ) ? $settings['single_course_additional_info_tab_heading_active_border_color'] : '' );
		$single_course_additional_info_description_icon_color = ( isset( $settings['single_course_additional_info_description_icon_color'] ) ? $settings['single_course_additional_info_description_icon_color'] : '' );
		$single_course_additional_info_description_text_color = ( isset( $settings['single_course_additional_info_description_text_color'] ) ? $settings['single_course_additional_info_description_text_color'] : '' );

		if ( $single_course_additional_info_tab_heading_text_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--additional-info .academy-tabs-nav li a {
                color: $single_course_additional_info_tab_heading_text_color;
            }";
		}

		if ( $single_course_additional_info_tab_heading_active_text_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--additional-info .academy-tabs-nav li.active a{
                color: $single_course_additional_info_tab_heading_active_text_color;
            }";
		}

		if ( $single_course_additional_info_tab_heading_active_border_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--additional-info .academy-tabs-nav li.active {
                border-bottom-color: $single_course_additional_info_tab_heading_active_border_color;
            }";
		}

		if ( $single_course_additional_info_description_icon_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--additional-info .academy-tabs-content ul li i{
                color: $single_course_additional_info_description_icon_color;
            }";
		}

		if ( $single_course_additional_info_description_text_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--additional-info .academy-tabs-content ul li span{
                color: $single_course_additional_info_description_text_color;
            }";
		}

		// Curriculam/Topic Style
		$topic_heading_text_color = ( isset( $settings['single_course_topic_heading_text_color'] ) ? $settings['single_course_topic_heading_text_color'] : '' );
		$topic_heading_margin = ( isset( $settings['single_course_topic_heading_margin'] ) ? $settings['single_course_topic_heading_margin'] : '' );
		$topic_title_bg_color = ( isset( $settings['single_course_topic_title_bg_color'] ) ? $settings['single_course_topic_title_bg_color'] : '' );
		$topic_title_padding = ( isset( $settings['single_course_topic_title_padding'] ) ? $settings['single_course_topic_title_padding'] : '' );
		$topic_title_text_color = ( isset( $settings['single_course_topic_title_text_color'] ) ? $settings['single_course_topic_title_text_color'] : '' );
		$topic_title_icon_color = ( isset( $settings['single_course_topic_title_icon_color'] ) ? $settings['single_course_topic_title_icon_color'] : '' );
		$topic_content_bg_color = ( isset( $settings['single_course_topic_content_bg_color'] ) ? $settings['single_course_topic_content_bg_color'] : '' );
		$topic_content_padding = ( isset( $settings['single_course_topic_content_padding'] ) ? $settings['single_course_topic_content_padding'] : '' );
		$topic_content_thumbnail_color = ( isset( $settings['single_course_topic_content_thumbnail_color'] ) ? $settings['single_course_topic_content_thumbnail_color'] : '' );
		$topic_content_text_color = ( isset( $settings['single_course_topic_content_text_color'] ) ? $settings['single_course_topic_content_text_color'] : '' );
		$topic_content_icon_color = ( isset( $settings['single_course_topic_content_icon_color'] ) ? $settings['single_course_topic_content_icon_color'] : '' );
		$topic_content_separator_color = ( isset( $settings['single_course_topic_content_separator_color'] ) ? $settings['single_course_topic_content_separator_color'] : '' );

		if ( $topic_heading_margin ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-single-course__content-item--curriculum .academy-curriculum-title', $topic_heading_margin, 'margin' );
		}

		if ( $topic_heading_text_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--curriculum .academy-curriculum-title {
                color: $topic_heading_text_color;
            }";
		}

		if ( $topic_title_bg_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--curriculum .academy-accordion li .academy-accordion__title {
                background: $topic_title_bg_color;
            }";
		}

		if ( $topic_title_padding ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-single-course__content-item--curriculum .academy-accordion li .academy-accordion__title', $topic_title_padding );
		}

		if ( $topic_title_text_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--curriculum .academy-accordion li .academy-accordion__title {
                color: $topic_title_text_color;
            }";
		}

		if ( $topic_title_icon_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--curriculum .academy-accordion li .academy-accordion__title:after {
                border-right-color: $topic_title_icon_color;
                border-bottom-color: $topic_title_icon_color;
            }";
		}

		if ( $topic_content_bg_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--curriculum .academy-accordion li .academy-accordion__body {
                background: $topic_content_bg_color;
            }";
		}

		if ( $topic_content_padding ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-single-course__content-item--curriculum .academy-accordion li .academy-accordion__body .academy-lesson-list__item', $topic_content_padding );
		}

		if ( $topic_content_thumbnail_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--curriculum .academy-accordion li .academy-accordion__body .academy-lesson-list__item .academy-entry-thumbnail {
                background: $topic_content_thumbnail_color;
            }";
		}

		if ( $topic_content_text_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--curriculum .academy-accordion li .academy-accordion__body .academy-entry-content .academy-entry-title, .academy-single-course .academy-single-course__content-item--curriculum .academy-accordion li .academy-accordion__body .academy-entry-content .academy-entry-time {
                color: $topic_content_text_color;
            }";
		}

		if ( $topic_content_icon_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--curriculum .academy-accordion li .academy-accordion__body .academy-lesson-list__item .academy-entry-control i:before {
                color: $topic_content_icon_color;
            }";
		}

		if ( $topic_content_separator_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--curriculum .academy-accordion li .academy-accordion__body .academy-lesson-list__item {
                border-top-color: $topic_content_separator_color;
            }";
		}

		// Feedback Style
		$feedback_heading_color = ( isset( $settings['single_course_feedback_heading_color'] ) ? $settings['single_course_feedback_heading_color'] : '' );
		$feedback_heading_margin = ( isset( $settings['single_course_feedback_heading_margin'] ) ? $settings['single_course_feedback_heading_margin'] : '' );
		$feedback_bg_color = ( isset( $settings['single_course_feedback_bg_color'] ) ? $settings['single_course_feedback_bg_color'] : '' );
		$feedback_text_color = ( isset( $settings['single_course_feedback_text_color'] ) ? $settings['single_course_feedback_text_color'] : '' );
		$feedback_padding = ( isset( $settings['single_course_feedback_padding'] ) ? $settings['single_course_feedback_padding'] : '' );
		$avg_rating_number_color = ( isset( $settings['single_course_avg_rating_number_color'] ) ? $settings['single_course_avg_rating_number_color'] : '' );
		$feedback_rating_icon_color = ( isset( $settings['single_course_feedback_rating_icon_color'] ) ? $settings['single_course_feedback_rating_icon_color'] : '' );
		$feedback_rating_progressbar_bg_color = ( isset( $settings['single_course_feedback_rating_progressbar_bg_color'] ) ? $settings['single_course_feedback_rating_progressbar_bg_color'] : '' );
		$feedback_rating_progressbar_fill_color = ( isset( $settings['single_course_feedback_rating_progressbar_fill_color'] ) ? $settings['single_course_feedback_rating_progressbar_fill_color'] : '' );
		$rating_percentage_color = ( isset( $settings['single_course_feedback_rating_percentage_color'] ) ? $settings['single_course_feedback_rating_percentage_color'] : '' );

		if ( $feedback_heading_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--feedback .feedback-title {
                color: $feedback_heading_color;
            }";
		}

		if ( $feedback_heading_margin ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-single-course__content-item--feedback .feedback-title', $feedback_heading_margin, 'margin' );
		}

		if ( $feedback_bg_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--feedback .academy-student-course-feedback-ratings {
                background: $feedback_bg_color;
            }";
		}

		if ( $feedback_text_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--feedback .academy-student-course-feedback-ratings .academy-avg-rating-total, .academy-single-course .academy-single-course__content-item--feedback .academy-student-course-feedback-ratings .academy-ratings-list .academy-ratings-list-item {
                color: $feedback_text_color;
            }";
		}

		if ( $feedback_padding ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-single-course__content-item--feedback .academy-student-course-feedback-ratings', $feedback_padding );
		}

		if ( $avg_rating_number_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--feedback .academy-student-course-feedback-ratings .academy-avg-rating {
                color: $avg_rating_number_color;
            }";
		}

		if ( $feedback_rating_icon_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--feedback .academy-student-course-feedback-ratings .academy-avg-rating-html i:before, .academy-single-course .academy-single-course__content-item--feedback .academy-student-course-feedback-ratings .academy-ratings-list .academy-icon:before {
                color: $feedback_rating_icon_color;
            }";
		}

		if ( $feedback_rating_progressbar_bg_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--feedback .academy-student-course-feedback-ratings .academy-ratings-list-item .academy-ratings-list-item-fill {
                background: $feedback_rating_progressbar_bg_color;
            }";
		}

		if ( $feedback_rating_progressbar_fill_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--feedback .academy-student-course-feedback-ratings .academy-ratings-list .academy-ratings-list-item .academy-ratings-list-item-fill-bar {
                background: $feedback_rating_progressbar_fill_color;
            }";
		}

		if ( $rating_percentage_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--feedback .academy-student-course-feedback-ratings .academy-ratings-list .academy-ratings-list-item .academy-ratings-list-item-label span {
                color: $rating_percentage_color;
            }";
		}

		// Review Style
		$review_margin = ( isset( $settings['single_course_review_margin'] ) ? $settings['single_course_review_margin'] : '' );
		$review_padding = ( isset( $settings['single_course_review_padding'] ) ? $settings['single_course_review_padding'] : '' );
		$review_bg_color = ( isset( $settings['single_course_review_bg_color'] ) ? $settings['single_course_review_bg_color'] : '' );
		$review_rating_color = ( isset( $settings['single_course_review_rating_color'] ) ? $settings['single_course_review_rating_color'] : '' );
		$review_rating_icon_color = ( isset( $settings['single_course_review_rating_icon_color'] ) ? $settings['single_course_review_rating_icon_color'] : '' );
		$review_author_color = ( isset( $settings['single_course_review_author_color'] ) ? $settings['single_course_review_author_color'] : '' );
		$review_date_color = ( isset( $settings['single_course_review_date_color'] ) ? $settings['single_course_review_date_color'] : '' );
		$review_description_color = ( isset( $settings['single_course_review_description_color'] ) ? $settings['single_course_review_description_color'] : '' );

		if ( $review_bg_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--reviews .academy-review-list {
                background: $review_bg_color;
            }";
		}

		if ( $review_padding ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-single-course__content-item--reviews .academy-review-list', $review_padding );
		}

		if ( $review_margin ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-single-course__content-item--reviews .academy-review-list', $review_margin, 'margin' );
		}

		if ( $review_rating_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--reviews .academy-review__rating {
                color: $review_rating_color;
            }";
		}

		if ( $review_rating_icon_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--reviews .academy-review__rating .academy-icon:before {
                color: $review_rating_icon_color;
            }";
		}

		if ( $review_author_color ) {
			$css .= ".academy-single-course .academy-review-list li .academy-review_container .academy-review-content .academy-review-meta__author {
                color: $review_author_color;
            }";
		}

		if ( $review_date_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--reviews .academy-review-content .academy-review-meta__published-date {
                color: $review_date_color;
            }";
		}

		if ( $review_description_color ) {
			$css .= ".academy-single-course .academy-review-list li .academy-review_container .academy-review-content .academy-review-description p {
                color: $review_description_color;
            }";
		}

		// Review Form Style
		$review_form_bg_color = ( isset( $settings['single_course_review_form_bg_color'] ) ? $settings['single_course_review_form_bg_color'] : '' );
		$review_form_margin = ( isset( $settings['single_course_review_form_margin'] ) ? $settings['single_course_review_form_margin'] : '' );
		$review_form_padding = ( isset( $settings['single_course_review_form_padding'] ) ? $settings['single_course_review_form_padding'] : '' );

		$add_review_button_bg_color = ( isset( $settings['single_course_add_review_button_bg_color'] ) ? $settings['single_course_add_review_button_bg_color'] : '' );
		$add_review_button_text_color = ( isset( $settings['single_course_add_review_button_text_color'] ) ? $settings['single_course_add_review_button_text_color'] : '' );
		$add_review_button_hover_bg_color = ( isset( $settings['single_course_add_review_button_hover_bg_color'] ) ? $settings['single_course_add_review_button_hover_bg_color'] : '' );
		$add_review_button_hover_text_color = ( isset( $settings['single_course_add_review_button_hover_text_color'] ) ? $settings['single_course_add_review_button_hover_text_color'] : '' );
		$add_review_button_padding = ( isset( $settings['single_course_add_review_button_padding'] ) ? $settings['single_course_add_review_button_padding'] : '' );

		$add_review_form_icon_color = ( isset( $settings['single_course_add_review_form_icon_color'] ) ? $settings['single_course_add_review_form_icon_color'] : '' );
		$add_review_form_input_text_color = ( isset( $settings['single_course_add_review_form_input_text_color'] ) ? $settings['single_course_add_review_form_input_text_color'] : '' );
		$review_form_input_placeholder_color = ( isset( $settings['single_course_add_review_form_input_placeholder_color'] ) ? $settings['single_course_add_review_form_input_placeholder_color'] : '' );

		$add_review_submit_button_bg_color = ( isset( $settings['single_course_add_review_submit_button_bg_color'] ) ? $settings['single_course_add_review_submit_button_bg_color'] : '' );
		$add_review_submit_button_text_color = ( isset( $settings['single_course_add_review_submit_button_text_color'] ) ? $settings['single_course_add_review_submit_button_text_color'] : '' );
		$add_review_submit_button_hover_bg_color = ( isset( $settings['single_course_add_review_submit_button_hover_bg_color'] ) ? $settings['single_course_add_review_submit_button_hover_bg_color'] : '' );
		$add_review_submit_button_hover_text_color = ( isset( $settings['single_course_add_review_submit_button_hover_text_color'] ) ? $settings['single_course_add_review_submit_button_hover_text_color'] : '' );
		$add_review_submit_button_padding = ( isset( $settings['single_course_add_review_submit_button_padding'] ) ? $settings['single_course_add_review_submit_button_padding'] : '' );

		if ( $review_form_bg_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--reviews .academy-review-form {
                background: $review_form_bg_color;
            }";
		}

		if ( $review_form_padding ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-single-course__content-item--reviews .academy-review-form', $review_form_padding );
		}

		if ( $review_form_margin ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-single-course__content-item--reviews .academy-review-form', $review_form_margin, 'margin' );
		}

		if ( $add_review_button_bg_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--reviews .academy-review-form .academy-btn-add-review {
                background: $add_review_button_bg_color;
            }";
		}

		if ( $add_review_button_text_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--reviews .academy-review-form .academy-btn-add-review {
                color: $add_review_button_text_color;
            }";
		}

		if ( $add_review_button_hover_bg_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--reviews .academy-review-form .academy-btn-add-review:hover {
                background: $add_review_button_hover_bg_color;
            }";
		}

		if ( $add_review_button_hover_text_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--reviews .academy-review-form .academy-btn-add-review:hover {
                color: $add_review_button_hover_text_color;
            }";
		}

		if ( $add_review_button_padding ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-single-course__content-item--reviews .academy-review-form .academy-btn-add-review', $add_review_button_padding );
		}

		if ( $add_review_form_icon_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--reviews .academy-review-form .comment-respond .comment-form .stars a {
                color: $add_review_form_icon_color;
            }";
		}

		if ( $add_review_form_input_text_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--reviews .academy-review-form .comment-respond .comment-form .academy-review-form-review textarea {
                color: $add_review_form_input_text_color;
            }";
		}

		if ( $review_form_input_placeholder_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--reviews .academy-review-form .comment-respond .comment-form .academy-review-form-review textarea::placeholder {
                color: $review_form_input_placeholder_color;
            }";
		}

		if ( $add_review_submit_button_bg_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--reviews .academy-review-form .comment-respond .comment-form .form-submit input[type = submit]  {
                background: $add_review_submit_button_bg_color;
            }";
		}

		if ( $add_review_submit_button_text_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--reviews .academy-review-form .comment-respond .comment-form .form-submit input[type = submit] {
                color: $add_review_submit_button_text_color;
            }";
		}

		if ( $add_review_submit_button_hover_bg_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--reviews .academy-review-form .comment-respond .comment-form .form-submit input[type = submit]:hover {
                background: $add_review_submit_button_hover_bg_color;
            }";
		}

		if ( $add_review_submit_button_hover_text_color ) {
			$css .= ".academy-single-course .academy-single-course__content-item--reviews .academy-review-form .comment-respond .comment-form .form-submit input[type = submit]:hover {
                color: $add_review_submit_button_hover_text_color;
            }";
		}

		if ( $add_review_submit_button_padding ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-single-course__content-item--reviews .academy-review-form .comment-respond .comment-form .form-submit input[type = submit]', $add_review_submit_button_padding );
		}

		// Enroll Widget
		$enroll_widget_bg_color = ( isset( $settings['single_course_enroll_widget_bg_color'] ) ? $settings['single_course_enroll_widget_bg_color'] : '' );
		$enroll_widget_padding = ( isset( $settings['single_course_enroll_widget_padding'] ) ? $settings['single_course_enroll_widget_padding'] : '' );
		$enroll_widget_heading_text_color = ( isset( $settings['single_course_enroll_widget_heading_text_color'] ) ? $settings['single_course_enroll_widget_heading_text_color'] : '' );
		$enroll_widget_normal_price_text_color = ( isset( $settings['single_course_enroll_widget_normal_price_text_color'] ) ? $settings['single_course_enroll_widget_normal_price_text_color'] : '' );
		$enroll_widget_sale_price_text_color = ( isset( $settings['single_course_enroll_widget_sale_price_text_color'] ) ? $settings['single_course_enroll_widget_sale_price_text_color'] : '' );
		$enroll_widget_heading_margin = ( isset( $settings['single_course_enroll_widget_heading_margin'] ) ? $settings['single_course_enroll_widget_heading_margin'] : '' );
		$enroll_widget_header_separator_color = ( isset( $settings['single_course_enroll_widget_header_separator_color'] ) ? $settings['single_course_enroll_widget_header_separator_color'] : '' );
		$enroll_widget_content_text_color = ( isset( $settings['single_course_enroll_widget_content_text_color'] ) ? $settings['single_course_enroll_widget_content_text_color'] : '' );
		$enroll_widget_content_icon_color = ( isset( $settings['single_course_enroll_widget_content_icon_color'] ) ? $settings['single_course_enroll_widget_content_icon_color'] : '' );
		$enroll_widget_content_item_margin = ( isset( $settings['single_course_enroll_widget_content_item_margin'] ) ? $settings['single_course_enroll_widget_content_item_margin'] : '' );

		if ( $enroll_widget_bg_color ) {
			$css .= ".academy-single-course .academy-widget-enroll {
                background: $enroll_widget_bg_color;
            }";
		}

		if ( $enroll_widget_padding ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-widget-enroll', $enroll_widget_padding );
		}

		if ( $enroll_widget_heading_text_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__head .academy-course-type {
                color: $enroll_widget_heading_text_color;
            }";
		}

		if ( $enroll_widget_normal_price_text_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__head .academy-course-price del .amount {
                color: $enroll_widget_normal_price_text_color;
            }";
		}

		if ( $enroll_widget_sale_price_text_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__head .academy-course-price ins .amount {
                color: $enroll_widget_sale_price_text_color;
            }";
		}

		if ( $enroll_widget_heading_margin ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-widget-enroll .academy-widget-enroll__head', $enroll_widget_heading_margin, 'margin' );
		}

		if ( $enroll_widget_header_separator_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__head {
                border-bottom-color: $enroll_widget_header_separator_color;
            }";
		}

		if ( $enroll_widget_content_text_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__content ul li {
                color: $enroll_widget_content_text_color;
            }";
		}

		if ( $enroll_widget_content_icon_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__content .academy-icon:before {
                color: $enroll_widget_content_icon_color;
            }";
		}

		if ( $enroll_widget_content_item_margin ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-widget-enroll .academy-widget-enroll__content ul li', $enroll_widget_content_item_margin, 'margin' );
		}

		// Start Course Button
		$enroll_widget_start_course_button_bg_color = ( isset( $settings['single_course_enroll_widget_start_course_button_bg_color'] ) ? $settings['single_course_enroll_widget_start_course_button_bg_color'] : '' );
		$enroll_widget_start_course_button_text_color = ( isset( $settings['single_course_enroll_widget_start_course_button_text_color'] ) ? $settings['single_course_enroll_widget_start_course_button_text_color'] : '' );
		$enroll_widget_start_course_button_hover_bg_color = ( isset( $settings['single_course_enroll_widget_start_course_button_hover_bg_color'] ) ? $settings['single_course_enroll_widget_start_course_button_hover_bg_color'] : '' );
		$enroll_widget_start_course_button_hover_text_color = ( isset( $settings['single_course_enroll_widget_start_course_button_hover_text_color'] ) ? $settings['single_course_enroll_widget_start_course_button_hover_text_color'] : '' );
		$enroll_widget_start_course_button_padding = ( isset( $settings['single_course_enroll_widget_start_course_button_padding'] ) ? $settings['single_course_enroll_widget_start_course_button_padding'] : '' );
		$enroll_widget_start_course_button_margin = ( isset( $settings['single_course_enroll_widget_start_course_button_margin'] ) ? $settings['single_course_enroll_widget_start_course_button_margin'] : '' );

		if ( $enroll_widget_start_course_button_bg_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__continue a {
                background: $enroll_widget_start_course_button_bg_color;
            }";
		}

		if ( $enroll_widget_start_course_button_text_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__continue a {
                color: $enroll_widget_start_course_button_text_color;
            }";
		}

		if ( $enroll_widget_start_course_button_hover_bg_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__continue:hover a {
                background: $enroll_widget_start_course_button_hover_bg_color;
            }";
		}

		if ( $enroll_widget_start_course_button_hover_text_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__continue:hover a {
                color: $enroll_widget_start_course_button_hover_text_color;
            }";
		}

		if ( $enroll_widget_start_course_button_padding ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-widget-enroll .academy-widget-enroll__continue a', $enroll_widget_start_course_button_padding );
		}

		if ( $enroll_widget_start_course_button_margin ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-widget-enroll .academy-widget-enroll__continue', $enroll_widget_start_course_button_margin, 'margin' );
		}

		// Complete Course Button
		$enroll_widget_complete_course_button_bg_color = ( isset( $settings['single_course_enroll_widget_complete_course_button_bg_color'] ) ? $settings['single_course_enroll_widget_complete_course_button_bg_color'] : '' );
		$enroll_widget_complete_course_button_text_color = ( isset( $settings['single_course_enroll_widget_complete_course_button_text_color'] ) ? $settings['single_course_enroll_widget_complete_course_button_text_color'] : '' );
		$enroll_widget_complete_course_button_hover_bg_color = ( isset( $settings['single_course_enroll_widget_complete_course_button_hover_bg_color'] ) ? $settings['single_course_enroll_widget_complete_course_button_hover_bg_color'] : '' );
		$enroll_widget_complete_course_button_hover_text_color = ( isset( $settings['single_course_enroll_widget_complete_course_button_hover_text_color'] ) ? $settings['single_course_enroll_widget_complete_course_button_hover_text_color'] : '' );
		$enroll_widget_complete_course_button_padding = ( isset( $settings['single_course_enroll_widget_complete_course_button_padding'] ) ? $settings['single_course_enroll_widget_complete_course_button_padding'] : '' );
		$enroll_widget_complete_course_button_margin = ( isset( $settings['single_course_enroll_widget_complete_course_button_margin'] ) ? $settings['single_course_enroll_widget_complete_course_button_margin'] : '' );

		if ( $enroll_widget_complete_course_button_bg_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__complete-form button.academy-btn {
                background: $enroll_widget_complete_course_button_bg_color;
            }";
		}

		if ( $enroll_widget_complete_course_button_text_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__complete-form button.academy-btn {
                color: $enroll_widget_complete_course_button_text_color;
            }";
		}

		if ( $enroll_widget_complete_course_button_hover_bg_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__complete-form button.academy-btn:hover {
                background: $enroll_widget_complete_course_button_hover_bg_color;
            }";
		}

		if ( $enroll_widget_complete_course_button_hover_text_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__complete-form button.academy-btn:hover {
                color: $enroll_widget_complete_course_button_hover_text_color;
            }";
		}

		if ( $enroll_widget_complete_course_button_padding ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-widget-enroll .academy-widget-enroll__complete-form button.academy-btn', $enroll_widget_complete_course_button_padding );
		}

		if ( $enroll_widget_complete_course_button_margin ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-widget-enroll .academy-widget-enroll__complete-form', $enroll_widget_complete_course_button_margin, 'margin' );
		}

		// Cart Button
		$enroll_widget_cart_button_bg_color = ( isset( $settings['single_course_enroll_widget_cart_button_bg_color'] ) ? $settings['single_course_enroll_widget_cart_button_bg_color'] : '' );
		$enroll_widget_cart_button_text_color = ( isset( $settings['single_course_enroll_widget_cart_button_text_color'] ) ? $settings['single_course_enroll_widget_cart_button_text_color'] : '' );
		$enroll_widget_cart_button_hover_bg_color = ( isset( $settings['single_course_enroll_widget_cart_button_hover_bg_color'] ) ? $settings['single_course_enroll_widget_cart_button_hover_bg_color'] : '' );
		$enroll_widget_cart_button_hover_text_color = ( isset( $settings['single_course_enroll_widget_cart_button_hover_text_color'] ) ? $settings['single_course_enroll_widget_cart_button_hover_text_color'] : '' );
		$enroll_widget_cart_button_padding = ( isset( $settings['single_course_enroll_widget_cart_button_padding'] ) ? $settings['single_course_enroll_widget_cart_button_padding'] : '' );
		$enroll_widget_cart_button_margin = ( isset( $settings['single_course_enroll_widget_cart_button_margin'] ) ? $settings['single_course_enroll_widget_cart_button_margin'] : '' );

		if ( $enroll_widget_cart_button_bg_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__add-to-cart a, .academy-single-course .academy-widget-enroll .academy-widget-enroll__add-to-cart button {
                background: $enroll_widget_cart_button_bg_color;
            }";
		}

		if ( $enroll_widget_cart_button_text_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__add-to-cart a, .academy-single-course .academy-widget-enroll .academy-widget-enroll__add-to-cart button {
                color: $enroll_widget_cart_button_text_color;
            }";
		}

		if ( $enroll_widget_cart_button_hover_bg_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__add-to-cart:hover a, .academy-single-course .academy-widget-enroll .academy-widget-enroll__add-to-cart button:hover {
                background: $enroll_widget_cart_button_hover_bg_color;
            }";
		}

		if ( $enroll_widget_cart_button_hover_text_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__add-to-cart:hover a, .academy-single-course .academy-widget-enroll .academy-widget-enroll__add-to-cart button:hover {
                color: $enroll_widget_cart_button_hover_text_color;
            }";
		}

		if ( $enroll_widget_cart_button_padding ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-widget-enroll .academy-widget-enroll__add-to-cart a, .academy-single-course .academy-widget-enroll .academy-widget-enroll__add-to-cart button', $enroll_widget_cart_button_padding );
		}

		if ( $enroll_widget_cart_button_margin ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-widget-enroll .academy-widget-enroll__add-to-cart', $enroll_widget_cart_button_margin, 'margin' );
		}

		// Enroll Button
		$enroll_widget_enroll_now_button_bg_color = ( isset( $settings['single_course_enroll_widget_enroll_now_button_bg_color'] ) ? $settings['single_course_enroll_widget_enroll_now_button_bg_color'] : '' );
		$enroll_widget_enroll_now_button_text_color = ( isset( $settings['single_course_enroll_widget_enroll_now_button_text_color'] ) ? $settings['single_course_enroll_widget_enroll_now_button_text_color'] : '' );
		$enroll_widget_enroll_now_button_hover_bg_color = ( isset( $settings['single_course_enroll_widget_enroll_now_button_hover_bg_color'] ) ? $settings['single_course_enroll_widget_enroll_now_button_hover_bg_color'] : '' );
		$enroll_widget_enroll_now_button_hover_text_color = ( isset( $settings['single_course_enroll_widget_enroll_now_button_hover_text_color'] ) ? $settings['single_course_enroll_widget_enroll_now_button_hover_text_color'] : '' );
		$enroll_widget_enroll_now_button_padding = ( isset( $settings['single_course_enroll_widget_enroll_now_button_padding'] ) ? $settings['single_course_enroll_widget_enroll_now_button_padding'] : '' );
		$enroll_widget_enroll_now_button_margin = ( isset( $settings['single_course_enroll_widget_enroll_now_button_margin'] ) ? $settings['single_course_enroll_widget_enroll_now_button_margin'] : '' );

		if ( $enroll_widget_enroll_now_button_bg_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__enroll-form button.academy-btn {
                background: $enroll_widget_enroll_now_button_bg_color;
            }";
		}

		if ( $enroll_widget_enroll_now_button_text_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__enroll-form .academy-btn {
                color: $enroll_widget_enroll_now_button_text_color;
            }";
		}

		if ( $enroll_widget_enroll_now_button_hover_bg_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__enroll-form:hover .academy-btn {
                background: $enroll_widget_enroll_now_button_hover_bg_color;
            }";
		}

		if ( $enroll_widget_enroll_now_button_hover_text_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__enroll-form:hover .academy-btn {
                color: $enroll_widget_enroll_now_button_hover_text_color;
            }";
		}

		if ( $enroll_widget_enroll_now_button_padding ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-widget-enroll .academy-widget-enroll__enroll-form .academy-btn', $enroll_widget_enroll_now_button_padding );
		}

		if ( $enroll_widget_enroll_now_button_margin ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-widget-enroll .academy-widget-enroll__enroll-form', $enroll_widget_enroll_now_button_margin, 'margin' );
		}

		// Wishlist button
		$enroll_widget_wishlist_button_bg_color = ( isset( $settings['single_course_enroll_widget_wishlist_button_bg_color'] ) ? $settings['single_course_enroll_widget_wishlist_button_bg_color'] : '' );
		$enroll_widget_wishlist_button_text_color = ( isset( $settings['single_course_enroll_widget_wishlist_button_text_color'] ) ? $settings['single_course_enroll_widget_wishlist_button_text_color'] : '' );
		$enroll_widget_wishlist_button_hover_bg_color = ( isset( $settings['single_course_enroll_widget_wishlist_button_hover_bg_color'] ) ? $settings['single_course_enroll_widget_wishlist_button_hover_bg_color'] : '' );
		$enroll_widget_wishlist_button_hover_text_color = ( isset( $settings['single_course_enroll_widget_wishlist_button_hover_text_color'] ) ? $settings['single_course_enroll_widget_wishlist_button_hover_text_color'] : '' );
		$enroll_widget_wishlist_button_padding = ( isset( $settings['single_course_enroll_widget_wishlist_button_padding'] ) ? $settings['single_course_enroll_widget_wishlist_button_padding'] : '' );
		$enroll_widget_wishlist_button_margin = ( isset( $settings['single_course_enroll_widget_wishlist_button_margin'] ) ? $settings['single_course_enroll_widget_wishlist_button_margin'] : '' );

		if ( $enroll_widget_wishlist_button_bg_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__wishlist-and-share .academy-add-to-wishlist-btn {
                background: $enroll_widget_wishlist_button_bg_color;
            }";
		}

		if ( $enroll_widget_wishlist_button_text_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__wishlist-and-share .academy-add-to-wishlist-btn {
                color: $enroll_widget_wishlist_button_text_color;
            }";
		}

		if ( $enroll_widget_wishlist_button_hover_bg_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__wishlist-and-share .academy-add-to-wishlist-btn:hover {
                background: $enroll_widget_wishlist_button_hover_bg_color;
            }";
		}

		if ( $enroll_widget_wishlist_button_hover_text_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__wishlist-and-share .academy-add-to-wishlist-btn:hover {
                color: $enroll_widget_wishlist_button_hover_text_color;
            }";
		}

		if ( $enroll_widget_wishlist_button_padding ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-widget-enroll .academy-widget-enroll__wishlist-and-share .academy-add-to-wishlist-btn', $enroll_widget_wishlist_button_padding );
		}

		if ( $enroll_widget_wishlist_button_margin ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-widget-enroll .academy-widget-enroll__wishlist-and-share .academy-add-to-wishlist-btn', $enroll_widget_wishlist_button_margin, 'margin' );
		}

		// Share Button
		$enroll_widget_share_button_bg_color = ( isset( $settings['single_course_enroll_widget_share_button_bg_color'] ) ? $settings['single_course_enroll_widget_share_button_bg_color'] : '' );
		$enroll_widget_share_button_text_color = ( isset( $settings['single_course_enroll_widget_share_button_text_color'] ) ? $settings['single_course_enroll_widget_share_button_text_color'] : '' );
		$enroll_widget_share_button_hover_bg_color = ( isset( $settings['single_course_enroll_widget_share_button_hover_bg_color'] ) ? $settings['single_course_enroll_widget_share_button_hover_bg_color'] : '' );
		$enroll_widget_share_button_hover_text_color = ( isset( $settings['single_course_enroll_widget_share_button_hover_text_color'] ) ? $settings['single_course_enroll_widget_share_button_hover_text_color'] : '' );
		$enroll_widget_share_button_padding = ( isset( $settings['single_course_enroll_widget_share_button_padding'] ) ? $settings['single_course_enroll_widget_share_button_padding'] : '' );
		$enroll_widget_share_button_margin = ( isset( $settings['single_course_enroll_widget_share_button_margin'] ) ? $settings['single_course_enroll_widget_share_button_margin'] : '' );

		if ( $enroll_widget_share_button_bg_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__wishlist-and-share .academy-share-button {
                background: $enroll_widget_share_button_bg_color;
            }";
		}

		if ( $enroll_widget_share_button_text_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__wishlist-and-share .academy-share-button {
                color: $enroll_widget_share_button_text_color;
            }";
		}

		if ( $enroll_widget_share_button_hover_bg_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__wishlist-and-share .academy-share-button:hover {
                background: $enroll_widget_share_button_hover_bg_color;
            }";
		}

		if ( $enroll_widget_share_button_hover_text_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__wishlist-and-share .academy-share-button:hover {
                color: $enroll_widget_share_button_hover_text_color;
            }";
		}

		if ( $enroll_widget_share_button_padding ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-widget-enroll .academy-widget-enroll__wishlist-and-share .academy-share-button', $enroll_widget_share_button_padding );
		}

		if ( $enroll_widget_share_button_margin ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-widget-enroll .academy-widget-enroll__wishlist-and-share .academy-share-button', $enroll_widget_share_button_margin, 'margin' );
		}

		// Course Enroll Content
		$enroll_widget_course_enroll_content_bg_color   = ( isset( $settings['single_course_enroll_widget_course_enroll_content_bg_color'] ) ? $settings['single_course_enroll_widget_course_enroll_content_bg_color'] : '' );
		$enroll_widget_course_enroll_content_text_color   = ( isset( $settings['single_course_enroll_widget_course_enroll_content_text_color'] ) ? $settings['single_course_enroll_widget_course_enroll_content_text_color'] : '' );
		$enroll_widget_course_enroll_content_padding = ( isset( $settings['single_course_enroll_widget_course_enroll_content_padding'] ) ? $settings['single_course_enroll_widget_course_enroll_content_padding'] : '' );
		$enroll_widget_course_enroll_content_margin = ( isset( $settings['single_course_enroll_widget_course_enroll_content_margin'] ) ? $settings['single_course_enroll_widget_course_enroll_content_margin'] : '' );

		if ( $enroll_widget_course_enroll_content_bg_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__enrolled-info {
                background: $enroll_widget_course_enroll_content_bg_color;
            }";
		}

		if ( $enroll_widget_course_enroll_content_text_color ) {
			$css .= ".academy-single-course .academy-widget-enroll .academy-widget-enroll__enrolled-info {
                color: $enroll_widget_course_enroll_content_text_color;
            }";
		}

		if ( $enroll_widget_course_enroll_content_padding ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-widget-enroll .academy-widget-enroll__enrolled-info', $enroll_widget_course_enroll_content_padding );
		}

		if ( $enroll_widget_course_enroll_content_margin ) {
			$css .= self::generate_dimensions_css( '.academy-single-course .academy-widget-enroll .academy-widget-enroll__enrolled-info', $enroll_widget_course_enroll_content_margin, 'margin' );
		}

		return $css;
	}
}
