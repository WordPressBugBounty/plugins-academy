<?php
namespace Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registration forms built with blocks: a Registration Form block holding
 * Form Field blocks, on any page, pattern or template.
 *
 * When a post is saved, every form in it is written down (its role and
 * fields) under the form's own ID. A sign-up then checks the submission
 * against that saved list, never against what the browser sends, and the
 * classic Form Builder settings stay in use for the shortcode.
 */
class RegistrationForms {

	/**
	 * Option holding every block form: form ID => role, post, fields, redirect.
	 */
	const OPTION = 'academy_registration_forms';

	/**
	 * Fields every account needs; the register endpoint requires them.
	 */
	const ACCOUNT_FIELDS = [ 'email', 'password', 'confirm-password' ];

	/**
	 * Fields that fill the account itself rather than profile details. The
	 * free plugin shows only these; any other field needs Academy Pro.
	 */
	const BASIC_FIELDS = [ 'first-name', 'last-name', 'email', 'confirm-email', 'password', 'confirm-password' ];

	/**
	 * Field types a Form Field block can be.
	 */
	const TYPES = [ 'text', 'email', 'password', 'tel', 'number', 'date', 'url', 'textarea', 'select', 'radio', 'checkbox' ];

	/**
	 * The form a sign-up in this request came from.
	 *
	 * @var array|null
	 */
	private static $current = null;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'save_post', [ __CLASS__, 'index_post' ], 20, 2 );
		add_action( 'deleted_post', [ __CLASS__, 'forget_post' ] );
		add_filter( 'academy/api/auth/after_register_student_redirect', [ __CLASS__, 'redirect' ], 20 );
		add_filter( 'academy/api/auth/after_register_instructor_redirect', [ __CLASS__, 'redirect' ], 20 );
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
	}

	/**
	 * Routes for Settings → Form Builder: where each form lives, and moving a
	 * registration page to the block form.
	 *
	 * @return void
	 */
	public static function register_routes() {
		$permission = static function () {
			return current_user_can( 'manage_options' );
		};
		register_rest_route(
			'academy/v1',
			'/registration-forms',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => static function () {
					return rest_ensure_response( self::pages() );
				},
				'permission_callback' => $permission,
			]
		);
		register_rest_route(
			'academy/v1',
			'/registration-forms/convert',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'convert' ],
				'permission_callback' => $permission,
				'args'                => [
					'role' => [
						'type'     => 'string',
						'enum'     => [ 'student', 'instructor' ],
						'required' => true,
					],
				],
			]
		);
	}

	/**
	 * The registration page of each role and whether it uses the block form.
	 *
	 * @return array
	 */
	public static function pages() {
		$pages = [];
		foreach ( [ 'student', 'instructor' ] as $role ) {
			$id   = (int) Helper::get_settings( 'frontend_' . $role . '_reg_page' );
			$post = $id ? get_post( $id ) : null;
			$pages[ $role ] = [
				'role'     => $role,
				'id'       => $post ? (int) $post->ID : 0,
				'title'    => $post ? get_the_title( $post ) : '',
				'usesBlock' => $post && has_block( 'academy/registration-form', $post->post_content ),
				'editUrl'  => $post ? (string) get_edit_post_link( $post->ID, 'raw' ) : '',
				'viewUrl'  => $post ? (string) get_permalink( $post ) : '',
			];
		}

		return $pages;
	}

	/**
	 * Put a block form, made from the classic Form Builder's fields, on a
	 * role's registration page, in place of the shortcode or classic block.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function convert( $request ) {
		$role = 'instructor' === $request->get_param( 'role' ) ? 'instructor' : 'student';
		$id   = (int) Helper::get_settings( 'frontend_' . $role . '_reg_page' );
		$post = $id ? get_post( $id ) : null;
		if ( ! $post ) {
			return new \WP_Error( 'academy_no_registration_page', __( 'Choose a registration page in Settings → Pages first.', 'academy' ), [ 'status' => 400 ] );
		}
		if ( ! has_block( 'academy/registration-form', $post->post_content ) ) {
			$form    = self::markup_from_settings( $role );
			$content = (string) $post->post_content;
			$classic = [
				'/<!-- wp:academy\/' . $role . '-registration-form[^>]*\/-->/',
				'/<!-- wp:shortcode -->\s*\[academy_' . $role . '_registration_form[^\]]*\]\s*<!-- \/wp:shortcode -->/',
				'/\[academy_' . $role . '_registration_form[^\]]*\]/',
			];
			$replaced = false;
			foreach ( $classic as $pattern ) {
				if ( preg_match( $pattern, $content ) ) {
					$content  = preg_replace( $pattern, $form, $content, 1 );
					$replaced = true;
					break;
				}
			}
			// An empty page showed the form on its own; anything else stays above it.
			$content = $replaced ? $content : trim( $content . "\n\n" . $form );
			$result  = wp_update_post(
				[
					'ID'           => $post->ID,
					'post_content' => wp_slash( $content ),
				],
				true
			);
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}//end if

		return rest_ensure_response( self::pages()[ $role ] );
	}

	/**
	 * Every block form.
	 *
	 * @return array
	 */
	public static function all() {
		$forms = get_option( self::OPTION, [] );

		return is_array( $forms ) ? $forms : [];
	}

	/**
	 * Write down the forms in a post, and forget the ones it no longer has.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post.
	 * @return void
	 */
	public static function index_post( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! $post instanceof \WP_Post ) {
			return;
		}
		$forms   = self::all();
		$changed = false;
		foreach ( $forms as $id => $form ) {
			if ( (int) ( $form['post'] ?? 0 ) === (int) $post_id ) {
				unset( $forms[ $id ] );
				$changed = true;
			}
		}
		// A form decides what a stranger's sign-up writes to their profile, so
		// only an admin's forms count (as with the classic Form Builder).
		$trusted = current_user_can( 'manage_options' ) || user_can( (int) $post->post_author, 'manage_options' );
		if ( $trusted && 'trash' !== $post->post_status && has_block( 'academy/registration-form', $post->post_content ) ) {
			foreach ( self::find( parse_blocks( $post->post_content ) ) as $block ) {
				$id = self::form_id( $block['attrs'] ?? [] );
				if ( '' === $id ) {
					continue;
				}
				$forms[ $id ] = [
					'role'     => self::role( $block['attrs'] ?? [] ),
					'post'     => (int) $post_id,
					'fields'   => self::fields( $block ),
					'redirect' => esc_url_raw( (string) ( $block['attrs']['redirectUrl'] ?? '' ) ),
				];
				$changed      = true;
			}
		}
		if ( $changed ) {
			update_option( self::OPTION, $forms, false );
		}
	}

	/**
	 * Forget the forms of a deleted post.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function forget_post( $post_id ) {
		$forms = array_filter(
			self::all(),
			static function ( $form ) use ( $post_id ) {
				return (int) ( $form['post'] ?? 0 ) !== (int) $post_id;
			}
		);
		update_option( self::OPTION, $forms, false );
	}

	/**
	 * Registration Form blocks anywhere in a block list.
	 *
	 * @param array $blocks Parsed blocks.
	 * @return array
	 */
	public static function find( array $blocks ) {
		$found = [];
		foreach ( $blocks as $block ) {
			if ( 'academy/registration-form' === ( $block['blockName'] ?? '' ) ) {
				$found[] = $block;
				continue;
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$found = array_merge( $found, self::find( $block['innerBlocks'] ) );
			}
		}

		return $found;
	}

	/**
	 * A form's ID.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function form_id( array $attributes ) {
		return preg_replace( '/[^a-z0-9-]/', '', strtolower( (string) ( $attributes['formId'] ?? '' ) ) );
	}

	/**
	 * Who a form signs up.
	 *
	 * @param array $attributes Block attributes.
	 * @return string student|instructor
	 */
	public static function role( array $attributes ) {
		return 'instructor' === ( $attributes['role'] ?? '' ) ? 'instructor' : 'student';
	}

	/**
	 * A field name: letters, digits, hyphens and underscores.
	 *
	 * @param string $name Name.
	 * @return string
	 */
	public static function field_name( $name ) {
		return trim( preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $name ) ), '-_' );
	}

	/**
	 * Whether a field name would write over one of Academy's own account
	 * settings (the saved field becomes academy_{name}).
	 *
	 * @param string $name Field name.
	 * @return bool
	 */
	public static function is_reserved( $name ) {
		$key = str_replace( '-', '_', $name );

		return (bool) preg_match( '/^(instructor_|is_academy|student_|dashboard_|menu_|guardian|earning|withdraw|capabilit|user_level|role)/', $key )
			|| in_array( $key, [ 'form_id', 'profile_photo', 'cover_photo', 'status' ], true );
	}

	/**
	 * One Form Field block as the registration code reads a field.
	 *
	 * @param array $attributes Field attributes.
	 * @return array|null
	 */
	public static function field( array $attributes ) {
		$name = self::field_name( $attributes['name'] ?? '' );
		// Profile fields are saved as academy_{name}; never over Academy's own.
		if ( '' === $name || self::is_reserved( $name ) ) {
			return null;
		}
		$type    = in_array( $attributes['fieldType'] ?? 'text', self::TYPES, true ) ? $attributes['fieldType'] : 'text';
		$options = [];
		foreach ( (array) ( $attributes['options'] ?? [] ) as $option ) {
			$label = sanitize_text_field( (string) ( $option['label'] ?? '' ) );
			$value = sanitize_text_field( (string) ( $option['value'] ?? '' ) );
			if ( '' !== $label || '' !== $value ) {
				$options[] = [
					'label' => '' !== $label ? $label : $value,
					'value' => '' !== $value ? $value : $label,
				];
			}
		}

		return [
			'name'        => $name,
			'type'        => $type,
			'label'       => sanitize_text_field( (string) ( $attributes['label'] ?? '' ) ),
			'placeholder' => sanitize_text_field( (string) ( $attributes['placeholder'] ?? '' ) ),
			// An account can't be made without these, whatever the block says.
			'is_required' => in_array( $name, self::ACCOUNT_FIELDS, true ) || ! empty( $attributes['required'] ),
			'options'     => $options,
		];
	}

	/**
	 * A form's fields, in the rows-of-columns shape the classic Form Builder
	 * uses, so the same validation and saving code handles both.
	 *
	 * @param array $block Parsed Registration Form block.
	 * @return array
	 */
	public static function fields( array $block ) {
		$rows = [];
		$walk = static function ( array $blocks ) use ( &$walk, &$rows ) {
			foreach ( $blocks as $inner ) {
				if ( 'academy/form-field' === ( $inner['blockName'] ?? '' ) ) {
					$field = self::field( $inner['attrs'] ?? [] );
					if ( $field ) {
						$rows[] = [ 'fields' => [ $field ] ];
					}
				} elseif ( ! empty( $inner['innerBlocks'] ) ) {
					$walk( $inner['innerBlocks'] );
				}
			}
		};
		$walk( $block['innerBlocks'] ?? [] );

		return $rows;
	}

	/**
	 * The fields a sign-up is checked against: its block form's, when it
	 * came from one for this role. Without Academy Pro only the account
	 * fields count, as only those are shown.
	 *
	 * @param string $form_id Form ID sent with the sign-up.
	 * @param string $role    student|instructor.
	 * @return array|null Null when the sign-up isn't from a block form.
	 */
	public static function fields_for( $form_id, $role ) {
		$form_id = self::form_id( [ 'formId' => $form_id ] );
		$forms   = self::all();
		if ( '' === $form_id || empty( $forms[ $form_id ] ) || $forms[ $form_id ]['role'] !== $role ) {
			return null;
		}
		self::$current = $forms[ $form_id ];
		$rows          = $forms[ $form_id ]['fields'];
		if ( ! Helper::is_active_academy_pro() ) {
			$rows = array_values(
				array_filter(
					$rows,
					static function ( $row ) {
						return in_array( $row['fields'][0]['name'] ?? '', self::BASIC_FIELDS, true );
					}
				)
			);
		}

		return $rows;
	}

	/**
	 * Where a sign-up from a block form goes next, when the form says.
	 *
	 * @param string $url Redirect URL.
	 * @return string
	 */
	public static function redirect( $url ) {
		if ( self::$current && ! empty( self::$current['redirect'] ) ) {
			return wp_validate_redirect( self::$current['redirect'], $url );
		}

		return $url;
	}

	/**
	 * Block markup for a form made from the classic Form Builder's fields, so
	 * a registration page can move to blocks without losing anything.
	 *
	 * @param string $role student|instructor.
	 * @return string
	 */
	public static function markup_from_settings( $role ) {
		$role     = 'instructor' === $role ? 'instructor' : 'student';
		$settings = json_decode( (string) get_option( 'academy_form_builder_settings', '' ), true );
		$rows     = is_array( $settings[ $role ] ?? null ) ? $settings[ $role ] : [];
		$inner    = '';
		$submit   = '';
		$names    = [];
		foreach ( $rows as $row ) {
			$columns = (array) ( $row['fields'] ?? [] );
			foreach ( $columns as $column ) {
				if ( 'button' === ( $column['name'] ?? '' ) ) {
					$submit = (string) ( $column['label'] ?? '' );
					continue;
				}
				$name = self::field_name( $column['name'] ?? '' );
				if ( '' === $name ) {
					continue;
				}
				$type = (string) ( $column['type'] ?? 'text' );
				// The classic builder kept email as a text box.
				if ( 'email' === $name || 'confirm-email' === $name ) {
					$type = 'email';
				}
				$attributes = [
					'name'        => $name,
					'fieldType'   => in_array( $type, self::TYPES, true ) ? $type : 'text',
					'label'       => (string) ( $column['label'] ?? '' ),
					'placeholder' => (string) ( $column['placeholder'] ?? '' ),
					'required'    => ! empty( $column['is_required'] ),
					'width'       => count( $columns ) > 1 ? 'half' : 'full',
				];
				if ( ! empty( $column['options'] ) ) {
					$attributes['options'] = array_values( (array) $column['options'] );
				}
				$names[] = $name;
				$inner  .= '<!-- wp:academy/form-field ' . wp_json_encode( $attributes ) . ' /-->';
			}//end foreach
		}//end foreach
		// A form without the account fields can't sign anyone up.
		$defaults = [
			'email'            => [ 'email', __( 'Email', 'academy' ) ],
			'password'         => [ 'password', __( 'Password', 'academy' ) ],
			'confirm-password' => [ 'password', __( 'Confirm Password', 'academy' ) ],
		];
		foreach ( $defaults as $name => $default ) {
			if ( ! in_array( $name, $names, true ) ) {
				$inner .= '<!-- wp:academy/form-field ' . wp_json_encode(
					[
						'name'      => $name,
						'fieldType' => $default[0],
						'label'     => $default[1],
						'required'  => true,
					]
				) . ' /-->';
			}
		}
		$attributes = [
			'role'   => $role,
			'formId' => wp_generate_uuid4(),
		];
		if ( '' !== $submit ) {
			$attributes['submitLabel'] = $submit;
		}

		return '<!-- wp:academy/registration-form ' . wp_json_encode( $attributes ) . ' -->' . $inner . '<!-- /wp:academy/registration-form -->';
	}
}
