<?php
namespace Academy\Design;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ready-made designs.
 *
 * A preset is an ordinary design: the same parts, order, and style values a
 * person would set by hand on the Design screen. Applying one fills the screen
 * in, and nothing is saved until they press Save — so a preset is a starting
 * point, never a lock-in.
 */
class Presets {

	/**
	 * The design of one preset.
	 *
	 * @param string $key Preset key.
	 * @return array|null
	 */
	public static function design( $key ) {
		foreach ( self::all() as $preset ) {
			if ( $preset['key'] === $key ) {
				return $preset['design'];
			}
		}

		return null;
	}

	/**
	 * Every preset, as complete designs.
	 *
	 * @return array[]
	 */
	public static function all() {
		$presets = [
			[
				'key'         => 'classic',
				'label'       => __( 'Classic', 'academy' ),
				'description' => __( 'Everything a course has to show: image, categories, instructor, rating and price.', 'academy' ),
				'design'      => Settings::defaults(),
			],
			[
				'key'         => 'minimal',
				'label'       => __( 'Minimal', 'academy' ),
				'description' => __( 'Just the essentials, in a wider grid. Good for a short catalog.', 'academy' ),
				'design'      => [
					'catalog' => [
						'columns'    => [
							'desktop' => 4,
							'tablet'  => 2,
							'mobile'  => 1,
						],
						'imageRatio' => '4/3',
						'elements'   => self::elements( [ 'image', 'title', 'meta', 'price' ] ),
					],
					'single'  => [
						'sidebar'  => 'right',
						'sections' => self::sections(
							[ 'media', 'title', 'meta', 'description', 'curriculum', 'reviews' ]
						),
					],
					'style'   => [
						'radius'       => 6,
						'buttonRadius' => 6,
						'titleSize'    => 16,
					],
				],
			],
			[
				'key'         => 'modern',
				'label'       => __( 'Modern', 'academy' ),
				'description' => __( 'Roomy cards with a summary and a big title. Rounded corners throughout.', 'academy' ),
				'design'      => [
					'catalog' => [
						'columns'    => [
							'desktop' => 3,
							'tablet'  => 2,
							'mobile'  => 1,
						],
						'imageRatio' => '16/9',
						'elements'   => self::elements(
							[ 'image', 'badge', 'categories', 'title', 'excerpt', 'instructors', 'meta', 'rating', 'price', 'button' ]
						),
					],
					'single'  => [
						'sidebar'  => 'right',
						'sections' => self::sections(
							[ 'media', 'categories', 'title', 'meta', 'description', 'benefits', 'curriculum', 'instructorProfile', 'ratingSummary', 'reviews', 'reviewForm' ]
						),
					],
					'style'   => [
						'radius'       => 16,
						'buttonRadius' => 12,
						'titleSize'    => 20,
					],
				],
			],
			[
				'key'         => 'marketplace',
				'label'       => __( 'Marketplace', 'academy' ),
				'description' => __( 'For a catalog with many instructors: badges, ratings, wishlist and a tight grid.', 'academy' ),
				'design'      => [
					'catalog' => [
						'columns'    => [
							'desktop' => 4,
							'tablet'  => 2,
							'mobile'  => 1,
						],
						'imageRatio' => '16/9',
						'elements'   => self::elements(
							[ 'image', 'badge', 'categories', 'title', 'instructors', 'rating', 'meta', 'price', 'button', 'wishlist' ]
						),
					],
					'single'  => [
						'sidebar'  => 'right',
						'sections' => self::sections(
							[ 'media', 'categories', 'title', 'meta', 'description', 'benefits', 'curriculum', 'attachments', 'instructorProfile', 'ratingSummary', 'reviewForm', 'reviews' ]
						),
					],
					'style'   => [
						'radius'       => 10,
						'buttonRadius' => 8,
						'titleSize'    => 16,
					],
				],
			],
			[
				'key'         => 'landing',
				'label'       => __( 'Sales page', 'academy' ),
				'description' => __( 'Course pages read like a sales page: what they will learn comes before the curriculum.', 'academy' ),
				'design'      => [
					'catalog' => [
						'columns'    => [
							'desktop' => 3,
							'tablet'  => 2,
							'mobile'  => 1,
						],
						'imageRatio' => '16/9',
						'elements'   => self::elements( [ 'image', 'badge', 'title', 'rating', 'price', 'button' ] ),
					],
					'single'  => [
						'sidebar'  => 'right',
						'sections' => self::sections(
							[ 'media', 'title', 'meta', 'benefits', 'description', 'curriculum', 'instructorProfile', 'ratingSummary', 'reviews' ]
						),
					],
					'style'   => [
						'radius'       => 12,
						'buttonRadius' => 40,
						'titleSize'    => 18,
					],
				],
			],
			[
				'key'         => 'training',
				'label'       => __( 'Internal training', 'academy' ),
				'description' => __( 'No prices and no marketing: a plain library for staff or members.', 'academy' ),
				'design'      => [
					'catalog' => [
						'columns'    => [
							'desktop' => 3,
							'tablet'  => 2,
							'mobile'  => 1,
						],
						'imageRatio' => '16/9',
						'elements'   => self::elements( [ 'image', 'categories', 'title', 'meta', 'button' ] ),
					],
					'single'  => [
						'sidebar'  => 'right',
						'sections' => self::sections(
							[ 'media', 'title', 'meta', 'description', 'benefits', 'curriculum', 'attachments', 'instructorProfile' ]
						),
					],
					'style'   => [
						'radius'       => 8,
						'buttonRadius' => 6,
						'titleSize'    => 18,
					],
				],
			],
		];

		foreach ( $presets as $index => $preset ) {
			$presets[ $index ]['design'] = Settings::sanitize( $preset['design'] );
		}

		/**
		 * Filters the ready-made designs offered on the Design screen.
		 *
		 * @param array[] $presets Presets, each with a key, label, description and design.
		 */
		return apply_filters( 'academy/design/presets', $presets );
	}

	/**
	 * A card's parts: the ones named, in that order, then the rest switched off.
	 *
	 * @param array $keys Parts to show.
	 * @return array
	 */
	private static function elements( array $keys ) {
		return self::ordered( $keys, Settings::card_elements() );
	}

	/**
	 * A course page's sections: the ones named, in that order, then the rest off.
	 *
	 * @param array $keys Sections to show.
	 * @return array
	 */
	private static function sections( array $keys ) {
		return self::ordered( $keys, Settings::page_sections() );
	}

	/**
	 * Put the named parts first and switch the rest off, so a preset says what
	 * it shows and a later Academy release can add parts without surprises.
	 *
	 * @param array $keys  Parts to show, in order.
	 * @param array $known Every part there is.
	 * @return array
	 */
	private static function ordered( array $keys, array $known ) {
		$list = [];
		foreach ( $keys as $key ) {
			if ( isset( $known[ $key ] ) ) {
				$list[] = [
					'key'     => $key,
					'visible' => true,
				];
			}
		}
		foreach ( array_keys( $known ) as $key ) {
			if ( ! in_array( $key, $keys, true ) ) {
				$list[] = [
					'key'     => $key,
					'visible' => false,
				];
			}
		}

		return $list;
	}
}
