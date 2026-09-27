<?php
/**
 * Base class for dummy-data seeder providers.
 *
 * A provider knows how to create (and let the framework clean up) sample data
 * for ONE feature area. The seeder addon registers the core providers; other
 * addons can contribute their own by hooking `academy/seeder/providers`.
 *
 * @package AcademySeeder\Classes
 */

namespace AcademySeeder\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class AbstractSeederProvider {

	/**
	 * Unique, stable key for this provider (e.g. "categories", "course").
	 *
	 * Used as the CLI selector (`--only=course`) and the bucket downstream
	 * providers read their ids from.
	 *
	 * @return string
	 */
	abstract public function get_key();

	/**
	 * Human-readable label shown in the UI and CLI output.
	 *
	 * @return string
	 */
	abstract public function get_label();

	/**
	 * Short description shown on the Dummy Data tab.
	 *
	 * @return string
	 */
	public function get_description() {
		return '';
	}

	/**
	 * Keys of other providers that MUST run before this one.
	 *
	 * @return string[]
	 */
	public function get_dependencies() {
		return [];
	}

	/**
	 * How many records to create when no explicit count is supplied.
	 *
	 * @return int
	 */
	public function get_default_count() {
		return 1;
	}

	/**
	 * Whether this data set is ticked by default (and included in a bare run).
	 *
	 * @return bool
	 */
	public function is_default_selected() {
		return true;
	}

	/**
	 * Create the dummy records.
	 *
	 * Implementations MUST:
	 *  - record every persisted object via {@see SeederContext::record()} so it
	 *    can be removed by a reset;
	 *  - read upstream ids via {@see SeederContext::ids()} rather than querying;
	 *  - never fatal on a single bad record — wrap risky work in try/catch and
	 *    keep going (the manager surfaces failures to the caller).
	 *
	 * @param SeederContext $context Shared run context.
	 * @param int           $count   Number of records to create.
	 *
	 * @return void
	 */
	abstract public function seed( SeederContext $context, $count );
}
