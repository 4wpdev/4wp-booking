<?php
/**
 * Booking provider contract.
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * One provider = one upstream CRM/calendar + normalization to the plugin payload.
 */
interface Booking_Provider_Interface {

	/**
	 * Stable slug (settings, shortcode, block attribute).
	 *
	 * @return string
	 */
	public function get_slug(): string;

	/**
	 * Human-readable name (admin / editor).
	 *
	 * @return string
	 */
	public function get_label(): string;

	/**
	 * False for roadmap stubs.
	 *
	 * @return bool
	 */
	public function is_implemented(): bool;

	/**
	 * True when live requests may run (credentials saved).
	 *
	 * @return bool
	 */
	public function is_ready(): bool;

	/**
	 * Bookable offerings (service + specialist).
	 *
	 * @return array<int, array<string, mixed>>|\WP_Error
	 */
	public function get_offerings();

	/**
	 * Free slots for an offering in a date range (YYYY-MM-DD).
	 *
	 * @param string $offering_id Offering id from get_offerings().
	 * @param string $from        Start date.
	 * @param string $to          End date.
	 * @return array<int, array{date: string, time_start: string, time_end: string}>|\WP_Error
	 */
	public function get_slots( string $offering_id, string $from, string $to );

	/**
	 * Create a booking.
	 *
	 * Expected keys: offering_id, date, time_start, first_name, last_name, phone, email, comment.
	 *
	 * @param array<string, mixed> $input Guest and slot payload.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function create_booking( array $input );
}
