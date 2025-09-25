<?php
namespace AcademyChatgpt\CourseImport\Importers;

use Academy\Helper;
use Exception;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class Assignment implements Interfaces\Insertable {
	public int $id;
	protected string $title;
	protected string $content;
	protected string $status = 'publish';
	protected array $data;
	protected array $meta = [
		'minimum_passing_points' => 4,
		'submission_time'        => 10,
		'submission_time_unit'   => 'hours',
		'total_points'           => 10
	];
	protected int $thumbnail_id;
	protected bool $is_edit;

	public function __construct( array $data, int $assignment_id ) {
		$this->is_edit = $assignment_id > 0;
		if ( $this->is_edit && ! get_post( $assignment_id ) ) {
			throw new Exception( __( 'Course not found.', 'academy' ) );
		}

		$this->id = $assignment_id;
		$this->data = $data;
		$this->title = $data['title'] ?? '';
		$this->content = $data['description'] ?? '';
		$this->meta['academy_assignment_resubmit_limit']  = $data['academy_assignment_resubmit_limit'] ?? 0;
		$this->meta['academy_assignment_enable_resubmit'] = $data['academy_assignment_enable_resubmit'] ?? 0;
		$this->meta['academy_assignment_attachment']      = 0;

		if ( ! empty( $data['academy_assignment_settings'] ?? '' ) ) {
			$this->meta['academy_assignment_settings'] = $data['academy_assignment_settings'];
		}
		else {
			$this->meta['academy_assignment_settings'] = [
				'submission_time'        => absint( $data['meta']['submission_time'] ?? 0 ),
				'submission_time_unit'   => sanitize_text_field( $data['meta']['submission_time_unit'] ?? 'hours' ),
				'minimum_passing_points' => absint( $data['meta']['minimum_passing_points'] ?? 0 ),
				'total_points'           => absint( $data['meta']['total_points'] ?? 0 ),
			];
		}

	}

	public function insert() : int {
		$id = wp_insert_post( array_merge( $this->is_edit ? [ 'ID' => $this->id ] : [], [
			'post_title' => $this->title,
			'post_type'  => 'academy_assignments',
			'post_content' => $this->content,
			'post_status' => $this->status
		] ) );

		if ( is_wp_error( $id ) ) {
			throw new Exception( __( 'Error.', 'academy' ) );
		}
		$this->id = $id;

		$this->insert_meta();
		return $this->id;
	}

	protected function insert_meta() : void {
		foreach ( $this->meta as $key => $value ) {
			update_post_meta( $this->id, $key, $value );
		}
	}
}
