<?php
/**
 * REST API for the React settings screen.
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking;

use ForWP\Booking\Forms\Form_Source_Registry;
use ForWP\Booking\Providers\ClinicCards_Provider;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Registers routes under `forwp-booking/v1`.
 */
final class Rest_Settings {

	private const NAMESPACE = 'forwp-booking/v1';

	/**
	 * Hook REST route registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'register_routes' ) );
	}

	/**
	 * Register admin settings and preview routes.
	 *
	 * @return void
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/settings',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( self::class, 'get_settings' ),
					'permission_callback' => array( self::class, 'can_manage' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( self::class, 'update_settings' ),
					'permission_callback' => array( self::class, 'can_manage' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/preview',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( self::class, 'get_preview' ),
					'permission_callback' => array( self::class, 'can_manage' ),
					'args'                => array(
						'provider' => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_key',
						),
						'refresh'  => array(
							'type'     => 'boolean',
							'required' => false,
							'default'  => false,
						),
					),
				),
			)
		);
	}

	/**
	 * Whether the current user may manage settings.
	 *
	 * @return bool
	 */
	public static function can_manage(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * GET settings payload for the admin UI.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_settings(): WP_REST_Response {
		$slug       = Admin_Settings::instance()->get_provider_slug();
		$stored_key = Admin_Settings::instance()->get_provider_token( $slug );

		$telegram_token = Admin_Settings::instance()->get_channel_token( 'telegram' );

		return new WP_REST_Response(
			array(
				'credential_provider'       => $slug,
				'api_key_configured'        => '' !== $stored_key,
				'api_key_length'            => strlen( $stored_key ),
				'default_template'          => Admin_Settings::instance()->get_default_template(),
				'booking_flow'              => Admin_Settings::instance()->get_booking_flow(),
				'copy'                      => Admin_Settings::instance()->get_copy_overrides(),
				'copy_defaults'             => Admin_Settings::default_copy(),
				'templates'                 => Template_Registry::get_admin_rows(),
				'providers'                 => Provider_Registry::get_admin_status_rows(),
				'notify_emails'             => Admin_Settings::instance()->get_notify_emails_raw(),
				'admin_email'               => (string) get_option( 'admin_email', '' ),
				'channels'                  => Channel_Registry::get_admin_status_rows(),
				'telegram_token_configured' => '' !== $telegram_token,
				'telegram_token_length'     => strlen( $telegram_token ),
				'telegram_chat_ids'         => Admin_Settings::instance()->get_channel_targets_raw( 'telegram' ),
				'form_telegram'             => Admin_Settings::instance()->get_form_telegram_settings(),
				'form_sources'              => Form_Source_Registry::get_admin_rows(),
				'appearance'                => Appearance::get_overrides(),
				'appearance_defaults'       => Appearance::site_defaults(),
				'appearance_sources'        => Appearance::sources(),
				'shadow_presets'            => array_keys( Appearance::shadow_presets() ),
			),
			200
		);
	}

	/**
	 * Live offerings sample for the React preview panel.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function get_preview( WP_REST_Request $request ): WP_REST_Response {
		$slug = $request->get_param( 'provider' );
		$slug = is_string( $slug ) ? sanitize_key( $slug ) : '';
		if ( '' === $slug ) {
			$slug = Admin_Settings::instance()->get_provider_slug();
		}

		$provider = Provider_Registry::get( $slug );
		if ( ! $provider || ! $provider->is_implemented() ) {
			return new WP_REST_Response(
				array(
					'ready'           => false,
					'offerings_count' => 0,
					'offerings'       => array(),
					'error'           => __( 'This provider is not implemented yet.', '4wp-booking' ),
				),
				200
			);
		}

		if ( $request->get_param( 'refresh' ) ) {
			ClinicCards_Provider::bump_cache();
		}

		if ( ! $provider->is_ready() ) {
			return new WP_REST_Response(
				array(
					'ready'           => false,
					'offerings_count' => 0,
					'offerings'       => array(),
					'error'           => __( 'API key is not configured.', '4wp-booking' ),
				),
				200
			);
		}

		$offerings = $provider->get_offerings();
		if ( is_wp_error( $offerings ) ) {
			return new WP_REST_Response(
				array(
					'ready'           => true,
					'offerings_count' => 0,
					'offerings'       => array(),
					'error'           => $offerings->get_error_message(),
				),
				200
			);
		}

		$list = array();
		foreach ( (array) $offerings as $item ) {
			if ( isset( $item['label'] ) ) {
				$list[] = array(
					'label' => (string) $item['label'],
				);
			}
		}

		return new WP_REST_Response(
			array(
				'ready'           => true,
				'offerings_count' => count( $list ),
				'offerings'       => $list,
				'error'           => null,
			),
			200
		);
	}

	/**
	 * POST updated credential provider and optionally API key.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public static function update_settings( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			$params = array();
		}

		if ( array_key_exists( 'credential_provider', $params ) ) {
			$slug = sanitize_key( (string) $params['credential_provider'] );
			if ( '' === $slug || ! in_array( $slug, Provider_Registry::implemented_slugs(), true ) ) {
				return new \WP_Error(
					'forwp_booking_invalid_provider',
					__( 'Invalid credential provider.', '4wp-booking' ),
					array( 'status' => 400 )
				);
			}
			Admin_Settings::instance()->set_provider_slug( $slug );
		}

		if ( array_key_exists( 'api_key', $params ) ) {
			$key = $params['api_key'];
			$key = is_string( $key ) ? Admin_Settings::instance()->sanitize_api_key( $key ) : '';
			if ( '' !== $key ) {
				$slug = Admin_Settings::instance()->get_provider_slug();
				$prev = Admin_Settings::instance()->get_provider_token( $slug );
				Admin_Settings::instance()->set_provider_token( $slug, $key );
				if ( $prev !== $key ) {
					ClinicCards_Provider::bump_cache();
				}
			}
		}

		if ( array_key_exists( 'default_template', $params ) ) {
			$tpl = sanitize_key( (string) $params['default_template'] );
			if ( Template_Registry::has( $tpl ) ) {
				Admin_Settings::instance()->set_default_template( $tpl );
			}
		}

		if ( array_key_exists( 'booking_flow', $params ) ) {
			Admin_Settings::instance()->set_booking_flow( (string) $params['booking_flow'] );
		}

		if ( array_key_exists( 'copy', $params ) && is_array( $params['copy'] ) ) {
			Admin_Settings::instance()->set_copy( $params['copy'] );
		}

		if ( array_key_exists( 'notify_emails', $params ) ) {
			$raw = is_string( $params['notify_emails'] ) ? $params['notify_emails'] : '';
			Admin_Settings::instance()->set_notify_emails( $raw );
		}

		if ( array_key_exists( 'telegram_token', $params ) ) {
			$token = is_string( $params['telegram_token'] ) ? Admin_Settings::instance()->sanitize_api_key( $params['telegram_token'] ) : '';
			Admin_Settings::instance()->set_channel_token( 'telegram', $token );
		}

		if ( array_key_exists( 'telegram_chat_ids', $params ) ) {
			$ids = is_string( $params['telegram_chat_ids'] ) ? $params['telegram_chat_ids'] : '';
			Admin_Settings::instance()->set_channel_targets( 'telegram', $ids );
		}

		if ( array_key_exists( 'form_telegram', $params ) && is_array( $params['form_telegram'] ) ) {
			Admin_Settings::instance()->set_form_telegram_settings( $params['form_telegram'] );
		}

		if ( array_key_exists( 'appearance', $params ) && is_array( $params['appearance'] ) ) {
			Appearance::set_overrides( $params['appearance'] );
		}

		return self::get_settings();
	}
}
