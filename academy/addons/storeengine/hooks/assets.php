<?php

namespace AcademyStoreEngine\hooks;

class Assets {

	public static function init() {
		$self = new self();

		add_filter( 'academy/assets/backend_scripts_data', [ $self, 'add_active_addons' ] );
		add_filter( 'academy/assets/frontend_scripts_data', [ $self, 'add_active_addons' ] );
	}

	public function add_active_addons( array $data ): array {
		global $storeengine_addons;

		return array_merge( $data, [
			'storeengine_is_active' => class_exists( 'StoreEngine' ),
			'storeengine_addons'    => $storeengine_addons ?? null,
			'storeengine_nonce'     => wp_create_nonce( 'storeengine_nonce' ),
		] );
	}
}
