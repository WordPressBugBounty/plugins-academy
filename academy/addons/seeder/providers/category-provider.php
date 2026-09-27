<?php
/**
 * Seeds course categories + tags used by the sample course.
 *
 * @package AcademySeeder\Providers
 */

namespace AcademySeeder\Providers;

use AcademySeeder\Classes\AbstractSeederProvider;
use AcademySeeder\Classes\Manager;
use AcademySeeder\Classes\SeederContext;
use AcademySeeder\Classes\SeederData;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CategoryProvider extends AbstractSeederProvider {

	const CATEGORY_TAXONOMY = 'academy_courses_category';
	const TAG_TAXONOMY      = 'academy_courses_tag';

	public function get_key() {
		return 'categories';
	}

	public function get_label() {
		return __( 'Categories & Tags', 'academy' );
	}

	public function get_description() {
		return __( 'Course categories and tags for the sample course to live under.', 'academy' );
	}

	public function seed( SeederContext $context, $count ) {
		foreach ( SeederData::categories() as $name ) {
			$this->create_term( $context, $name, self::CATEGORY_TAXONOMY, 'course_category' );
		}

		foreach ( SeederData::tags() as $name ) {
			$this->create_term( $context, $name, self::TAG_TAXONOMY, 'course_tag' );
		}
	}

	/**
	 * Create (or reuse) a term and record only the ones we created, so a reset
	 * never removes a term the user already had.
	 *
	 * @param SeederContext $context      Run context.
	 * @param string        $name         Term name.
	 * @param string        $taxonomy     Taxonomy slug.
	 * @param string        $record_type  Manifest type used for cleanup.
	 *
	 * @return void
	 */
	private function create_term( SeederContext $context, $name, $taxonomy, $record_type ) {
		if ( term_exists( $name, $taxonomy ) ) {
			return;
		}

		$term = wp_insert_term( $name, $taxonomy );
		if ( is_wp_error( $term ) || empty( $term['term_id'] ) ) {
			return;
		}

		$term_id = (int) $term['term_id'];
		update_term_meta( $term_id, Manager::MARKER_META, 1 );
		$context->record( $record_type, $term_id );
	}
}
