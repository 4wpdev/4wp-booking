<?php
/**
 * REST routes for the booking form.
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking;

use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Public booking API (server-side provider calls).
 */
final class Rest_Booking {

	private const NAMESPACE = 'forwp-booking/v1';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'register_routes' ) );
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/offerings',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( self::class, 'get_offerings' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'provider' => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_key',
						),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/slots',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( self::class, 'get_slots' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'provider'    => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_key',
						),
						'offering_id' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'from'        => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'to'          => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/availability',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( self::class, 'get_availability' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'provider'   => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_key',
						),
						'from'       => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'to'         => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'service_id' => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/book',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( self::class, 'create_booking' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'provider'    => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_key',
						),
						'offering_id' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'date'        => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'time_start'  => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'first_name'  => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'last_name'   => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'phone'       => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'email'       => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_email',
						),
						'comment'     => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_textarea_field',
						),
						'website'     => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
	}

	/**
	 * List offerings.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public static function get_offerings( WP_REST_Request $request ) {
		$provider = self::resolve_provider( (string) $request->get_param( 'provider' ) );
		if ( is_wp_error( $provider ) ) {
			return $provider;
		}

		$data = $provider->get_offerings();
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		return new WP_REST_Response(
			array(
				'offerings' => $data,
			),
			200
		);
	}

	/**
	 * List slots.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public static function get_slots( WP_REST_Request $request ) {
		$from = (string) $request->get_param( 'from' );
		$to   = (string) $request->get_param( 'to' );
		if ( ! self::valid_date( $from ) || ! self::valid_date( $to ) ) {
			return new \WP_Error( 'forwp_booking_date', __( 'Invalid date range.', '4wp-booking' ), array( 'status' => 400 ) );
		}

		$provider = self::resolve_provider( (string) $request->get_param( 'provider' ) );
		if ( is_wp_error( $provider ) ) {
			return $provider;
		}

		$data = $provider->get_slots( (string) $request->get_param( 'offering_id' ), $from, $to );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$public = array();
		foreach ( (array) $data as $slot ) {
			$public[] = array(
				'date'       => $slot['date'],
				'time_start' => $slot['time_start'],
				'time_end'   => $slot['time_end'],
			);
		}

		return new WP_REST_Response(
			array(
				'slots' => $public,
			),
			200
		);
	}

	/**
	 * Slots for every offering in a date range (date-first calendar).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public static function get_availability( WP_REST_Request $request ) {
		$from = (string) $request->get_param( 'from' );
		$to   = (string) $request->get_param( 'to' );
		if ( ! self::valid_date( $from ) || ! self::valid_date( $to ) ) {
			return new \WP_Error( 'forwp_booking_date', __( 'Invalid date range.', '4wp-booking' ), array( 'status' => 400 ) );
		}

		$provider = self::resolve_provider( (string) $request->get_param( 'provider' ) );
		if ( is_wp_error( $provider ) ) {
			return $provider;
		}

		$offerings = $provider->get_offerings();
		if ( is_wp_error( $offerings ) ) {
			return $offerings;
		}

		$service_id       = (string) $request->get_param( 'service_id' );
		$public           = array();
		$seen_specialists = array();
		foreach ( (array) $offerings as $offering ) {
			if ( ! is_array( $offering ) || empty( $offering['id'] ) ) {
				continue;
			}
			if ( '' !== $service_id ) {
				$offering_service = isset( $offering['service_id'] ) ? (string) $offering['service_id'] : '';
				if ( $offering_service !== $service_id ) {
					continue;
				}
			}
			$specialist_id = isset( $offering['specialist_id'] ) ? (string) $offering['specialist_id'] : '';
			if ( '' !== $specialist_id ) {
				if ( isset( $seen_specialists[ $specialist_id ] ) ) {
					continue;
				}
				$seen_specialists[ $specialist_id ] = true;
			}
			$slots = $provider->get_slots( (string) $offering['id'], $from, $to );
			if ( is_wp_error( $slots ) ) {
				continue;
			}
			$label = '';
			if ( isset( $offering['specialist_label'] ) && '' !== (string) $offering['specialist_label'] ) {
				$label = (string) $offering['specialist_label'];
			} elseif ( isset( $offering['label'] ) ) {
				$label = (string) $offering['label'];
			}
			foreach ( (array) $slots as $slot ) {
				if ( ! is_array( $slot ) ) {
					continue;
				}
				$public[] = array(
					'date'        => isset( $slot['date'] ) ? (string) $slot['date'] : '',
					'time_start'  => isset( $slot['time_start'] ) ? (string) $slot['time_start'] : '',
					'time_end'    => isset( $slot['time_end'] ) ? (string) $slot['time_end'] : '',
					'offering_id' => (string) $offering['id'],
					'label'       => $label,
				);
			}
		}

		return new WP_REST_Response(
			array(
				'slots' => $public,
			),
			200
		);
	}

	/**
	 * Create a booking.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public static function create_booking( WP_REST_Request $request ) {
		$honeypot = (string) $request->get_param( 'website' );
		if ( '' !== $honeypot ) {
			return new \WP_Error( 'forwp_booking_spam', __( 'Booking could not be submitted.', '4wp-booking' ), array( 'status' => 400 ) );
		}

		if ( ! self::allow_book_attempt() ) {
			return new \WP_Error( 'forwp_booking_rate', __( 'Please wait before submitting another booking.', '4wp-booking' ), array( 'status' => 429 ) );
		}

		$provider = self::resolve_provider( (string) $request->get_param( 'provider' ) );
		if ( is_wp_error( $provider ) ) {
			return $provider;
		}

		$result = $provider->create_booking( $request->get_params() );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		try {
			Notifier::dispatch( self::booking_notice_payload( $provider, $request, is_array( $result ) ? $result : array() ) );
		} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			unset( $e );
		}

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Fields for email / messenger copies of the booking.
	 *
	 * @param \ForWP\Booking\Contracts\Booking_Provider_Interface $provider Provider.
	 * @param WP_REST_Request                                     $request  Request.
	 * @param array<string, mixed>                                $result   Provider result.
	 * @return array<string, string>
	 */
	private static function booking_notice_payload( $provider, WP_REST_Request $request, array $result ): array {
		$offering_id = (string) $request->get_param( 'offering_id' );
		$doctor      = '';
		$offerings   = $provider->get_offerings();
		if ( ! is_wp_error( $offerings ) ) {
			foreach ( (array) $offerings as $item ) {
				if ( is_array( $item ) && isset( $item['id'] ) && (string) $item['id'] === $offering_id ) {
					$doctor = isset( $item['label'] ) ? (string) $item['label'] : '';
					break;
				}
			}
		}

		return array(
			'doctor'     => $doctor,
			'date'       => isset( $result['date'] ) ? (string) $result['date'] : (string) $request->get_param( 'date' ),
			'time'       => isset( $result['time'] ) ? (string) $result['time'] : (string) $request->get_param( 'time_start' ),
			'first_name' => sanitize_text_field( (string) $request->get_param( 'first_name' ) ),
			'last_name'  => sanitize_text_field( (string) $request->get_param( 'last_name' ) ),
			'phone'      => sanitize_text_field( (string) $request->get_param( 'phone' ) ),
			'email'      => sanitize_email( (string) $request->get_param( 'email' ) ),
			'comment'    => sanitize_textarea_field( (string) $request->get_param( 'comment' ) ),
		);
	}

	/**
	 * Resolve implemented provider.
	 *
	 * @param string $slug Requested slug.
	 * @return \ForWP\Booking\Contracts\Booking_Provider_Interface|\WP_Error
	 */
	private static function resolve_provider( string $slug ) {
		if ( '' === $slug ) {
			$provider = Provider_Registry::active();
		} else {
			$provider = Provider_Registry::get( $slug );
		}

		if ( ! $provider || ! $provider->is_implemented() ) {
			return new \WP_Error( 'forwp_booking_provider', __( 'Booking provider is not available.', '4wp-booking' ), array( 'status' => 400 ) );
		}

		if ( ! $provider->is_ready() ) {
			return new \WP_Error( 'forwp_booking_not_ready', __( 'Booking is not configured yet.', '4wp-booking' ), array( 'status' => 503 ) );
		}

		return $provider;
	}

	/**
	 * YYYY-MM-DD check.
	 *
	 * @param string $date Date.
	 * @return bool
	 */
	private static function valid_date( string $date ): bool {
		return (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date );
	}

	/**
	 * Simple per-IP rate limit for POST /book.
	 *
	 * @return bool
	 */
	private static function allow_book_attempt(): bool {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0';
		$key = 'forwp_booking_rate_' . md5( $ip );
		$n   = (int) get_transient( $key );
		if ( $n >= 8 ) {
			return false;
		}
		set_transient( $key, $n + 1, HOUR_IN_SECONDS );

		return true;
	}
}
