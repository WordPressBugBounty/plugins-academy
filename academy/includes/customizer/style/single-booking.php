<?php
namespace Academy\Customizer\Style;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Interfaces\DynamicStyleInterface;

class SingleBooking extends Base implements DynamicStyleInterface {
	public static function get_css() {
		$css = '';
		$settings = self::get_settings();

		// General Options
		$single_booking_wrapper_bg_color = ( isset( $settings['single_booking_wrapper_bg_color'] ) ? $settings['single_booking_wrapper_bg_color'] : '' );
		$single_booking_wrapper_margin = ( isset( $settings['single_booking_wrapper_margin'] ) ? $settings['single_booking_wrapper_margin'] : '' );
		$single_booking_wrapper_padding = ( isset( $settings['single_booking_wrapper_padding'] ) ? $settings['single_booking_wrapper_padding'] : '' );
		$single_booking_category_text_color = ( isset( $settings['single_booking_category_text_color'] ) ? $settings['single_booking_category_text_color'] : '' );
		$single_booking_title_color = ( isset( $settings['single_booking_title_color'] ) ? $settings['single_booking_title_color'] : '' );

		if ( $single_booking_wrapper_bg_color ) {
			$css .= ".academy-single-booking , .single-academy_courses .is-style-academylms  {
                background: $single_booking_wrapper_bg_color;
            }";
		}

		if ( $single_booking_wrapper_margin ) {
			$css .= preg_replace('/;(?=\s*})/', ' !important;', self::generate_dimensions_css(
				'.academy-single-booking, .single-academy_courses .is-style-academylms',
				$single_booking_wrapper_margin,
				'margin'
			));
		}

		if ( $single_booking_wrapper_padding ) {
			$css .= preg_replace('/;(?=\s*})/', ' !important;', self::generate_dimensions_css(
				'.academy-single-booking, .single-academy_courses .is-style-academylms',
				$single_booking_wrapper_padding,
				'padding'
			));
		}
		if ( $single_booking_category_text_color ) {
			$css .= ".academy-single-booking__category a  {
                color: $single_booking_category_text_color;
            }";
		}
		if ( $single_booking_title_color ) {
			$css .= ".academy-single-booking__title  {
                color: $single_booking_title_color;
            }";
		}

		// Instructor Area
		$instructor_title_color = ( isset( $settings['single_booking_instructor_title_color'] ) ? $settings['single_booking_instructor_title_color'] : '' );
		$instructor_name_color = ( isset( $settings['single_booking_instructor_name_color'] ) ? $settings['single_booking_instructor_name_color'] : '' );
		$review_title_color = ( isset( $settings['single_booking_review_title_color'] ) ? $settings['single_booking_review_title_color'] : '' );
		$rating_icon_color = ( isset( $settings['single_booking_rating_icon_color'] ) ? $settings['single_booking_rating_icon_color'] : '' );
		$rating_text_color = ( isset( $settings['single_booking_rating_text_color'] ) ? $settings['single_booking_rating_text_color'] : '' );
		$instructor_padding = ( isset( $settings['single_booking_instructor_padding'] ) ? $settings['single_booking_instructor_padding'] : '' );

		if ( $instructor_title_color ) {
			$css .= ".academy-single-booking__content-item--instructor .booking-single-instructor .instructor-info__content .instructor-title {
                color: $instructor_title_color;
            }";
		}

		if ( $instructor_name_color ) {
			$css .= ".academy-single-booking__content-item--instructor .booking-single-instructor .instructor-info__content .instructor-name a {
                color: $instructor_name_color;
            }";
		}

		if ( $review_title_color ) {
			$css .= ".academy-single-booking .booking-single-instructor .instructor-review .instructor-review__title , .academy-single-booking__content-item--instructors .booking-single-instructor .instructor-review__title{
                color: $review_title_color;
            }";
		}

		if ( $rating_icon_color ) {
			$css .= ".academy-single-booking .booking-single-instructor .instructor-review .instructor-review__rating .academy-group-star i:before , .academy-single-booking__content-item--instructors .booking-single-instructor .instructor-review__rating .academy-group-star .academy-icon:before{
                color: $rating_icon_color;
            }";
		}

		if ( $rating_text_color ) {
			$css .= ".academy-single-booking .booking-single-instructor .instructor-review .instructor-review__rating .instructor-review__rating-number, .academy-single-booking .booking-single-instructor .instructor-review .instructor-review__rating .instructor-review__rating-number span , .academy-single-booking__content-item--instructors .booking-single-instructor .instructor-review__rating span{
                color: $rating_text_color;
            }";
		}

		if ( $instructor_padding ) {
			$css .= self::generate_dimensions_css( '.academy-single-booking .academy-single-booking__content-item--instructors .booking-single-instructor, .academy-single-booking__content-item--instructors .booking-single-instructor', $instructor_padding );
		}

		// Content Area
		$single_booking_description_heading_text_color = ( isset( $settings['single_booking_description_heading_text_color'] ) ? $settings['single_booking_description_heading_text_color'] : '' );
		$single_booking_description_text_color = ( isset( $settings['single_booking_description_text_color'] ) ? $settings['single_booking_description_text_color'] : '' );

		if ( $single_booking_description_heading_text_color ) {
			$css .= ".academy-single-booking .academy-single-booking__content-item--description .academy-single-booking__content-item--description-title , .academy-single-booking__content-item--description-title{
                color: $single_booking_description_heading_text_color;
            }";
		}

		if ( $single_booking_description_text_color ) {
			$css .= ".academy-single-booking .academy-single-booking__content-item--description p, .academy-single-booking__content-item--description p, .academy-single-booking .academy-single-booking__content-item--description h1, .academy-single-booking .academy-single-booking__content-item--description h3, .academy-single-booking__content-item--description h4, .academy-single-booking .academy-single-booking__content-item--description h5, .academy-single-booking__content-item--description h6, .academy-single-booking__content-item--description span{
                color: $single_booking_description_text_color !important;
            }";
		}

		// Calander Aria
		$single_booking_description_heading_text_color = ( isset( $settings['single_booking_calander_text_color'] ) ? $settings['single_booking_calander_text_color'] : '' );
		$single_booking_calander_inactive_text_color = ( isset( $settings['single_booking_calander_inactive_text_color'] ) ? $settings['single_booking_calander_inactive_text_color'] : '' );
		$single_booking_calander_active_text_color = ( isset( $settings['single_booking_calander_active_text_color'] ) ? $settings['single_booking_calander_active_text_color'] : '' );
		$single_booking_calander_active_bg_color = ( isset( $settings['single_booking_calander_active_bg_color'] ) ? $settings['single_booking_calander_active_bg_color'] : '' );

		if ( $single_booking_description_heading_text_color ) {
			$css .= ".academy-select-single-label span, .react-calendar__navigation__label span , .academy_booking-template-default .academy-single-booking .academy-widget--calendar .react-calendar__navigation button.react-calendar__navigation__arrow , .academy-custom-select--indicator .academy-icon , .academy-single-booking .academy-custom-select--option {
                color: $single_booking_description_heading_text_color!important;
            }";
		}
		if ( $single_booking_calander_inactive_text_color ) {
			$css .= ".react-calendar .inactive {
                color: $single_booking_calander_inactive_text_color!important;
            }";
		}
		if ( $single_booking_calander_active_text_color ) {
			$css .= ".react-calendar .date-range-active {
                color: $single_booking_calander_active_text_color!important;
            }";
		}
		if ( $single_booking_calander_active_bg_color ) {
			$css .= ".react-calendar .date-range-active {
                background: $single_booking_calander_active_bg_color!important;
            }";
		}

		// Feedback Style
		$feedback_heading_color = ( isset( $settings['single_booking_feedback_heading_color'] ) ? $settings['single_booking_feedback_heading_color'] : '' );
		$feedback_heading_margin = ( isset( $settings['single_booking_feedback_heading_margin'] ) ? $settings['single_booking_feedback_heading_margin'] : '' );
		$feedback_bg_color = ( isset( $settings['single_booking_feedback_bg_color'] ) ? $settings['single_booking_feedback_bg_color'] : '' );
		$feedback_text_color = ( isset( $settings['single_booking_feedback_text_color'] ) ? $settings['single_booking_feedback_text_color'] : '' );
		$feedback_padding = ( isset( $settings['single_booking_feedback_padding'] ) ? $settings['single_booking_feedback_padding'] : '' );
		$avg_rating_number_color = ( isset( $settings['single_booking_avg_rating_number_color'] ) ? $settings['single_booking_avg_rating_number_color'] : '' );
		$feedback_rating_icon_color = ( isset( $settings['single_booking_feedback_rating_icon_color'] ) ? $settings['single_booking_feedback_rating_icon_color'] : '' );
		$feedback_rating_progressbar_bg_color = ( isset( $settings['single_booking_feedback_rating_progressbar_bg_color'] ) ? $settings['single_booking_feedback_rating_progressbar_bg_color'] : '' );
		$feedback_rating_progressbar_fill_color = ( isset( $settings['single_booking_feedback_rating_progressbar_fill_color'] ) ? $settings['single_booking_feedback_rating_progressbar_fill_color'] : '' );
		$rating_percentage_color = ( isset( $settings['single_booking_feedback_rating_percentage_color'] ) ? $settings['single_booking_feedback_rating_percentage_color'] : '' );

		if ( $feedback_heading_color ) {
			$css .= ".academy-single-booking .academy-single-booking__content-item--feedback .feedback-title ,.single-academy_courses .academy-single-booking__content-item--feedback .feedback-title  {
                color: $feedback_heading_color;
            }";
		}

		if ( $feedback_heading_margin ) {
			$css .= self::generate_dimensions_css( '.academy-single-booking .academy-single-booking__content-item--feedback .feedback-title , .single-academy_courses .academy-single-booking__content-item--feedback .feedback-title', $feedback_heading_margin, 'margin' );
		}

		if ( $feedback_bg_color ) {
			$css .= ".academy-single-booking .academy-single-booking__content-item--feedback .academy-student-booking-feedback-ratings ,.single-academy_courses .academy-single-booking__content-item--feedback .academy-student-booking-feedback-ratings {
                background: $feedback_bg_color;
            }";
		}

		if ( $feedback_text_color ) {
			$css .= ".academy-single-booking .academy-single-booking__content-item--feedback .academy-student-booking-feedback-ratings .academy-avg-rating-total,.single-academy_courses .academy-single-booking__content-item--feedback .academy-student-booking-feedback-ratings .academy-avg-rating-total {
                color: $feedback_text_color;
            }";
		}

		if ( $feedback_padding ) {
			$css .= self::generate_dimensions_css( '.academy-single-booking .academy-single-booking__content-item--feedback .academy-student-booking-feedback-ratings, .academy-single-booking__content-item--feedback .feedback-title ,.single-academy_courses .academy-single-booking__content-item--feedback .academy-student-booking-feedback-ratings, .academy-single-booking__content-item--feedback .feedback-title ', $feedback_padding );
		}

		if ( $avg_rating_number_color ) {
			$css .= ".academy-single-booking .academy-single-booking__content-item--feedback .academy-student-booking-feedback-ratings .academy-avg-rating , .single-academy_courses  .academy-single-booking__content-item--feedback .academy-student-booking-feedback-ratings .academy-avg-rating {
                color: $avg_rating_number_color;
            }";
		}

		if ( $feedback_rating_icon_color ) {
			$css .= ".academy-single-booking .academy-single-booking__content-item--feedback .academy-student-booking-feedback-ratings .academy-avg-rating-html i:before, .academy-single-booking .academy-single-booking__content-item--feedback .academy-student-booking-feedback-ratings .academy-ratings-list .academy-icon:before , .single-academy_courses .academy-single-booking__content-item--feedback .academy-student-booking-feedback-ratings .academy-avg-rating-html i:before, .single-academy_courses .academy-single-booking__content-item--feedback .academy-student-booking-feedback-ratings .academy-ratings-list-item .academy-icon  {
                color: $feedback_rating_icon_color;
            }";
		}

		if ( $feedback_rating_progressbar_bg_color ) {
			$css .= ".academy-single-booking .academy-single-booking__content-item--feedback .academy-student-booking-feedback-ratings .academy-ratings-list-item .academy-ratings-list-item-fill , .single-academy_courses .academy-single-booking__content-item--feedback .academy-student-booking-feedback-ratings .academy-ratings-list-item .academy-ratings-list-item-fill {
                background: $feedback_rating_progressbar_bg_color;
            }";
		}

		if ( $feedback_rating_progressbar_fill_color ) {
			$css .= ".academy-single-booking .academy-single-booking__content-item--feedback .academy-student-booking-feedback-ratings .academy-ratings-list .academy-ratings-list-item .academy-ratings-list-item-fill-bar, .single-academy_courses .academy-single-booking__content-item--feedback .academy-student-booking-feedback-ratings .academy-ratings-list .academy-ratings-list-item .academy-ratings-list-item-fill-bar {
                background: $feedback_rating_progressbar_fill_color;
            }";
		}

		if ( $rating_percentage_color ) {
			$css .= ".academy-single-booking .academy-single-booking__content-item--feedback .academy-student-booking-feedback-ratings .academy-ratings-list .academy-ratings-list-item .academy-ratings-list-item-label span , .single-academy_courses .academy-single-booking__content-item--feedback .academy-student-booking-feedback-ratings .academy-ratings-list .academy-ratings-list-item .academy-ratings-list-item-label span {
                color: $rating_percentage_color;
            }";
		}

		// Review Style
		$review_margin = ( isset( $settings['single_booking_review_margin'] ) ? $settings['single_booking_review_margin'] : '' );
		$review_padding = ( isset( $settings['single_booking_review_padding'] ) ? $settings['single_booking_review_padding'] : '' );
		$review_bg_color = ( isset( $settings['single_booking_review_bg_color'] ) ? $settings['single_booking_review_bg_color'] : '' );
		$review_rating_color = ( isset( $settings['single_booking_review_rating_color'] ) ? $settings['single_booking_review_rating_color'] : '' );
		$review_rating_icon_color = ( isset( $settings['single_booking_review_rating_icon_color'] ) ? $settings['single_booking_review_rating_icon_color'] : '' );
		$review_author_color = ( isset( $settings['single_booking_review_author_color'] ) ? $settings['single_booking_review_author_color'] : '' );
		$review_date_color = ( isset( $settings['single_booking_review_date_color'] ) ? $settings['single_booking_review_date_color'] : '' );
		$review_description_color = ( isset( $settings['single_booking_review_description_color'] ) ? $settings['single_booking_review_description_color'] : '' );

		if ( $review_bg_color ) {
			$css .= ".academy-single-booking .academy-single-booking__content-item--reviews .academy-review-list , .academy-review_container , .single-academy_courses .academy-single-booking__content-item--reviews .academy-review-list , .academy-review_container{
                background: $review_bg_color;
            }";
		}

		if ( $review_padding ) {
			$css .= self::generate_dimensions_css( '.academy-single-booking .academy-single-booking__content-item--reviews .academy-review-list , .academy-review_container, .single-academy_courses .academy-single-booking__content-item--reviews .academy-review-list , .academy-review_container', $review_padding );
		}

		if ( $review_margin ) {
			$css .= self::generate_dimensions_css( '.academy-single-booking .academy-single-booking__content-item--reviews .academy-review-list , .academy-review_container,.single-academy_courses .academy-single-booking__content-item--reviews .academy-review-list , .academy-review_container', $review_margin, 'margin' );
		}

		if ( $review_rating_color ) {
			$css .= ".academy-single-booking .academy-single-booking__content-item--reviews .academy-review__rating , .academy-review-list li .academy-review_container .academy-review-thumnail , .single-academy_courses .academy-single-booking__content-item--reviews .academy-review__rating , .academy-review-list li .academy-review_container .academy-review-thumnail {
                color: $review_rating_color;
            }";
		}

		if ( $review_rating_icon_color ) {
			$css .= ".academy-single-booking .academy-single-booking__content-item--reviews .academy-review__rating .academy-icon:before , .academy-review-list li .academy-review_container .academy-review-thumnail .academy-group-star i , .single-academy_courses .academy-single-booking__content-item--reviews .academy-review__rating .academy-icon:before , .academy-review-list li .academy-review_container .academy-review-thumnail .academy-group-star i{
                color: $review_rating_icon_color;
            }";
		}

		if ( $review_author_color ) {
			$css .= ".academy-single-booking .academy-review-list li .academy-review_container .academy-review-content .academy-review-meta__author , .academy-review-list li .academy-review_container .academy-review-content .academy-review-meta__author , .single-academy_courses .academy-review-list li .academy-review_container .academy-review-content .academy-review-meta__author , .academy-review-list li .academy-review_container .academy-review-content .academy-review-meta__author{
                color: $review_author_color;
            }";
		}

		if ( $review_date_color ) {
			$css .= ".academy-single-booking .academy-single-booking__content-item--reviews .academy-review-content .academy-review-meta__published-date , .academy-review-list li .academy-review_container .academy-review-content .academy-review-meta__published-date, .single-academy_courses .academy-single-booking__content-item--reviews .academy-review-content .academy-review-meta__published-date , .academy-review-list li .academy-review_container .academy-review-content .academy-review-meta__published-date{
                color: $review_date_color;
            }";
		}

		if ( $review_description_color ) {
			$css .= ".academy-single-booking .academy-single-booking__content-item--description p, .academy-single-booking__content-item--description p, .academy-single-booking .academy-single-booking__content-item--description h1, .academy-single-booking .academy-single-booking__content-item--description h3, .academy-single-booking__content-item--description h4, .academy-single-booking .academy-single-booking__content-item--description h5, .academy-single-booking__content-item--description h6, .academy-single-booking__content-item--description span{
                color: $review_description_color;
            }";
		}

		// Review Form Style
		$review_form_bg_color = ( isset( $settings['single_booking_review_form_bg_color'] ) ? $settings['single_booking_review_form_bg_color'] : '' );
		$review_form_margin = ( isset( $settings['single_booking_review_form_margin'] ) ? $settings['single_booking_review_form_margin'] : '' );
		$review_form_padding = ( isset( $settings['single_booking_review_form_padding'] ) ? $settings['single_booking_review_form_padding'] : '' );

		$add_review_button_bg_color = ( isset( $settings['single_booking_add_review_button_bg_color'] ) ? $settings['single_booking_add_review_button_bg_color'] : '' );
		$add_review_button_text_color = ( isset( $settings['single_booking_add_review_button_text_color'] ) ? $settings['single_booking_add_review_button_text_color'] : '' );
		$add_review_button_hover_bg_color = ( isset( $settings['single_booking_add_review_button_hover_bg_color'] ) ? $settings['single_booking_add_review_button_hover_bg_color'] : '' );
		$add_review_button_hover_text_color = ( isset( $settings['single_booking_add_review_button_hover_text_color'] ) ? $settings['single_booking_add_review_button_hover_text_color'] : '' );
		$add_review_button_padding = ( isset( $settings['single_booking_add_review_button_padding'] ) ? $settings['single_booking_add_review_button_padding'] : '' );

		$add_review_form_icon_color = ( isset( $settings['single_booking_add_review_form_icon_color'] ) ? $settings['single_booking_add_review_form_icon_color'] : '' );
		$add_review_form_input_text_color = ( isset( $settings['single_booking_add_review_form_input_text_color'] ) ? $settings['single_booking_add_review_form_input_text_color'] : '' );
		$review_form_input_placeholder_color = ( isset( $settings['single_booking_add_review_form_input_placeholder_color'] ) ? $settings['single_booking_add_review_form_input_placeholder_color'] : '' );

		$add_review_submit_button_bg_color = ( isset( $settings['single_booking_add_review_submit_button_bg_color'] ) ? $settings['single_booking_add_review_submit_button_bg_color'] : '' );
		$add_review_submit_button_text_color = ( isset( $settings['single_booking_add_review_submit_button_text_color'] ) ? $settings['single_booking_add_review_submit_button_text_color'] : '' );
		$add_review_submit_button_hover_bg_color = ( isset( $settings['single_booking_add_review_submit_button_hover_bg_color'] ) ? $settings['single_booking_add_review_submit_button_hover_bg_color'] : '' );
		$add_review_submit_button_hover_text_color = ( isset( $settings['single_booking_add_review_submit_button_hover_text_color'] ) ? $settings['single_booking_add_review_submit_button_hover_text_color'] : '' );
		$add_review_submit_button_padding = ( isset( $settings['single_booking_add_review_submit_button_padding'] ) ? $settings['single_booking_add_review_submit_button_padding'] : '' );

		if ( $review_form_bg_color ) {
			$css .= ".academy-review-form__add-review .academy-btn-add-review {
                background: $review_form_bg_color;
            }";
		}

		if ( $review_form_padding ) {
			$css .= self::generate_dimensions_css( '.academy-single-booking .academy-single-booking__content-item--reviews .academy-review-form , .single-academy_courses .academy-review-form', $review_form_padding );
		}

		if ( $review_form_margin ) {
			$css .= self::generate_dimensions_css( '.academy-single-booking .academy-single-booking__content-item--reviews .academy-review-form, .academy-single-booking  .academy-review-form', $review_form_margin, 'margin' );
		}

		if ( $add_review_button_bg_color ) {
			$css .= ".academy-single-booking .academy-single-booking__content-item--reviews .academy-review-form .academy-review-form__add-review button.academy-btn-add-review, .single-academy_courses  .academy-review-form .academy-btn-add-review {
                background: $add_review_button_bg_color;
            }";
		}

		if ( $add_review_button_text_color ) {
			$css .= ".academy-single-booking .academy-single-booking__content-item--reviews .academy-review-form .academy-btn-add-review, .single-academy_courses .academy-review-form .academy-btn-add-review {
                color: $add_review_button_text_color;
            }";
		}

		if ( $add_review_button_hover_bg_color ) {
			$css .= ".academy-single-booking .academy-single-booking__content-item--reviews .academy-review-form .academy-btn-add-review:hover, .single-academy_courses  .academy-review-form .academy-btn-add-review:hover {
                background: $add_review_button_hover_bg_color;
            }";
		}

		if ( $add_review_button_hover_text_color ) {
			$css .= ".academy-single-booking .academy-single-booking__content-item--reviews .academy-review-form .academy-btn-add-review:hover, .single-academy_courses   .academy-review-form .academy-btn-add-review:hover {
                color: $add_review_button_hover_text_color;
            }";
		}

		if ( $add_review_button_padding ) {
			$css .= self::generate_dimensions_css( '.academy-single-booking .academy-single-booking__content-item--reviews .academy-review-form .academy-btn-add-review,.single-academy_courses  .academy-review-form .academy-btn-add-review', $add_review_button_padding );
		}

		if ( $add_review_form_icon_color ) {
			$css .= ".academy-single-booking .academy-single-booking__content-item--reviews .academy-review-form .comment-respond .comment-form .stars a, .single-academy_courses  .academy-review-form .comment-respond .comment-form .stars a {
                color: $add_review_form_icon_color;
            }";
		}

		if ( $add_review_form_input_text_color ) {
			$css .= ".academy-single-booking .academy-single-booking__content-item--reviews .academy-review-form .comment-respond .comment-form .academy-review-form-review textarea,.single-academy_courses .academy-review-form .comment-respond .comment-form .academy-review-form-review textarea {
                color: $add_review_form_input_text_color;
            }";
		}

		if ( $review_form_input_placeholder_color ) {
			$css .= ".academy-single-booking .academy-single-booking__content-item--reviews .academy-review-form .comment-respond .comment-form .academy-review-form-review textarea::placeholder, .single-academy_courses  .academy-review-form .comment-respond .comment-form .academy-review-form-review textarea::placeholder {
                color: $review_form_input_placeholder_color;
            }";
		}

		if ( $add_review_submit_button_bg_color ) {
			$css .= ".academy-single-booking .academy-single-booking__content-item--reviews .academy-review-form .comment-respond .comment-form .form-submit input[type = submit] , .single-academy_courses .academy-review-form .comment-respond .comment-form .form-submit input[type = submit]  {
                background: $add_review_submit_button_bg_color;
            }";
		}

		if ( $add_review_submit_button_text_color ) {
			$css .= ".academy-single-booking .academy-single-booking__content-item--reviews .academy-review-form .comment-respond .comment-form .form-submit input[type = submit],.single-academy_courses .academy-review-form .comment-respond .comment-form .form-submit input[type = submit] {
                color: $add_review_submit_button_text_color;
            }";
		}

		if ( $add_review_submit_button_hover_bg_color ) {
			$css .= ".academy-single-booking .academy-single-booking__content-item--reviews .academy-review-form .comment-respond .comment-form .form-submit input[type = submit]:hover, .single-academy_courses .academy-review-form .comment-respond .comment-form .form-submit input[type = submit]:hover {
                background: $add_review_submit_button_hover_bg_color;
            }";
		}

		if ( $add_review_submit_button_hover_text_color ) {
			$css .= ".academy-single-booking .academy-single-booking__content-item--reviews .academy-review-form .comment-respond .comment-form .form-submit input[type = submit]:hover,.single-academy_courses .academy-review-form .comment-respond .comment-form .form-submit input[type = submit]:hover {
                color: $add_review_submit_button_hover_text_color;
            }";
		}

		if ( $add_review_submit_button_padding ) {
			$css .= self::generate_dimensions_css( '.academy-single-booking .academy-single-booking__content-item--reviews .academy-review-form .comment-respond .comment-form .form-submit input[type = submit], .single-academy_courses .academy-review-form .comment-respond .comment-form .form-submit input[type = submit]', $add_review_submit_button_padding );
		}

		// Enroll Widget
		$booking_widget_bg_color = ( isset( $settings['single_booking_enroll_widget_bg_color'] ) ? $settings['single_booking_enroll_widget_bg_color'] : '' );
		$booking_widget_padding = ( isset( $settings['single_booking_enroll_widget_padding'] ) ? $settings['single_booking_enroll_widget_padding'] : '' );
		$booking_widget_heading_text_color = ( isset( $settings['single_booking_enroll_widget_heading_text_color'] ) ? $settings['single_booking_enroll_widget_heading_text_color'] : '' );
		$booking_widget_normal_price_text_color = ( isset( $settings['single_booking_enroll_widget_normal_price_text_color'] ) ? $settings['single_booking_enroll_widget_normal_price_text_color'] : '' );
		$booking_widget_sale_price_text_color = ( isset( $settings['single_booking_enroll_widget_sale_price_text_color'] ) ? $settings['single_booking_enroll_widget_sale_price_text_color'] : '' );
		$booking_widget_heading_margin = ( isset( $settings['single_booking_enroll_widget_heading_margin'] ) ? $settings['single_booking_enroll_widget_heading_margin'] : '' );
		$booking_widget_header_separator_color = ( isset( $settings['single_booking_enroll_widget_header_separator_color'] ) ? $settings['single_booking_enroll_widget_header_separator_color'] : '' );
		$booking_widget_content_text_color = ( isset( $settings['single_booking_enroll_widget_content_text_color'] ) ? $settings['single_booking_enroll_widget_content_text_color'] : '' );
		$booking_widget_content_icon_color = ( isset( $settings['single_booking_enroll_widget_content_icon_color'] ) ? $settings['single_booking_enroll_widget_content_icon_color'] : '' );
		$booking_widget_content_item_margin = ( isset( $settings['single_booking_enroll_widget_content_item_margin'] ) ? $settings['single_booking_enroll_widget_content_item_margin'] : '' );

		if ( $booking_widget_bg_color ) {
			$css .= ".academy-single-booking .academy-widget-booking ,.single-academy_courses  .academy-widget-booking__continue , .single-academy_courses .academy-widget-booking__complete-form, .single-academy_courses .academy-widget-booking__head ,.single-academy_courses  .academy-widget-booking__add-to-cart ,.single-academy_courses .academy-g-plus-enroll-content-team , .single-academy_courses .academy-widget-booking .academy-widget-booking-tab-head , .single-academy_courses .academy-widget-booking__add-to-cart , .single-academy_courses .academy-widget-booking__enroll-form , .single-academy_courses .academy-widget-booking-tab-head > .academy-btn-enroll-tab , .single-academy_courses .academy-widget-booking .academy-g-plus-enroll-content-team{
                background: $booking_widget_bg_color;
            }";
		}

		if ( $booking_widget_padding ) {
			$css .= self::generate_dimensions_css( '.academy-single-booking .academy-widget-booking, .academy-single-booking .academy-widget-booking , .single-academy_courses .academy-widget-booking__continue , .single-academy_courses .academy-widget-booking__complete-form, .single-academy_courses .academy-widget-booking__head , .single-academy_courses .academy-widget-booking__add-to-cart , .single-academy_courses .academy-g-plus-enroll-content-team , .single-academy_courses .academy-widget-booking .academy-widget-booking-tab-head ,.single-academy_courses  .academy-widget-booking__add-to-cart , .single-academy_courses .academy-widget-booking__enroll-form ,.single-academy_courses  .academy-widget-booking-tab-head > .academy-btn-enroll-tab , .single-academy_courses .academy-widget-booking .academy-g-plus-enroll-content-team', $booking_widget_padding );
		}

		if ( $booking_widget_heading_text_color ) {
			$css .= ".academy-single-booking .academy-widget-booking .academy-widget-booking__head .academy-course-type , .academy-widget-booking__head .academy-course-type{
                color: $booking_widget_heading_text_color;
            }";
		}

		if ( $booking_widget_normal_price_text_color ) {
			$css .= ".academy-single-booking .academy-widget-booking .academy-widget-booking__head .academy-course-price del .amount , .g-plus > .price_area > .price_info > .price , .academy-widget-booking__head .academy-course-price del .storeengine-price , .academy-widget-booking__head .academy-booking-type , .academy-widget-booking__head .academy-booking-price del{
                color: $booking_widget_normal_price_text_color;
            }";
		}

		if ( $booking_widget_sale_price_text_color ) {
			$css .= ".academy-single-booking .academy-widget-booking .academy-widget-booking__head .academy-course-price ins .amount , .g-plus > .price_area > .price_info > .sale_price , .academy-widget-booking__head .academy-course-price ins .storeengine-price , .academy-widget-booking__head .academy-booking-price ins
{
                color: $booking_widget_sale_price_text_color;
            }";
		}

		if ( $booking_widget_heading_margin ) {
			$css .= self::generate_dimensions_css( '.academy-single-booking .academy-widget-booking .academy-widget-booking__head', $booking_widget_heading_margin, 'margin' );
		}

		if ( $booking_widget_header_separator_color ) {
			$css .= ".academy-single-booking .academy-widget-booking .academy-widget-booking__head {
                border-bottom-color: $booking_widget_header_separator_color;
            }";
		}

		if ( $booking_widget_content_text_color ) {
			$css .= ".academy-single-booking .academy-widget-booking .academy-widget-booking__content ul li , .academy-widget-booking__content-lists li , .single-academy_courses .academy-widget-booking__content-lists li{
                color: $booking_widget_content_text_color;
            }";
		}

		if ( $booking_widget_content_icon_color ) {
			$css .= ".academy-single-booking .academy-widget-booking .academy-widget-booking__content .academy-icon:before ,.academy-widget-booking__content-lists li .academy-icon:before , .single-academy_courses .academy-widget-booking__content-lists li  .academy-icon:before{
                color: $booking_widget_content_icon_color;
            }";
		}

		if ( $booking_widget_content_item_margin ) {
			$css .= self::generate_dimensions_css( '.academy-single-booking .academy-widget-booking .academy-widget-booking__content ul li , .academy-widget-booking__content-lists li', $booking_widget_content_item_margin, 'margin' );
		}

		// Booking Button
		$booking_widget_start_course_button_bg_color = ( isset( $settings['single_booking_enroll_widget_start_course_button_bg_color'] ) ? $settings['single_booking_enroll_widget_start_course_button_bg_color'] : '' );
		$booking_widget_start_course_button_text_color = ( isset( $settings['single_booking_enroll_widget_start_course_button_text_color'] ) ? $settings['single_booking_enroll_widget_start_course_button_text_color'] : '' );
		$booking_widget_start_course_button_hover_bg_color = ( isset( $settings['single_booking_enroll_widget_start_course_button_hover_bg_color'] ) ? $settings['single_booking_enroll_widget_start_course_button_hover_bg_color'] : '' );
		$booking_widget_start_course_button_hover_text_color = ( isset( $settings['single_booking_enroll_widget_start_course_button_hover_text_color'] ) ? $settings['single_booking_enroll_widget_start_course_button_hover_text_color'] : '' );
		$booking_widget_start_course_button_padding = ( isset( $settings['single_booking_enroll_widget_start_course_button_padding'] ) ? $settings['single_booking_enroll_widget_start_course_button_padding'] : '' );
		$booking_widget_start_course_button_margin = ( isset( $settings['single_booking_enroll_widget_start_course_button_margin'] ) ? $settings['single_booking_enroll_widget_start_course_button_margin'] : '' );
		$single_booking_enroll_widget_course_enroll_content_bg_color = ( isset( $settings['single_booking_enroll_widget_course_enroll_content_bg_color'] ) ? $settings['single_booking_enroll_widget_course_enroll_content_bg_color'] : '' );

		if ( $booking_widget_start_course_button_bg_color ) {
			$css .= ".academy-widget-booking__footer a , .academy-single-booking .academy-widget-booking__continue .academy-btn , .single-academy_courses .academy-widget-booking .academy-widget-booking__continue a , .single-academy_courses .academy-widget-booking__continue .academy-btn{
                background: $booking_widget_start_course_button_bg_color;
            }";
		}

		if ( $booking_widget_start_course_button_text_color ) {
			$css .= ".academy-widget-booking__footer a, .single-academy_courses .academy-widget-booking .academy-widget-booking__continue a, .single-academy_courses .academy-widget-booking__continue .academy-btn{
                color: $booking_widget_start_course_button_text_color !important;
            }";
		}

		if ( $booking_widget_start_course_button_hover_bg_color ) {
			$css .= ".academy-widget-booking__footer a:hover , .single-academy_courses .academy-widget-booking .academy-widget-booking__continue:hover a{
                background: $booking_widget_start_course_button_hover_bg_color;
            }";
		}

		if ( $booking_widget_start_course_button_hover_text_color ) {
			$css .= ".academy-widget-booking__footer a:hover , .single-academy_courses .academy-widget-booking .academy-widget-booking__continue:hover a{
                color: $booking_widget_start_course_button_hover_text_color !important;
            }";
		}

		if ( $booking_widget_start_course_button_padding ) {
			$css .= self::generate_dimensions_css( '.academy-widget-booking__footer a ,  .single-academy_courses .academy-widget-booking .academy-widget-booking__continue a', $booking_widget_start_course_button_padding );
		}

		if ( $booking_widget_start_course_button_margin ) {
			$css .= self::generate_dimensions_css( '.academy-widget-booking__footer a ,  .single-academy_courses .academy-widget-booking .academy-widget-booking__continue a', $booking_widget_start_course_button_margin, 'margin' );
		}

		if ( $single_booking_enroll_widget_course_enroll_content_bg_color ) {
			$css .= ".academy-widget-booking__footer a{
                color: $booking_widget_start_course_button_hover_text_color;
            }";
		}

		// Enroll Button
		$booking_widget_enroll_now_button_bg_color = ( isset( $settings['single_booking_enroll_widget_enroll_now_button_bg_color'] ) ? $settings['single_booking_enroll_widget_enroll_now_button_bg_color'] : '' );
		$booking_widget_enroll_now_button_text_color = ( isset( $settings['single_booking_enroll_widget_enroll_now_button_text_color'] ) ? $settings['single_booking_enroll_widget_enroll_now_button_text_color'] : '' );
		$booking_widget_enroll_now_button_hover_bg_color = ( isset( $settings['single_booking_enroll_widget_enroll_now_button_hover_bg_color'] ) ? $settings['single_booking_enroll_widget_enroll_now_button_hover_bg_color'] : '' );
		$booking_widget_enroll_now_button_hover_text_color = ( isset( $settings['single_booking_enroll_widget_enroll_now_button_hover_text_color'] ) ? $settings['single_booking_enroll_widget_enroll_now_button_hover_text_color'] : '' );
		$booking_widget_enroll_now_button_padding = ( isset( $settings['single_booking_enroll_widget_enroll_now_button_padding'] ) ? $settings['single_booking_enroll_widget_enroll_now_button_padding'] : '' );
		$booking_widget_enroll_now_button_margin = ( isset( $settings['single_booking_enroll_widget_enroll_now_button_margin'] ) ? $settings['single_booking_enroll_widget_enroll_now_button_margin'] : '' );

		if ( $booking_widget_enroll_now_button_bg_color ) {
			$css .= ".academy-single-booking .academy-widget-booking .academy-widget-booking__enroll-form button.academy-btn, .single-academy_courses .academy-widget-booking .academy-widget-booking__enroll-form button.academy-btn {
                background: $booking_widget_enroll_now_button_bg_color;
            }";
		}

		if ( $booking_widget_enroll_now_button_text_color ) {
			$css .= ".academy-single-booking .academy-widget-booking .academy-widget-booking__enroll-form .academy-btn, .single-academy_courses .academy-widget-booking .academy-widget-booking__enroll-form .academy-btn {
                color: $booking_widget_enroll_now_button_text_color;
            }";
		}

		if ( $booking_widget_enroll_now_button_hover_bg_color ) {
			$css .= ".academy-single-booking .academy-widget-booking .academy-widget-booking__enroll-form:hover .academy-btn, .single-academy_courses .academy-widget-booking .academy-widget-booking__enroll-form:hover .academy-btn {
                background: $booking_widget_enroll_now_button_hover_bg_color;
            }";
		}

		if ( $booking_widget_enroll_now_button_hover_text_color ) {
			$css .= ".academy-single-booking .academy-widget-booking .academy-widget-booking__enroll-form:hover .academy-btn, .single-academy_courses .academy-widget-booking .academy-widget-booking__enroll-form:hover .academy-btn {
                color: $booking_widget_enroll_now_button_hover_text_color;
            }";
		}

		if ( $booking_widget_enroll_now_button_padding ) {
			$css .= self::generate_dimensions_css( '.academy-single-booking .academy-widget-booking .academy-widget-booking__enroll-form .academy-btn , .single-academy_courses .academy-widget-booking .academy-widget-booking__enroll-form .academy-btn', $booking_widget_enroll_now_button_padding );
		}

		if ( $booking_widget_enroll_now_button_margin ) {
			$css .= self::generate_dimensions_css( '.academy-single-booking .academy-widget-booking .academy-widget-booking__enroll-form, .single-academy_courses .academy-widget-booking .academy-widget-booking__enroll-form', $booking_widget_enroll_now_button_margin, 'margin' );
		}

		return $css;
	}
}
