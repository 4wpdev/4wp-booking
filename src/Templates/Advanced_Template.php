<?php
/**
 * Advanced booking template (three-column Calendly-style board).
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking\Templates;

use ForWP\Booking\Admin_Settings;
use ForWP\Booking\Contracts\Booking_Template_Interface;
use ForWP\Booking\Front_Assets;
use ForWP\Booking\Provider_Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Doctor switcher + month + times, then guest details.
 */
final class Advanced_Template implements Booking_Template_Interface {

	public const SLUG = 'advanced';

	/**
	 * Template slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return self::SLUG;
	}

	/**
	 * Template label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Advanced', '4wp-booking' );
	}

	/**
	 * Admin catalog description.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Three columns: list, calendar, and times. Guests start from a service, a doctor, or a date.', '4wp-booking' );
	}

	/**
	 * Front-end assets.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		Front_Assets::enqueue();
	}

	/**
	 * Render shell.
	 *
	 * @param array<string, mixed> $context Context.
	 * @return string
	 */
	public function render( array $context ): string {
		$this->enqueue_assets();

		$provider = isset( $context['provider'] ) ? sanitize_key( (string) $context['provider'] ) : '';
		if ( '' === $provider ) {
			$active   = Provider_Registry::active();
			$provider = $active ? $active->get_slug() : 'cliniccards';
		}

		$uid  = isset( $context['uid'] ) ? sanitize_html_class( (string) $context['uid'] ) : wp_unique_id( 'forwp-booking-' );
		$flow = isset( $context['flow'] ) ? Admin_Settings::sanitize_booking_flow( (string) $context['flow'] ) : '';
		if ( '' === $flow ) {
			$flow = Admin_Settings::instance()->get_booking_flow();
		}

		ob_start();
		$forwp_booking_provider = $provider;
		$forwp_booking_uid      = $uid;
		$forwp_booking_flow     = $flow;
		$forwp_booking_copy     = Admin_Settings::instance()->get_copy();
		include FORWP_BOOKING_PATH . 'src/views/templates/advanced.php';

		return (string) ob_get_clean();
	}
}
