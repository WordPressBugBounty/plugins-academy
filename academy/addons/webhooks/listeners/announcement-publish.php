<?php
namespace AcademyWebhooks\Listeners;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AcademyWebhooks\Classes\Payload;
use AcademyWebhooks\Interfaces\ListenersInterface;

class AnnouncementPublish implements ListenersInterface {
	public static function dispatch( $deliver_callback, $webhook ) {
		add_action(
			'rest_after_insert_academy_announcement',
			function( $announcement ) use ( $deliver_callback, $webhook ) {
				call_user_func_array(
					$deliver_callback,
					array(
						$webhook,
						self::get_payload( $announcement )
					)
				);
			}, 10
		);

	}

	public static function get_payload( $announcement ) {
		$data = [];
		$course = [];
		$meta_values = get_post_meta( $announcement->ID, 'academy_announcements_course_ids', true );
		if ( count( $meta_values ) || $announcement ) {
			foreach ( $meta_values as $meta_value ) {
				$course = Payload::get_course_data( $meta_value['value'] );
			}
			$data = array(
				'ID'                 => $announcement->ID,
				'title'              => wp_specialchars_decode( $announcement->post_title ),
				'permalink'          => get_permalink( $announcement->ID ),
				'status'             => (string) $announcement->post_status,
				'short_description'  => $announcement->post_excerpt,
				'slug'               => $announcement->post_name,
				'description'        => $announcement->post_content,
				'preview_link'       => get_preview_post_link( $announcement ),
				'parent_id'          => (int) $announcement->post_parent,
				'menu_order'         => (int) $announcement->menu_order,
				'author'             => (int) $announcement->post_author,
				'author_display_name' => get_the_author_meta( 'display_name', $announcement->post_author ),
				'author_avatar_url'  => get_avatar_url( $announcement->post_author ),
				'date_created'       => $announcement->post_date,
				'date_modified'      => $announcement->post_modified,
				'edit_post_link'     => get_edit_post_link( $announcement ),
				'_course'            => $course,
			);
		}//end if

		return apply_filters( 'academy_webhooks/announcement_published_payload', $data );
	}
}
