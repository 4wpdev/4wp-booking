<?php
/**
 * Messenger channel contract.
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * One channel = one outbound messenger (Telegram now; others as stubs).
 */
interface Channel_Provider_Interface {

	/**
	 * Stable slug.
	 *
	 * @return string
	 */
	public function get_slug(): string;

	/**
	 * Admin label.
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
	 * True when credentials are saved.
	 *
	 * @return bool
	 */
	public function is_ready(): bool;

	/**
	 * Send a plain-text booking notice.
	 *
	 * @param string $text Message body.
	 * @return true|\WP_Error
	 */
	public function send( string $text );
}
