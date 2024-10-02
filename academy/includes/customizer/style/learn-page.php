<?php
namespace Academy\Customizer\Style;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Interfaces\DynamicStyleInterface;

class LearnPage extends Base implements DynamicStyleInterface {
	public static function get_css() {
		$css = '';
		$settings = self::get_settings();

		// General Style
		$learn_page_background = $settings['learn_page_background'] ?? '';
		$learn_page_heading_color = $settings['learn_page_heading_color'] ?? '';
		$learn_page_paragraph_color = $settings['learn_page_paragraph_color'] ?? '';
		$learn_page_link_color = $settings['learn_page_link_color'] ?? '';

		if ( $learn_page_background ) {
			$css .= ".academy-lessons {
                background: $learn_page_background;
            }";
		}
		if ( $learn_page_heading_color ) {
			$css .= ".academy-lessons h1, .academy-lessons h2, .academy-lessons h3, .academy-lessons h4, .academy-lessons h5, .academy-lessons h6 {
                color: $learn_page_heading_color;
            }";
		}
		if ( $learn_page_paragraph_color ) {
			$css .= ".academy-lessons p, .academy-lessons span, .academy-lessons strong, .academy-lessons li {
                color: $learn_page_paragraph_color;
            }";
		}

		if ( $learn_page_link_color ) {
			$css .= ".academy-lessons a {
                color: $learn_page_link_color;
            }";
		}

		// Top bar Style
		$learn_page_top_bar_background = $settings['learn_page_top_bar_background'] ?? '';
		$learn_page_top_bar_color = $settings['learn_page_top_bar_color'] ?? '';
		$learn_page_top_bar_link_color = $settings['learn_page_top_bar_link_color'] ?? '';
		$learn_page_share_button_bg_color = $settings['learn_page_share_button_background_color'] ?? '';
		$learn_page_share_button_text_color = $settings['learn_page_share_button_text_color'] ?? '';

		if ( $learn_page_top_bar_background ) {
			$css .= ".academy-lessons .academy-lesson-topbar {
                background: $learn_page_top_bar_background;
            }";
		}
		if ( $learn_page_top_bar_color ) {
			$css .= ".academy-lessons .academy-lesson-topbar p {
                color: $learn_page_top_bar_color;
            }";
		}

		if ( $learn_page_top_bar_link_color ) {
			$css .= ".academy-lessons .academy-lesson-topbar__left .academy-course-title a {
                color: $learn_page_top_bar_link_color;
            }";
		}

		if ( $learn_page_share_button_bg_color ) {
			$css .= ".academy-lessons button.academy-btn--share {
				background: $learn_page_share_button_bg_color;
			}";
		}

		if ( $learn_page_share_button_text_color ) {
			$css .= ".academy-lessons button.academy-btn--share span.academy-btn--label {
                color: $learn_page_share_button_text_color;
            }";
		}

		// Sidebar Style
		$learn_page_sidebar_background = $settings['learn_page_sidebar_background'] ?? '';
		$learn_page_sidebar_border_color = $settings['learn_page_sidebar_border_color'] ?? '';
		$learn_page_sidebar_heading_color = $settings['learn_page_sidebar_heading_color'] ?? '';

		if ( $learn_page_sidebar_background ) {
			$css .= ".academy-lessons .academy-lesson-content-wrapper .academy-lesson-sidebar-content  {
                background: $learn_page_sidebar_background;
            }";
		}
		if ( $learn_page_sidebar_border_color ) {
			$css .= ".academy-lessons .academy-lesson-content-wrapper .academy-lesson-sidebar-content, .academy-lesson-content-wrapper .academy-lesson-sidebar-content__title {
                border-color: $learn_page_sidebar_border_color;
            }";
		}

		if ( $learn_page_sidebar_heading_color ) {
			$css .= ".academy-lessons .academy-lesson-content-wrapper .academy-lesson-sidebar-content__title h4 {
                color: $learn_page_sidebar_heading_color;
            }";
		}

		// Topics Style
		$learn_page_topics_heading_background = $settings['learn_page_topics_heading_background'] ?? '';
		$learn_page_topics_heading_border_color = $settings['learn_page_topics_heading_border_color'] ?? '';
		$learn_page_topics_heading_color = $settings['learn_page_topics_heading_color'] ?? '';
		if ( $learn_page_topics_heading_background ) {
			$css .= ".academy-lessons .academy-lesson-content-wrapper .academy-lesson-sidebar-content .academy-topics .academy-topics-title  {
                background: $learn_page_topics_heading_background;
            }";
		}
		if ( $learn_page_topics_heading_border_color ) {
			$css .= ".academy-lessons .academy-lesson-content-wrapper .academy-lesson-sidebar-content .academy-topics .academy-topics-title {
                border-color: $learn_page_topics_heading_border_color;
            }";
		}
		if ( $learn_page_topics_heading_color ) {
			$css .= ".academy-lessons .academy-lesson-content-wrapper .academy-lesson-sidebar-content .academy-topics .academy-topics-title__text, .academy-lessons .academy-lesson-content-wrapper .academy-lesson-sidebar-content .academy-topics .academy-topics-title .academy-icon:before {
                color: $learn_page_topics_heading_color;
            }";
		}

		// Topic Style
		$learn_page_topic_item_background = $settings['learn_page_topic_item_background'] ?? '';
		$learn_page_topic_item_border_color = $settings['learn_page_topic_item_border_color'] ?? '';
		$learn_page_topic_item_color = $settings['learn_page_topic_item_color'] ?? '';
		$learn_page_topic_item_icon_color = $settings['learn_page_topic_item_icon_color'] ?? '';

		if ( $learn_page_topic_item_background ) {
			$css .= ".academy-lessons .academy-lesson-content-wrapper .academy-lesson-sidebar-content .academy-topics .academy-topics-lesson-items .academy-topics-lesson-item  {
                background: $learn_page_topic_item_background;
            }";
		}
		if ( $learn_page_topic_item_border_color ) {
			$css .= ".academy-lessons .academy-lesson-content-wrapper .academy-lesson-sidebar-content .academy-topics .academy-topics-lesson-items .academy-topics-lesson-item  {
                border-top-color: $learn_page_topic_item_border_color;
            }";
		}
		if ( $learn_page_topic_item_color ) {
			$css .= ".academy-lessons .academy-lesson-content-wrapper .academy-lesson-sidebar-content .academy-topics .academy-topics-lesson-items .academy-topics-lesson-item span.academy-topics-lesson-item__text {
                color: $learn_page_topic_item_color;
            }";
		}
		if ( $learn_page_topic_item_icon_color ) {
			$css .= ".academy-lessons .academy-lesson-content-wrapper .academy-lesson-sidebar-content .academy-topics .academy-topics-lesson-items .academy-topics-lesson-item__btn .academy-entry-left .academy-icon:before {
                color: $learn_page_topic_item_icon_color;
            }";
		}

		// Topic Active Style
		$learn_page_topic_active_background = $settings['learn_page_topic_active_background'] ?? '';
		$learn_page_topic_active_border_color = $settings['learn_page_topic_active_border_color'] ?? '';
		$learn_page_topic_active_color = $settings['learn_page_topic_active_color'] ?? '';
		$learn_page_topic_active_icon_color = $settings['learn_page_topic_active_icon_color'] ?? '';
		if ( $learn_page_topic_active_background ) {
			$css .= ".academy-lessons .academy-lesson-content-wrapper .academy-lesson-sidebar-content .academy-topics .academy-topics-lesson-items .academy-topics-lesson-item--playing  {
                background: $learn_page_topic_active_background;
            }";
		}
		if ( $learn_page_topic_active_border_color ) {
			$css .= ".academy-lessons .academy-lesson-content-wrapper .academy-lesson-sidebar-content .academy-topics .academy-topics-lesson-items .academy-topics-lesson-item--playing  {
                border-top-color: $learn_page_topic_active_border_color;
            }";
		}
		if ( $learn_page_topic_active_color ) {
			$css .= ".academy-lessons .academy-lesson-content-wrapper .academy-lesson-sidebar-content .academy-topics .academy-topics-lesson-items .academy-topics-lesson-item--playing span.academy-topics-lesson-item__text {
                color: $learn_page_topic_active_color;
            }";
		}
		if ( $learn_page_topic_active_icon_color ) {
			$css .= ".academy-lessons .academy-lesson-content-wrapper .academy-lesson-sidebar-content .academy-topics .academy-topics-lesson-items .academy-topics-lesson-item--playing .academy-topics-lesson-item__btn .academy-entry-left .academy-icon:before {
                color: $learn_page_topic_active_icon_color;
            }";
		}

		// QA Form
		$learn_page_qa_form_background = $settings['learn_page_qa_form_background'] ?? '';
		$learn_page_qa_form_border_color = $settings['learn_page_qa_form_border_color'] ?? '';
		$learn_page_qa_form_color = $settings['learn_page_qa_form_color'] ?? '';
		$learn_page_qa_form_field_background = $settings['learn_page_qa_form_field_background'] ?? '';
		$learn_page_qa_form_field_border_color = $settings['learn_page_qa_form_field_border_color'] ?? '';
		if ( $learn_page_qa_form_background ) {
			$css .= ".academy-lessons .academy-lesson-tab__body .academy-lesson-browseqa-wrap  {
				background: $learn_page_qa_form_background;
			}";
		}
		if ( $learn_page_qa_form_border_color ) {
			$css .= ".academy-lessons .academy-lesson-tab__body .academy-lesson-browseqa-wrap  {
                border-color: $learn_page_qa_form_border_color;
            }";
		}
		if ( $learn_page_qa_form_color ) {
			$css .= ".academy-lessons .academy-lesson-browseqa-wrap .academy-question-form__heading, 
			.academy-lessons .academy-lesson-browseqa-wrap .academy-question-lists .academy-qa__meta .academy-qa-user-info .academy-qa-username,
			.academy-lessons .academy-lesson-browseqa-wrap .academy-question-lists .academy-qa__meta .academy-qa-user-info .academy-qa-time,
			.academy-lessons .academy-lesson-browseqa-wrap .academy-question-lists .academy-question-not-found h3,
			.academy-lessons .academy-lesson-browseqa-wrap .academy-question-lists .academy-question-not-found p,
			.academy-lessons .academy-lesson-browseqa-wrap .academy-question-lists .academy-qa__body .academy-qa-title,
			.academy-lessons .academy-lesson-browseqa-wrap .academy-question-lists .academy-qa__body p {
				color: $learn_page_qa_form_color;
			}";
		}

		if ( $learn_page_qa_form_field_background ) {
			$css .= ".academy-lessons .academy-lesson-browseqa-wrap .academy-question-form form input, 
			.academy-lessons .academy-lesson-browseqa-wrap .academy-question-form form textarea  {
				background: $learn_page_qa_form_field_background;
			}";
		}
		if ( $learn_page_qa_form_field_border_color ) {
			$css .= ".academy-lessons .academy-lesson-browseqa-wrap .academy-question-form form input, 
			.academy-lessons .academy-lesson-browseqa-wrap .academy-question-form form textarea  {
				border-color: $learn_page_qa_form_field_border_color;
			}";
		}

		// Announcement
		$learn_page_announcement_item_background = $settings['learn_page_announcement_item_background'] ?? '';
		$learn_page_announcement_item_border_color = $settings['learn_page_announcement_item_border_color'] ?? '';
		$learn_page_announcement_item_heading_color = $settings['learn_page_announcement_item_heading_color'] ?? '';
		$learn_page_announcement_item_color = $settings['learn_page_announcement_item_color'] ?? '';
		if ( $learn_page_announcement_item_background ) {
			$css .= ".academy-lessons .academy-announcements-wrap .academy-announcement-item  {
				background: $learn_page_announcement_item_background;
			}";
		}
		if ( $learn_page_announcement_item_border_color ) {
			$css .= ".academy-lessons .academy-announcements-wrap .academy-announcement-item  {
				border-color: $learn_page_announcement_item_border_color;
			}";
		}
		if ( $learn_page_announcement_item_heading_color ) {
			$css .= ".academy-lessons .academy-announcements-wrap .academy-announcement-item h3  {
				color: $learn_page_announcement_item_heading_color;
			}";
		}
		if ( $learn_page_announcement_item_color ) {
			$css .= ".academy-lessons .academy-announcements-wrap .academy-announcement-item p  {
				color: $learn_page_announcement_item_color;
			}";
		}

		return $css;
	}
}
