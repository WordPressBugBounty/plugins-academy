<?php
namespace Academy\Customizer\Style;

use Academy\Interfaces\DynamicStyleInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Archive extends Base implements DynamicStyleInterface {
	public static function get_css() {
		$css = '';
		$settings = self::get_settings();

		// Header Color Options
		$course_archive_header_bg_color = ( isset( $settings['course_archive_header_bg_color'] ) ? $settings['course_archive_header_bg_color'] : '' );
		$course_archive_header_pading = ( isset( $settings['course_archive_header_pading'] ) ? $settings['course_archive_header_pading'] : '' );
		$course_archive_header_margin = ( isset( $settings['course_archive_header_margin'] ) ? $settings['course_archive_header_margin'] : '' );
		$course_archive_header_course_count_color = ( isset( $settings['course_archive_header_course_count_color'] ) ? $settings['course_archive_header_course_count_color'] : '' );
		$course_archive_header_sorting_bg_color = ( isset( $settings['course_archive_header_sorting_bg_color'] ) ? $settings['course_archive_header_sorting_bg_color'] : '' );
		$course_archive_header_sorting_color = ( isset( $settings['course_archive_header_sorting_color'] ) ? $settings['course_archive_header_sorting_color'] : '' );

		if ( $course_archive_header_bg_color ) {
			$css .= ".academy-courses .academy-courses__header {
                background: $course_archive_header_bg_color;
            }";
		}

		if ( $course_archive_header_pading ) {
			$css .= self::generate_dimensions_css( '.academy-courses .academy-courses__header', $course_archive_header_pading );
		}

		if ( $course_archive_header_margin ) {
			$css .= self::generate_dimensions_css( '.academy-courses .academy-courses__header', $course_archive_header_margin, 'margin' );
		}

		if ( $course_archive_header_course_count_color ) {
			$css .= ".academy-courses .academy-courses__header .academy-courses__header-result-count {
                color: $course_archive_header_course_count_color;
            }";
		}

		if ( $course_archive_header_sorting_bg_color ) {
			$css .= ".academy-courses .academy-courses__header .academy-courses__header-ordering select {
                background: $course_archive_header_sorting_bg_color;
            }";
		}

		if ( $course_archive_header_sorting_color ) {
			$css .= ".academy-courses .academy-courses__header .academy-courses__header-ordering select {
                color: $course_archive_header_sorting_color;
            }";
		}

		// Course Card
		$course_archive_course_card_bg_color = ( isset( $settings['course_archive_course_card_bg_color'] ) ? $settings['course_archive_course_card_bg_color'] : '' );
		$course_archive_course_card_content_padding = ( isset( $settings['course_archive_course_card_content_padding'] ) ? $settings['course_archive_course_card_content_padding'] : '' );
		$course_archive_course_wishlist_bg_color = ( isset( $settings['course_archive_course_wishlist_bg_color'] ) ? $settings['course_archive_course_wishlist_bg_color'] : '' );
		$course_archive_course_wishlist_icon_color = ( isset( $settings['course_archive_course_wishlist_icon_color'] ) ? $settings['course_archive_course_wishlist_icon_color'] : '' );
		$course_archive_course_wishlist_icon_padding = ( isset( $settings['course_archive_course_wishlist_icon_padding'] ) ? $settings['course_archive_course_wishlist_icon_padding'] : '' );
		$course_archive_course_category_color = ( isset( $settings['course_archive_course_category_color'] ) ? $settings['course_archive_course_category_color'] : '' );
		$course_archive_course_title_color = ( isset( $settings['course_archive_course_title_color'] ) ? $settings['course_archive_course_title_color'] : '' );
		$course_archive_course_author_color = ( isset( $settings['course_archive_course_author_color'] ) ? $settings['course_archive_course_author_color'] : '' );
		$course_archive_course_footer_separator_color = ( isset( $settings['course_archive_course_footer_separator_color'] ) ? $settings['course_archive_course_footer_separator_color'] : '' );
		$course_archive_course_card_footer_padding = ( isset( $settings['course_archive_course_card_footer_padding'] ) ? $settings['course_archive_course_card_footer_padding'] : '' );
		$course_archive_course_rating_icon_color = ( isset( $settings['course_archive_course_rating_icon_color'] ) ? $settings['course_archive_course_rating_icon_color'] : '' );
		$course_archive_course_rating_color = ( isset( $settings['course_archive_course_rating_color'] ) ? $settings['course_archive_course_rating_color'] : '' );
		$course_archive_course_rating_count_color = ( isset( $settings['course_archive_course_rating_count_color'] ) ? $settings['course_archive_course_rating_count_color'] : '' );
		$course_archive_course_price_color = ( isset( $settings['course_archive_course_price_color'] ) ? $settings['course_archive_course_price_color'] : '' );
		$course_archive_normal_price_text_color = ( isset( $settings['course_archive_normal_price_text_color'] ) ? $settings['course_archive_normal_price_text_color'] : '' );
		$course_archive_sale_price_text_color = ( isset( $settings['course_archive_sale_price_text_color'] ) ? $settings['course_archive_sale_price_text_color'] : '' );

		if ( $course_archive_course_card_bg_color ) {
			$css .= ".academy-courses .academy-course {
                background: $course_archive_course_card_bg_color;
            }";
		}

		if ( $course_archive_course_card_content_padding ) {
			$css .= self::generate_dimensions_css( '.academy-courses .academy-course .academy-course__body', $course_archive_course_card_content_padding );
		}

		if ( $course_archive_course_wishlist_bg_color ) {
			$css .= ".academy-courses .academy-course .academy-course-header-meta .academy-add-to-wishlist-btn {
                background: $course_archive_course_wishlist_bg_color;
            }";
		}

		if ( $course_archive_course_wishlist_icon_color ) {
			$css .= ".academy-courses .academy-course .academy-course-header-meta .academy-add-to-wishlist-btn i {
                color: $course_archive_course_wishlist_icon_color;
            }";
		}

		if ( $course_archive_course_wishlist_icon_padding ) {
			$css .= self::generate_dimensions_css( '.academy-courses .academy-course .academy-course-header-meta .academy-add-to-wishlist-btn', $course_archive_course_wishlist_icon_padding );
		}

		if ( $course_archive_course_category_color ) {
			$css .= ".academy-courses .academy-course__meta--categroy a {
                color: $course_archive_course_category_color;
            }";
		}

		if ( $course_archive_course_title_color ) {
			$css .= ".academy-courses .academy-course__title a {
                color: $course_archive_course_title_color;
            }";
		}

		if ( $course_archive_course_author_color ) {
			$css .= ".academy-courses .academy-course__author .author, .academy-courses .academy-courses__body .academy-course__author .author a, .academy-courses .academy-course__author a {
                color: $course_archive_course_author_color;
            }";
		}

		if ( $course_archive_course_footer_separator_color ) {
			$css .= ".academy-courses .academy-course__footer {
                border-top-color: $course_archive_course_footer_separator_color;
            }";
		}

		if ( $course_archive_course_card_footer_padding ) {
			$css .= self::generate_dimensions_css( '.academy-courses .academy-course__footer', $course_archive_course_card_footer_padding );
		}

		if ( $course_archive_course_rating_icon_color ) {
			$css .= ".academy-courses .academy-course__footer .academy-course__rating .academy-group-star .academy-icon:before {
                color: $course_archive_course_rating_icon_color;
            }";
		}

		if ( $course_archive_course_rating_color ) {
			$css .= ".academy-courses .academy-course__footer .academy-course__rating {
                color: $course_archive_course_rating_color;
            }";
		}

		if ( $course_archive_course_rating_count_color ) {
			$css .= ".academy-courses .academy-course__footer .academy-course__rating .academy-course__rating-count {
                color: $course_archive_course_rating_count_color;
            }";
		}

		if ( $course_archive_course_price_color ) {
			$css .= ".academy-courses .academy-course__footer .academy-course__price {
                color: $course_archive_course_price_color;
            }";
		}

		if ( $course_archive_normal_price_text_color ) {
			$css .= ".academy-courses .academy-course__footer .academy-course__price del .amount {
                color: $course_archive_normal_price_text_color;
            }";
		}

		if ( $course_archive_sale_price_text_color ) {
			$css .= ".academy-courses .academy-course__footer .academy-course__price ins .amount {
                color: $course_archive_sale_price_text_color;
            }";
		}

		// Pagination Styles
		$course_archive_course_pagination_padding = ( isset( $settings['course_archive_course_pagination_padding'] ) ? $settings['course_archive_course_pagination_padding'] : '' );
		$course_archive_course_pagination_margin = ( isset( $settings['course_archive_course_pagination_margin'] ) ? $settings['course_archive_course_pagination_margin'] : '' );
		$course_archive_pagination_active_button_bg_color = ( isset( $settings['course_archive_pagination_active_button_bg_color'] ) ? $settings['course_archive_pagination_active_button_bg_color'] : '' );
		$course_archive_pagination_active_button_color = ( isset( $settings['course_archive_pagination_active_button_color'] ) ? $settings['course_archive_pagination_active_button_color'] : '' );
		$course_archive_pagination_normal_button_bg_color = ( isset( $settings['course_archive_pagination_normal_button_bg_color'] ) ? $settings['course_archive_pagination_normal_button_bg_color'] : '' );
		$course_archive_pagination_normal_button_color = ( isset( $settings['course_archive_pagination_normal_button_color'] ) ? $settings['course_archive_pagination_normal_button_color'] : '' );
		$course_archive_next_prev_pagination_button_bg_color = ( isset( $settings['course_archive_next_prev_pagination_button_bg_color'] ) ? $settings['course_archive_next_prev_pagination_button_bg_color'] : '' );
		$course_archive_next_prev_pagination_button_text_color = ( isset( $settings['course_archive_next_prev_pagination_button_text_color'] ) ? $settings['course_archive_next_prev_pagination_button_text_color'] : '' );

		if ( $course_archive_course_pagination_padding ) {
			$css .= self::generate_dimensions_css( '.academy-courses .academy-courses__pagination .page-numbers', $course_archive_course_pagination_padding );
		}

		if ( $course_archive_course_pagination_margin ) {
			$css .= self::generate_dimensions_css( '.academy-courses .academy-courses__pagination .page-numbers', $course_archive_course_pagination_margin, 'margin' );
		}

		if ( $course_archive_pagination_active_button_bg_color ) {
			$css .= ".academy-courses .academy-courses__pagination .page-numbers.current, .academy-courses .academy-courses__pagination .page-numbers:hover {
                background: $course_archive_pagination_active_button_bg_color;
            }";
		}

		if ( $course_archive_pagination_active_button_color ) {
			$css .= ".academy-courses .academy-courses__pagination .page-numbers.current, .academy-courses .academy-courses__pagination .page-numbers:hover {
                color: $course_archive_pagination_active_button_color;
            }";
		}

		if ( $course_archive_pagination_normal_button_bg_color ) {
			$css .= ".academy-courses .academy-courses__pagination .page-numbers {
                background: $course_archive_pagination_normal_button_bg_color;
            }";
		}

		if ( $course_archive_pagination_normal_button_color ) {
			$css .= ".academy-courses .academy-courses__pagination .page-numbers {
                color: $course_archive_pagination_normal_button_color;
            }";
		}

		if ( $course_archive_next_prev_pagination_button_bg_color ) {
			$css .= ".academy-courses .academy-courses__pagination .next.page-numbers, .academy-courses .academy-courses__pagination .prev.page-numbers {
                background: $course_archive_next_prev_pagination_button_bg_color;
            }";
		}

		if ( $course_archive_next_prev_pagination_button_text_color ) {
			$css .= ".academy-courses .academy-courses__pagination .next.page-numbers i, .academy-courses .academy-courses__pagination .prev.page-numbers i {
                color: $course_archive_next_prev_pagination_button_text_color;
            }";
		}

		// Sidebar Filter Styles
		$course_archive_sidebar_bg_color = ( isset( $settings['course_archive_sidebar_bg_color'] ) ? $settings['course_archive_sidebar_bg_color'] : '' );
		$course_archive_course_sidebar_padding = ( isset( $settings['course_archive_course_sidebar_padding'] ) ? $settings['course_archive_course_sidebar_padding'] : '' );
		$course_archive_course_sidebar_filter_margin = ( isset( $settings['course_archive_course_sidebar_filter_margin'] ) ? $settings['course_archive_course_sidebar_filter_margin'] : '' );
		$course_archive_sidebar_searchbox_bg_color = ( isset( $settings['course_archive_sidebar_searchbox_bg_color'] ) ? $settings['course_archive_sidebar_searchbox_bg_color'] : '' );
		$course_archive_sidebar_searchbox_placeholder_text_color = ( isset( $settings['course_archive_sidebar_searchbox_placeholder_text_color'] ) ? $settings['course_archive_sidebar_searchbox_placeholder_text_color'] : '' );
		$course_archive_sidebar_searchbox_text_color = ( isset( $settings['course_archive_sidebar_searchbox_text_color'] ) ? $settings['course_archive_sidebar_searchbox_text_color'] : '' );
		$course_archive_sidebar_filter_heading_color = ( isset( $settings['course_archive_sidebar_filter_heading_color'] ) ? $settings['course_archive_sidebar_filter_heading_color'] : '' );
		$course_archive_sidebar_filter_checkbox_bg_color = ( isset( $settings['course_archive_sidebar_filter_checkbox_bg_color'] ) ? $settings['course_archive_sidebar_filter_checkbox_bg_color'] : '' );
		$course_archive_sidebar_filter_checkbox_border_color = ( isset( $settings['course_archive_sidebar_filter_checkbox_border_color'] ) ? $settings['course_archive_sidebar_filter_checkbox_border_color'] : '' );
		$course_archive_sidebar_filter_item_color = ( isset( $settings['course_archive_sidebar_filter_item_color'] ) ? $settings['course_archive_sidebar_filter_item_color'] : '' );

		if ( $course_archive_sidebar_bg_color ) {
			$css .= ".academy-courses .academy-courses__sidebar {
                background: $course_archive_sidebar_bg_color;
            }";
		}

		if ( $course_archive_course_sidebar_padding ) {
			$css .= self::generate_dimensions_css( '.academy-courses .academy-courses__sidebar', $course_archive_course_sidebar_padding );
		}

		if ( $course_archive_course_sidebar_filter_margin ) {
			$css .= self::generate_dimensions_css( '.academy-courses .academy-courses__sidebar .academy-archive-course-widget', $course_archive_course_sidebar_filter_margin, 'margin' );
		}

		if ( $course_archive_sidebar_searchbox_bg_color ) {
			$css .= ".academy-courses .academy-courses__sidebar .academy-archive-course-widget--search input.academy-archive-course-search {
                background: $course_archive_sidebar_searchbox_bg_color;
            }";
		}

		if ( $course_archive_sidebar_searchbox_placeholder_text_color ) {
			$css .= ".academy-courses .academy-courses__sidebar .academy-archive-course-widget--search input.academy-archive-course-search::placeholder {
                color: $course_archive_sidebar_searchbox_placeholder_text_color;
            }";
		}

		if ( $course_archive_sidebar_searchbox_text_color ) {
			$css .= ".academy-courses .academy-courses__sidebar .academy-archive-course-widget--search input.academy-archive-course-search {
                color: $course_archive_sidebar_searchbox_text_color;
            }";
		}

		if ( $course_archive_sidebar_filter_heading_color ) {
			$css .= ".academy-courses .academy-courses__sidebar .academy-archive-course-widget .academy-archive-course-widget__title {
                color: $course_archive_sidebar_filter_heading_color;
            }";
		}

		if ( $course_archive_sidebar_filter_checkbox_bg_color ) {
			$css .= ".academy-courses .academy-courses__sidebar .academy-archive-course-widget__body label .checkmark {
                background: $course_archive_sidebar_filter_checkbox_bg_color;
            }";
		}

		if ( $course_archive_sidebar_filter_checkbox_border_color ) {
			$css .= ".academy-courses .academy-courses__sidebar .academy-archive-course-widget__body label .checkmark {
                border-color: $course_archive_sidebar_filter_checkbox_border_color;
            }";
		}

		if ( $course_archive_sidebar_filter_item_color ) {
			$css .= ".academy-courses .academy-courses__sidebar .academy-archive-course-widget .academy-archive-course-widget__body label {
                color: $course_archive_sidebar_filter_item_color;
            }";
		}

		return $css;
	}
}
