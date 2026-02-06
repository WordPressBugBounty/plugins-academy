<?php
namespace Academy\Customizer\Style;

use Academy\Interfaces\DynamicStyleInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class ArchiveBooking extends Base implements DynamicStyleInterface {
	public static function get_css() {
		$css = '';
		$settings = self::get_settings();

		// Header Color Options
		$archive_tutor_booking_header_bg_color = ( isset( $settings['archive_tutor_booking_header_bg_color'] ) ? $settings['archive_tutor_booking_header_bg_color'] : '' );
		$archive_tutor_booking_header_pading = ( isset( $settings['archive_tutor_booking_header_pading'] ) ? $settings['archive_tutor_booking_header_pading'] : '' );
		$archive_tutor_booking_header_margin = ( isset( $settings['archive_tutor_booking_header_margin'] ) ? $settings['archive_tutor_booking_header_margin'] : '' );
		$archive_tutor_booking_header_course_count_color = ( isset( $settings['archive_tutor_booking_header_Booking_count_color'] ) ? $settings['archive_tutor_booking_header_Booking_count_color'] : '' );
		$archive_tutor_booking_header_sorting_bg_color = ( isset( $settings['archive_tutor_booking_header_sorting_bg_color'] ) ? $settings['archive_tutor_booking_header_sorting_bg_color'] : '' );
		$archive_tutor_booking_header_sorting_color = ( isset( $settings['archive_tutor_booking_header_sorting_color'] ) ? $settings['archive_tutor_booking_header_sorting_color'] : '' );

		if ( $archive_tutor_booking_header_bg_color ) {
			$css .= ".academy-bookings .academy-bookings__header, .academy-bookings__header {
                background: $archive_tutor_booking_header_bg_color;
            }";
		}

		if ( $archive_tutor_booking_header_pading ) {
			$css .= self::generate_dimensions_css( '.academy-bookings .academy-bookings__header , .academy-bookings__header', $archive_tutor_booking_header_pading );
		}

		if ( $archive_tutor_booking_header_margin ) {
			$css .= self::generate_dimensions_css( '.academy-bookings .academy-bookings__header, .academy-bookings__header', $archive_tutor_booking_header_margin, 'margin' );
		}

		if ( $archive_tutor_booking_header_course_count_color ) {
			$css .= ".academy-bookings .academy-bookings__header .academy-bookings__header-result-count, .academy-bookings__header {
                color: $archive_tutor_booking_header_course_count_color;
            }";
		}

		if ( $archive_tutor_booking_header_sorting_bg_color ) {
			$css .= ".academy-bookings .academy-bookings__header .academy-bookings__header-ordering select , .academy-bookings__header .academy-bookings__header-ordering select{
                background: $archive_tutor_booking_header_sorting_bg_color;
            }";
		}

		if ( $archive_tutor_booking_header_sorting_color ) {
			$css .= ".academy-bookings .academy-bookings__header .academy-bookings__header-ordering select , .academy-bookings__header .academy-bookings__header-ordering select {
                color: $archive_tutor_booking_header_sorting_color;
            }";
		}

		// Course Card
		$archive_tutor_booking_course_card_bg_color = ( isset( $settings['archive_tutor_booking_Booking_card_bg_color'] ) ? $settings['archive_tutor_booking_Booking_card_bg_color'] : '' );
		$archive_tutor_booking_course_card_content_padding = ( isset( $settings['archive_tutor_booking_course_card_content_padding'] ) ? $settings['archive_tutor_booking_course_card_content_padding'] : '' );
		$archive_tutor_booking_course_category_color = ( isset( $settings['archive_tutor_booking_Booking_category_color'] ) ? $settings['archive_tutor_booking_Booking_category_color'] : '' );
		$archive_tutor_booking_course_title_color = ( isset( $settings['archive_tutor_booking_Booking_title_color'] ) ? $settings['archive_tutor_booking_Booking_title_color'] : '' );
		$archive_tutor_booking_course_author_color = ( isset( $settings['archive_tutor_booking_Booking_author_color'] ) ? $settings['archive_tutor_booking_Booking_author_color'] : '' );
		$archive_tutor_booking_course_footer_separator_color = ( isset( $settings['archive_tutor_booking_Booking_footer_separator_color'] ) ? $settings['archive_tutor_booking_Booking_footer_separator_color'] : '' );
		$archive_tutor_booking_course_card_footer_padding = ( isset( $settings['archive_tutor_booking_course_card_footer_padding'] ) ? $settings['archive_tutor_booking_course_card_footer_padding'] : '' );
		$archive_tutor_booking_course_rating_icon_color = ( isset( $settings['archive_tutor_booking_Booking_rating_icon_color'] ) ? $settings['archive_tutor_booking_Booking_rating_icon_color'] : '' );
		$archive_tutor_booking_course_rating_color = ( isset( $settings['archive_tutor_booking_Booking_rating_color'] ) ? $settings['archive_tutor_booking_Booking_rating_color'] : '' );
		$archive_tutor_booking_course_rating_count_color = ( isset( $settings['archive_tutor_booking_Booking_rating_count_color'] ) ? $settings['archive_tutor_booking_Booking_rating_count_color'] : '' );
		$archive_tutor_booking_course_price_color = ( isset( $settings['archive_tutor_booking_Booking_price_color'] ) ? $settings['archive_tutor_booking_Booking_price_color'] : '' );
		$archive_tutor_booking_normal_price_text_color = ( isset( $settings['archive_tutor_booking_normal_price_text_color'] ) ? $settings['archive_tutor_booking_normal_price_text_color'] : '' );
		$archive_tutor_booking_sale_price_text_color = ( isset( $settings['archive_tutor_booking_sale_price_text_color'] ) ? $settings['archive_tutor_booking_sale_price_text_color'] : '' );

		if ( $archive_tutor_booking_course_card_bg_color ) {
			$css .= ".academy-bookings .academy-booking {
                background: $archive_tutor_booking_course_card_bg_color;
            }";
		}

		if ( $archive_tutor_booking_course_card_content_padding ) {
			$css .= self::generate_dimensions_css( '.academy-booking .academy-booking__body', $archive_tutor_booking_course_card_content_padding );
		}

		if ( $archive_tutor_booking_course_category_color ) {
			$css .= ".academy-bookings .academy-booking__meta--category a {
                color: $archive_tutor_booking_course_category_color;
            }";
		}

		if ( $archive_tutor_booking_course_title_color ) {
			$css .= ".academy-bookings .academy-booking__title a {
                color: $archive_tutor_booking_course_title_color;
            }";
		}

		if ( $archive_tutor_booking_course_author_color ) {
			$css .= ".academy-bookings .academy-booking__author .author, .academy-bookings .academy-bookings__body .academy-booking__author .author a, .academy-bookings .academy-booking__author a {
                color: $archive_tutor_booking_course_author_color;
            }";
		}

		if ( $archive_tutor_booking_course_footer_separator_color ) {
			$css .= ".academy-bookings .academy-booking__footer {
                border-top-color: $archive_tutor_booking_course_footer_separator_color;
            }";
		}

		if ( $archive_tutor_booking_course_card_footer_padding ) {
			$css .= self::generate_dimensions_css( '.academy-bookings .academy-booking__footer', $archive_tutor_booking_course_card_footer_padding );
		}

		if ( $archive_tutor_booking_course_rating_icon_color ) {
			$css .= ".academy-bookings .academy-booking__footer .academy-booking__rating .academy-group-star .academy-icon:before {
                color: $archive_tutor_booking_course_rating_icon_color;
            }";
		}

		if ( $archive_tutor_booking_course_rating_color ) {
			$css .= ".academy-bookings .academy-booking__footer .academy-booking__rating {
                color: $archive_tutor_booking_course_rating_color;
            }";
		}

		if ( $archive_tutor_booking_course_rating_count_color ) {
			$css .= ".academy-bookings .academy-booking__footer .academy-booking__rating .academy-booking__rating-count {
                color: $archive_tutor_booking_course_rating_count_color;
            }";
		}

		if ( $archive_tutor_booking_course_price_color ) {
			$css .= ".academy-bookings .academy-booking__footer .academy-booking__price {
                color: $archive_tutor_booking_course_price_color;
            }";
		}

		if ( $archive_tutor_booking_normal_price_text_color ) {
			$css .= ".academy-bookings .academy-booking__footer .academy-booking__price del .amount , .academy-bookings .academy-booking__price del {
                color: $archive_tutor_booking_normal_price_text_color;
            }";
		}

		if ( $archive_tutor_booking_sale_price_text_color ) {
			$css .= ".academy-bookings .academy-booking__footer .academy-booking__price ins .amount , .academy-bookings .academy-booking__price span ins{
                color: $archive_tutor_booking_sale_price_text_color;
            }";
		}

		// Pagination Styles
		$archive_tutor_booking_course_pagination_padding = ( isset( $settings['archive_tutor_booking_course_pagination_padding'] ) ? $settings['archive_tutor_booking_course_pagination_padding'] : '' );
		$archive_tutor_booking_course_pagination_margin = ( isset( $settings['archive_tutor_booking_course_pagination_margin'] ) ? $settings['archive_tutor_booking_course_pagination_margin'] : '' );
		$archive_tutor_booking_pagination_active_button_bg_color = ( isset( $settings['archive_tutor_booking_pagination_active_button_bg_color'] ) ? $settings['archive_tutor_booking_pagination_active_button_bg_color'] : '' );
		$archive_tutor_booking_pagination_active_button_color = ( isset( $settings['archive_tutor_booking_pagination_active_button_color'] ) ? $settings['archive_tutor_booking_pagination_active_button_color'] : '' );
		$archive_tutor_booking_pagination_normal_button_bg_color = ( isset( $settings['archive_tutor_booking_pagination_normal_button_bg_color'] ) ? $settings['archive_tutor_booking_pagination_normal_button_bg_color'] : '' );
		$archive_tutor_booking_pagination_normal_button_color = ( isset( $settings['archive_tutor_booking_pagination_normal_button_color'] ) ? $settings['archive_tutor_booking_pagination_normal_button_color'] : '' );
		$archive_tutor_booking_next_prev_pagination_button_bg_color = ( isset( $settings['archive_tutor_booking_next_prev_pagination_button_bg_color'] ) ? $settings['archive_tutor_booking_next_prev_pagination_button_bg_color'] : '' );
		$archive_tutor_booking_next_prev_pagination_button_text_color = ( isset( $settings['archive_tutor_booking_next_prev_pagination_button_text_color'] ) ? $settings['archive_tutor_booking_next_prev_pagination_button_text_color'] : '' );

		if ( $archive_tutor_booking_course_pagination_padding ) {
			$css .= self::generate_dimensions_css( '.academy-bookings .academy-bookings__pagination .page-numbers', $archive_tutor_booking_course_pagination_padding );
		}

		if ( $archive_tutor_booking_course_pagination_margin ) {
			$css .= self::generate_dimensions_css( '.academy-bookings .academy-bookings__pagination .page-numbers', $archive_tutor_booking_course_pagination_margin, 'margin' );
		}

		if ( $archive_tutor_booking_pagination_active_button_bg_color ) {
			$css .= ".academy-bookings .academy-bookings__pagination .page-numbers.current, .academy-bookings .academy-bookings__pagination .page-numbers:hover {
                background: $archive_tutor_booking_pagination_active_button_bg_color;
            }";
		}

		if ( $archive_tutor_booking_pagination_active_button_color ) {
			$css .= ".academy-bookings .academy-bookings__pagination .page-numbers.current, .academy-bookings .academy-bookings__pagination .page-numbers:hover {
                color: $archive_tutor_booking_pagination_active_button_color;
            }";
		}

		if ( $archive_tutor_booking_pagination_normal_button_bg_color ) {
			$css .= ".academy-bookings .academy-bookings__pagination .page-numbers {
                background: $archive_tutor_booking_pagination_normal_button_bg_color;
            }";
		}

		if ( $archive_tutor_booking_pagination_normal_button_color ) {
			$css .= ".academy-bookings .academy-bookings__pagination .page-numbers {
                color: $archive_tutor_booking_pagination_normal_button_color;
            }";
		}

		if ( $archive_tutor_booking_next_prev_pagination_button_bg_color ) {
			$css .= ".academy-bookings .academy-bookings__pagination .next.page-numbers, .academy-bookings .academy-bookings__pagination .prev.page-numbers {
                background: $archive_tutor_booking_next_prev_pagination_button_bg_color;
            }";
		}

		if ( $archive_tutor_booking_next_prev_pagination_button_text_color ) {
			$css .= ".academy-bookings .academy-bookings__pagination .next.page-numbers i, .academy-bookings .academy-bookings__pagination .prev.page-numbers i {
                color: $archive_tutor_booking_next_prev_pagination_button_text_color;
            }";
		}

		// Sidebar Filter Styles
		$archive_tutor_booking_sidebar_bg_color = ( isset( $settings['archive_tutor_booking_sidebar_bg_color'] ) ? $settings['archive_tutor_booking_sidebar_bg_color'] : '' );
		$archive_tutor_booking_course_sidebar_padding = ( isset( $settings['archive_tutor_booking_course_sidebar_padding'] ) ? $settings['archive_tutor_booking_course_sidebar_padding'] : '' );
		$archive_tutor_booking_course_sidebar_filter_margin = ( isset( $settings['archive_tutor_booking_course_sidebar_filter_margin'] ) ? $settings['archive_tutor_booking_course_sidebar_filter_margin'] : '' );
		$archive_tutor_booking_sidebar_searchbox_bg_color = ( isset( $settings['archive_tutor_booking_sidebar_searchbox_bg_color'] ) ? $settings['archive_tutor_booking_sidebar_searchbox_bg_color'] : '' );
		$archive_tutor_booking_sidebar_searchbox_placeholder_text_color = ( isset( $settings['archive_tutor_booking_sidebar_searchbox_placeholder_text_color'] ) ? $settings['archive_tutor_booking_sidebar_searchbox_placeholder_text_color'] : '' );
		$archive_tutor_booking_sidebar_searchbox_text_color = ( isset( $settings['archive_tutor_booking_sidebar_searchbox_text_color'] ) ? $settings['archive_tutor_booking_sidebar_searchbox_text_color'] : '' );
		$archive_tutor_booking_sidebar_filter_heading_color = ( isset( $settings['archive_tutor_booking_sidebar_filter_heading_color'] ) ? $settings['archive_tutor_booking_sidebar_filter_heading_color'] : '' );
		$archive_tutor_booking_sidebar_filter_checkbox_bg_color = ( isset( $settings['archive_tutor_booking_sidebar_filter_checkbox_bg_color'] ) ? $settings['archive_tutor_booking_sidebar_filter_checkbox_bg_color'] : '' );
		$archive_tutor_booking_sidebar_filter_checkbox_border_color = ( isset( $settings['archive_tutor_booking_sidebar_filter_checkbox_border_color'] ) ? $settings['archive_tutor_booking_sidebar_filter_checkbox_border_color'] : '' );
		$archive_tutor_booking_sidebar_filter_item_color = ( isset( $settings['archive_tutor_booking_sidebar_filter_item_color'] ) ? $settings['archive_tutor_booking_sidebar_filter_item_color'] : '' );
		$archive_tutor_booking_sidebar_filter_heading_color = ( isset( $settings['archive_tutor_booking_sidebar_filter_heading_color'] ) ? $settings['archive_tutor_booking_sidebar_filter_heading_color'] : '' );
		$archive_tutor_booking_sidebar_options_heading = ( isset( $settings['archive_tutor_booking_sidebar_options_heading'] ) ? $settings['archive_tutor_booking_sidebar_options_heading'] : '' );
		$archive_tutor_booking_Booking_sidebar_padding = ( isset( $settings['archive_tutor_booking_Booking_sidebar_padding'] ) ? $settings['archive_tutor_booking_Booking_sidebar_padding'] : '' );

		if ( $archive_tutor_booking_sidebar_bg_color ) {
			$css .= ".academy-bookings__sidebar {
                background: $archive_tutor_booking_sidebar_bg_color;
            }";
		}
		if ( $archive_tutor_booking_Booking_sidebar_padding ) {
			$css .= self::generate_dimensions_css( '.academy-bookings__sidebar', $archive_tutor_booking_Booking_sidebar_padding );
		}
		if ( $archive_tutor_booking_sidebar_options_heading ) {
			$css .= ".academy-booking-filters {
                background: $archive_tutor_booking_sidebar_options_heading;
            }";
		}

		if ( $archive_tutor_booking_course_sidebar_padding ) {
			$css .= self::generate_dimensions_css( '.academy-booking-filters', $archive_tutor_booking_course_sidebar_padding );
		}

		if ( $archive_tutor_booking_course_sidebar_filter_margin ) {
			$css .= self::generate_dimensions_css( '.academy-bookings .academy-bookings__sidebar .academy-archive-booking-widget', $archive_tutor_booking_course_sidebar_filter_margin, 'margin' );
		}

		if ( $archive_tutor_booking_sidebar_searchbox_bg_color ) {
			$css .= ".academy-bookings__sidebar .academy-archive-booking-widget--search input.academy-archive-booking-search {
                background: $archive_tutor_booking_sidebar_searchbox_bg_color;
            }";
		}

		if ( $archive_tutor_booking_sidebar_searchbox_placeholder_text_color ) {
			$css .= ".academy-bookings__sidebar .academy-archive-booking-widget--search input.academy-archive-booking-search::placeholder {
                color: $archive_tutor_booking_sidebar_searchbox_placeholder_text_color;
            }";
		}

		if ( $archive_tutor_booking_sidebar_searchbox_text_color ) {
			$css .= ".academy-bookings__sidebar .academy-archive-booking-widget--search input.academy-archive-booking-search {
                color: $archive_tutor_booking_sidebar_searchbox_text_color;
            }";
		}

		if ( $archive_tutor_booking_sidebar_filter_heading_color ) {
			$css .= ".academy-bookings__sidebar .academy-archive-booking-widget__title {
                color: $archive_tutor_booking_sidebar_filter_heading_color;
            }";
		}

		if ( $archive_tutor_booking_sidebar_filter_checkbox_bg_color ) {
			$css .= ".academy-bookings__sidebar .academy-archive-booking-widget__body label .checkmark {
                background: $archive_tutor_booking_sidebar_filter_checkbox_bg_color;
            }";
		}

		if ( $archive_tutor_booking_sidebar_filter_checkbox_border_color ) {
			$css .= ".academy-bookings__sidebar .academy-archive-booking-widget__body label .checkmark {
                border-color: $archive_tutor_booking_sidebar_filter_checkbox_border_color;
            }";
		}

		if ( $archive_tutor_booking_sidebar_filter_item_color ) {
			$css .= ".academy-bookings__sidebar .academy-archive-booking-widget__body label {
                color: $archive_tutor_booking_sidebar_filter_item_color;
            }";
		}

		return $css;
	}
}
