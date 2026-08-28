<?php
/**
 * Registered booking providers.
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking;

use ForWP\Booking\Contracts\Booking_Credential_Help_Interface;
use ForWP\Booking\Contracts\Booking_Provider_Interface;
use ForWP\Booking\Providers\ClinicCards_Provider;
use ForWP\Booking\Providers\Placeholder_Provider;

defined( 'ABSPATH' ) || exit;

/**
 * Central registry; extend via filter for addons/tests.
 */
final class Provider_Registry {

	/**
	 * Cached provider map after first load.
	 *
	 * @var array<string, Booking_Provider_Interface>|null
	 */
	private static $providers = null;

	/**
	 * All providers keyed by slug.
	 *
	 * @return array<string, Booking_Provider_Interface>
	 */
	public static function all(): array {
		if ( null !== self::$providers ) {
			return self::$providers;
		}

		$map = array(
			ClinicCards_Provider::SLUG => new ClinicCards_Provider(),
			'google_calendar'          => new Placeholder_Provider(
				'google_calendar',
				__( 'Google Calendar', '4wp-booking' )
			),
			'calendly'                 => new Placeholder_Provider(
				'calendly',
				__( 'Calendly', '4wp-booking' )
			),
		);

		/**
		 * Filter full provider map before freeze.
		 *
		 * @param array<string, Booking_Provider_Interface> $map Providers.
		 */
		$filtered        = apply_filters( 'forwp_booking_providers', $map );
		self::$providers = is_array( $filtered ) ? $filtered : $map;

		return self::$providers;
	}

	/**
	 * Resolve a provider by slug.
	 *
	 * @param string $slug Provider slug.
	 * @return Booking_Provider_Interface|null
	 */
	public static function get( string $slug ) {
		$slug = sanitize_key( $slug );
		$all  = self::all();

		return isset( $all[ $slug ] ) ? $all[ $slug ] : null;
	}

	/**
	 * Active provider from settings, falling back to the first implemented ready/implemented one.
	 *
	 * @return Booking_Provider_Interface|null
	 */
	public static function active() {
		$slug     = Admin_Settings::instance()->get_provider_slug();
		$provider = self::get( $slug );
		if ( $provider && $provider->is_implemented() ) {
			return $provider;
		}

		foreach ( self::all() as $item ) {
			if ( $item->is_implemented() ) {
				return $item;
			}
		}

		return null;
	}

	/**
	 * Slugs that may be stored on blocks (implemented only).
	 *
	 * @return string[]
	 */
	public static function implemented_slugs(): array {
		$out = array();
		foreach ( self::all() as $slug => $provider ) {
			if ( $provider->is_implemented() ) {
				$out[] = $slug;
			}
		}

		return $out;
	}

	/**
	 * Settings screen rows (REST).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_admin_status_rows(): array {
		$rows       = array();
		$credential = Admin_Settings::instance()->get_provider_slug();

		foreach ( self::all() as $provider ) {
			if ( $provider->is_implemented() ) {
				if ( $credential !== $provider->get_slug() ) {
					$status = __( 'Not used for API credentials', '4wp-booking' );
				} elseif ( $provider->is_ready() ) {
					$status = __( 'Ready', '4wp-booking' );
				} else {
					$status = __( 'Needs API key', '4wp-booking' );
				}
			} else {
				$status = __( 'Planned (stub)', '4wp-booking' );
			}

			$row = array(
				'slug'        => $provider->get_slug(),
				'label'       => $provider->get_label(),
				'status'      => $status,
				'implemented' => $provider->is_implemented(),
			);

			if ( $provider instanceof Booking_Credential_Help_Interface ) {
				$url = esc_url_raw(
					$provider->get_api_key_docs_url(),
					array( 'http', 'https' )
				);
				if ( '' !== $url ) {
					$row['api_key_help_intro']      = $provider->get_api_key_help_intro();
					$row['api_key_docs_url']        = $url;
					$row['api_key_docs_link_label'] = $provider->get_api_key_docs_link_label();
				}
			}

			$rows[] = $row;
		}

		return $rows;
	}

	/**
	 * Reset static cache (tests).
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$providers = null;
	}
}
