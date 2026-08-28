<?php
/**
 * Roadmap provider stub (no HTTP).
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking\Providers;

use ForWP\Booking\Contracts\Booking_Provider_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Registered for architecture; not selectable for live booking.
 */
final class Placeholder_Provider implements Booking_Provider_Interface {

	/**
	 * Stable slug.
	 *
	 * @var string
	 */
	private $slug;

	/**
	 * Admin label.
	 *
	 * @var string
	 */
	private $label;

	/**
	 * Store slug and label.
	 *
	 * @param string $slug  Stable slug.
	 * @param string $label Admin label.
	 */
	public function __construct( string $slug, string $label ) {
		$this->slug  = $slug;
		$this->label = $label;
	}

	/**
	 * Provider slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return $this->slug;
	}

	/**
	 * Human-readable provider name.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return $this->label;
	}

	/**
	 * Whether this provider is implemented.
	 *
	 * @return bool
	 */
	public function is_implemented(): bool {
		return false;
	}

	/**
	 * Whether credentials are configured.
	 *
	 * @return bool
	 */
	public function is_ready(): bool {
		return false;
	}

	/**
	 * Offerings (stub).
	 *
	 * @return \WP_Error
	 */
	public function get_offerings() {
		return new \WP_Error(
			'forwp_booking_provider_not_implemented',
			__( 'This booking provider is not implemented yet.', '4wp-booking' )
		);
	}

	/**
	 * Slots (stub).
	 *
	 * @param string $offering_id Offering id.
	 * @param string $from        Start date.
	 * @param string $to          End date.
	 * @return \WP_Error
	 */
	public function get_slots( string $offering_id, string $from, string $to ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return $this->get_offerings();
	}

	/**
	 * Create booking (stub).
	 *
	 * @param array<string, mixed> $input Payload.
	 * @return \WP_Error
	 */
	public function create_booking( array $input ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return $this->get_offerings();
	}
}
