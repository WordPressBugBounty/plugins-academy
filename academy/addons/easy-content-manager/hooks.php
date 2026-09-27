<?php
namespace AcademyEasyContentManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Hooks {

	public static function init() {
		$self = new self();
		// third party plugin scripts allow
		add_filter( 'academy/allowed_third_party_plugins_assets', array( $self, 'allowed_third_party_assets' ) );
		// let ECM's react-editor bundle (custom taxonomy/field group UI) load on Academy's own admin pages, not just post.php
		add_action( 'admin_enqueue_scripts', array( $self, 'allow_react_editor_on_academy_pages' ), 5 );
	}

	public function allowed_third_party_assets( $allowed ) {
		$allowed[] = 'easy-content-manager';
		return $allowed;
	}

	/**
	 * ECM's react-editor bundle (the script that renders custom
	 * taxonomies/field groups inside a non-native post editor) only
	 * enqueues itself — see EasyContentManager\Assets::react_editor_script()
	 * — on a fixed set of core hook suffixes (post.php, post-new.php, ...)
	 * or ECM's own admin pages, because it assumes it's mounting into
	 * WordPress's own edit-post screen. Academy's course/lesson/announcement
	 * builders never use that screen — they're a standalone React app under
	 * admin.php?page=academy* — so without this, ECM's own gate never
	 * matches here and its script never loads, leaving Academy's own
	 * applyFilters() calls (`ecm.react-editor.register-taxonomy` /
	 * `-custom-field`) resolve to nothing.
	 *
	 * Rather than duplicating ECM's own enqueue/localize logic here (fragile
	 * — it has to track ECM's internal asset paths and payload building, and
	 * drifts whenever ECM changes them), this uses the extension point ECM
	 * itself already exposes for exactly this purpose: the
	 * `easy_content_manager/react_editor_allowed_hooks` filter consulted
	 * inside `react_editor_script()`. Hooked on `admin_enqueue_scripts` at
	 * priority 5 — before ECM's own `react_editor_script()` (default
	 * priority 10) runs on that same action — so the filter is registered
	 * in time, with the current hook captured via closure since the filter
	 * itself isn't passed one.
	 *
	 * @param string $hook Current admin hook suffix.
	 */
	public function allow_react_editor_on_academy_pages( $hook ) {
		if ( ! \Academy\Helper::is_active_ecm() || ! \Academy\Helper::plugin_page_hook_suffix( $hook ) ) {
			return;
		}

		add_filter(
			'easy_content_manager/react_editor_allowed_hooks',
			function ( $hooks ) use ( $hook ) {
				$hooks[] = $hook;
				return $hooks;
			}
		);
	}
}
