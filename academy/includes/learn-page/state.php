<?php
namespace Academy\LearnPage;

use Academy\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The learn page store's state on the server. Every learn page block sets it,
 * so a block drawn on its own (as the block editor does) renders the same as
 * on the page. Values the browser works out are worked out here too, so the
 * first paint is already right.
 */
class State {

	const STORE = 'academy/learn';

	/**
	 * Set the state once per request.
	 *
	 * @return void
	 */
	public static function ensure() {
		static $done = false;
		if ( $done ) {
			return;
		}
		$done = true;

		$learn    = Data::current();
		$settings = Settings::get();

		$completed = [];
		foreach ( $learn['topics'] as $topic ) {
			if ( $topic['completed'] ) {
				$completed[ $topic['key'] ] = true;
			}
		}
		$title    = $learn['current'] ? $learn['current']['title'] : $learn['courseTitle'];
		$user_id  = get_current_user_id();
		$reviewed = $user_id && $learn['courseId'] ? (bool) Helper::get_review_by_user( $user_id, $learn['courseId'] ) : false;
		$left     = max( $learn['total'] - $learn['completed'], 0 );
		$minimum  = (int) Helper::get_settings( 'minimum_course_completion_on_review', 0 );
		$can      = $learn['loggedIn'] && ! $reviewed && (bool) Helper::get_settings( 'is_enabled_course_review', true );
		$widths   = [
			'standard' => __( 'Standard', 'academy' ),
			'wide'     => __( 'Wide', 'academy' ),
			'focused'  => __( 'Focused', 'academy' ),
		];

		wp_interactivity_state(
			self::STORE,
			[
				'courseId'        => $learn['courseId'],
				'currentKey'      => $learn['currentKey'],
				'currentType'     => $learn['type'],
				'completedKeys'   => (object) $completed,
				'total'           => $learn['total'],
				'pageTitle'       => wp_strip_all_tags( $title . ' – ' . $learn['courseTitle'] ),
				'defaultTheme'    => $settings['theme'],
				'sidebarOpen'     => (bool) $settings['sidebarOpen'],
				'width'           => $settings['width'],
				'drawer'          => '',
				'menuOpen'        => false,
				'notice'          => '',
				'busy'            => false,
				'userTheme'       => '',
				'small'           => false,
				'navigating'      => false,
				'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'academy_nonce' ),
				'loggedIn'        => $learn['loggedIn'],
				'canReviewCourse' => $can,
				'reviewMinimum'   => $minimum,
				// Topics whose content loads its own scripts: open them as a new page.
				'fullReloadTypes' => apply_filters( 'academy/learn_page/full_reload_types', [ 'booking', 'quizpress_quiz' ] ),

				// Worked out in the browser too (view.js); these are first-paint values.
				'isDark'          => false,
				'percentText'     => $learn['percent'] . '%',
				/* translators: %d: percent of the course completed. */
				'percentComplete' => sprintf( __( '%d%% complete', 'academy' ), $learn['percent'] ),
				/* translators: %d: topics left. */
				'leftText'        => sprintf( _n( '%d topic left', '%d topics left', $left, 'academy' ), $left ),
				/* translators: 1: completed topics, 2: all topics. */
				'progressCount'   => sprintf( __( '%1$d of %2$d done', 'academy' ), $learn['completed'], $learn['total'] ),
				/* translators: %d: percent of the course completed. */
				'progressLabel'   => sprintf( __( 'Course progress: %d%%', 'academy' ), $learn['percent'] ),
				'progressOffset'  => number_format( 2 * M_PI * 15 * ( 1 - $learn['percent'] / 100 ), 2, '.', '' ),
				'sidebarInert'    => false,
				/* translators: %s: content width, e.g. Wide. */
				'widthLabel'      => sprintf( __( 'Content width: %s', 'academy' ), $widths[ $settings['width'] ] ?? $settings['width'] ),
				'canReview'       => $can && $learn['percent'] >= $minimum,
				'isCurrent'       => static function () use ( $learn ) {
					$context = wp_interactivity_get_context( self::STORE );
					return isset( $context['key'] ) && $context['key'] === $learn['currentKey'];
				},
				'ariaCurrent'     => static function () use ( $learn ) {
					$context = wp_interactivity_get_context( self::STORE );
					return isset( $context['key'] ) && $context['key'] === $learn['currentKey'] ? 'page' : null;
				},
				'isCompleted'     => static function () use ( $completed ) {
					$context = wp_interactivity_get_context( self::STORE );
					return isset( $context['key'] ) && ! empty( $completed[ $context['key'] ] );
				},
				'completeLabel'   => static function () use ( $completed ) {
					$context = wp_interactivity_get_context( self::STORE );
					return isset( $context['key'] ) && ! empty( $completed[ $context['key'] ] ) ? __( 'Completed', 'academy' ) : __( 'Mark as complete', 'academy' );
				},
				'isDrawerOpen'    => false,
				'favoriteLabel'   => static function () {
					$context = wp_interactivity_get_context( self::STORE );
					return ! empty( $context['favorited'] ) ? __( 'Remove from favorites', 'academy' ) : __( 'Add to favorites', 'academy' );
				},
				'i18n'            => [
					'loginToTrack'    => __( 'Log in to keep track of your progress.', 'academy' ),
					'failed'          => __( 'That did not work. Please try again.', 'academy' ),
					'markComplete'    => __( 'Mark as complete', 'academy' ),
					'completed'       => __( 'Completed', 'academy' ),
					/* translators: %d: percent of the course completed. */
					'percentComplete' => __( '%d%% complete', 'academy' ),
					/* translators: %d: topics left. */
					'topicLeft'       => __( '%d topic left', 'academy' ),
					/* translators: %d: topics left. */
					'topicsLeft'      => __( '%d topics left', 'academy' ),
					/* translators: 1: completed topics, 2: all topics. */
					'progressCount'   => __( '%1$d of %2$d done', 'academy' ),
					/* translators: %d: percent of the course completed. */
					'progressLabel'   => __( 'Course progress: %d%%', 'academy' ),
					/* translators: %s: content width, e.g. Wide. */
					'widthLabel'      => __( 'Content width: %s', 'academy' ),
					'widths'          => $widths,
					'addFavorite'     => __( 'Add to favorites', 'academy' ),
					'removeFavorite'  => __( 'Remove from favorites', 'academy' ),
				],
			]
		);
	}
}
