<?php
namespace Academy\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Helper;
use stdClass;

class GroupPlusHelper {

	protected string $group_table;
	protected string $gcr_table;
	protected string $tcr_table;
	protected string $tmr_table;

	public function __construct() {
		$this->gcr_table   = $this->merge_prefix_at_table( 'group_courses' );
		$this->tmr_table   = $this->merge_prefix_at_table( 'team_members' );
		$this->tcr_table   = $this->merge_prefix_at_table( 'team_courses' );
		$this->group_table = $this->merge_prefix_at_table( 'groups' );
	}

	public function is_team_member_enrolled( int $course_id, int $member_id ) : ?stdClass {
		global $wpdb;

		if ( $this->check_table_exists( $this->tcr_table ) ) {
			return null;
		}

		$query = "SELECT g.id as ID, 
                         g.name as post_title, 
                         tmr.member_id as post_author, 
                         'completed' as enrolled_status, 
                         g.updated_at as post_date,
                         g.updated_at as post_date_gmt
					FROM {$this->tmr_table}  tmr
                    INNER JOIN {$this->tcr_table}  tcr 
                        ON tmr.team_id = tcr.team_id
                    INNER JOIN {$this->group_table}  g
                        ON g.id = tcr.group_id
					        WHERE tcr.course_id = %d AND  tmr.member_id = %d";

		$data = $wpdb->get_row( $q = $wpdb->prepare( $query, $course_id, $member_id ) ) ?? false;

		if ( $data ) {
			return $data;
		}

		return null;
	}

	public function total_seat_count_by_course( int $course_id ) : int {
		global $wpdb;

		if ( $this->check_table_exists( $this->gcr_table ) ) {
			return 0;
		}

		$query = "SELECT SUM(total_seats) 
					FROM {$this->gcr_table}
					where course_id = %d";

		return intval( $wpdb->get_var( $wpdb->prepare( $query, $course_id ) ) ?? 0 );
	}

	protected function check_table_exists( string $table ) : bool {
		global $wpdb;
		$query = 'SHOW TABLES LIKE %s';
		return boolval(
			$wpdb->get_var( $wpdb->prepare( $query, $this->merge_prefix_at_table( $table ) ) ) ?? false
		);
	}

	protected function merge_prefix_at_table( string $table ) : string {
		return $GLOBALS['wpdb']->prefix . ACADEMY_PLUGIN_SLUG . '_' . $table;
	}

	public static function ins(): self {
		// singleton is needed to enhance performance, because this method is need to be call several times
		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new self();
		}
		return $instance;
	}
}
