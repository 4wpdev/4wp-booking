<?php
/**
 * Roadmap messenger channel stub.
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking\Channels;

use ForWP\Booking\Contracts\Channel_Provider_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Registered for architecture; no outbound send.
 */
final class Placeholder_Channel implements Channel_Provider_Interface {

	/**
	 * Channel slug.
	 *
	 * @var string
	 */
	private $slug;

	/**
	 * Channel label.
	 *
	 * @var string
	 */
	private $label;

	/**
	 * Constructor.
	 *
	 * @param string $slug  Stable slug.
	 * @param string $label Admin label.
	 */
	public function __construct( string $slug, string $label ) {
		$this->slug  = $slug;
		$this->label = $label;
	}

	/**
	 * Channel slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return $this->slug;
	}

	/**
	 * Admin label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return $this->label;
	}

	/**
	 * Not implemented.
	 *
	 * @return bool
	 */
	public function is_implemented(): bool {
		return false;
	}

	/**
	 * Never ready.
	 *
	 * @return bool
	 */
	public function is_ready(): bool {
		return false;
	}

	/**
	 * Stub send.
	 *
	 * @param string $text Message.
	 * @return \WP_Error
	 */
	public function send( string $text ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return new \WP_Error(
			'forwp_booking_channel_stub',
			__( 'This channel is not implemented yet.', '4wp-booking' )
		);
	}
}
