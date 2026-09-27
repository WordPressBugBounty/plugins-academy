<?php
/**
 * AJAX endpoints for the Dummy Data tab under Academy → Tools.
 *
 * Thin wrappers over {@see Manager} — the same engine the CLI uses — exposed to
 * the React admin app. Registered only while the seeder addon is active.
 *
 * @package AcademySeeder\Classes
 */

namespace AcademySeeder\Classes;

use Academy\Classes\AbstractAjaxHandler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ajax extends AbstractAjaxHandler {

	public function __construct() {
		$this->actions = [
			'seeder_data'  => [
				'capability' => 'manage_options',
				'callback'   => [ $this, 'get_data' ],
			],
			'seeder_run'   => [
				'capability' => 'manage_options',
				'callback'   => [ $this, 'run' ],
			],
			'seeder_reset' => [
				'capability' => 'manage_options',
				'callback'   => [ $this, 'reset' ],
			],
		];
	}

	/**
	 * Provider list + current manifest size + environment, for the tab to render.
	 *
	 * @return void
	 */
	public function get_data() {
		wp_send_json_success( $this->snapshot() );
	}

	/**
	 * @param array $payload Request data: only[], counts (a JSON string) and force (bool).
	 *
	 * @return void
	 */
	public function run( $payload ) {
		$force = ! empty( $payload['force'] ) && 'false' !== $payload['force'];
		if ( 'production' === wp_get_environment_type() && ! $force ) {
			wp_send_json_error( __( 'Seeding is disabled on production. Tick the override to proceed.', 'academy' ) );
		}

		$only = isset( $payload['only'] ) && is_array( $payload['only'] )
			? array_map( 'sanitize_key', $payload['only'] )
			: [];

		$counts = [];
		if ( ! empty( $payload['counts'] ) ) {
			$decoded = json_decode( (string) $payload['counts'], true );
			if ( is_array( $decoded ) ) {
				foreach ( $decoded as $key => $value ) {
					if ( is_numeric( $value ) ) {
						$counts[ sanitize_key( $key ) ] = max( 0, (int) $value );
					}
				}
			}
		}

		$context = Manager::get_instance()->run( [
			'only'   => $only,
			'counts' => $counts,
		] );

		$summary = [];
		foreach ( $context->get_records() as $record ) {
			$summary[ $record['type'] ] = ( $summary[ $record['type'] ] ?? 0 ) + 1;
		}

		wp_send_json_success( array_merge( $this->snapshot(), [ 'summary' => $summary ] ) );
	}

	/**
	 * @return void
	 */
	public function reset() {
		$deleted = Manager::get_instance()->reset();

		wp_send_json_success( array_merge( $this->snapshot(), [ 'removed' => array_sum( $deleted ) ] ) );
	}

	/**
	 * Common payload: registered providers, manifest size, environment flag.
	 *
	 * @return array
	 */
	private function snapshot() {
		$manager = Manager::get_instance();

		$providers = array_map( static function ( $provider ) {
			return [
				'key'              => $provider->get_key(),
				'label'            => $provider->get_label(),
				'description'      => $provider->get_description(),
				'default_count'    => $provider->get_default_count(),
				'dependencies'     => $provider->get_dependencies(),
				'default_selected' => $provider->is_default_selected(),
			];
		}, $manager->resolve_order() );

		return [
			'providers'      => array_values( $providers ),
			'manifest_count' => count( $manager->get_manifest() ),
			'is_production'  => 'production' === wp_get_environment_type(),
		];
	}
}
