<?php
/**
 * Front-end booking form template contract.
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * One template = one booking UX (calendar, list, …).
 */
interface Booking_Template_Interface {

	/**
	 * Stable slug (shortcode / block attribute).
	 *
	 * @return string
	 */
	public function get_slug(): string;

	/**
	 * Human-readable name.
	 *
	 * @return string
	 */
	public function get_label(): string;

	/**
	 * Short admin description.
	 *
	 * @return string
	 */
	public function get_description(): string;

	/**
	 * Enqueue front-end assets for this template.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void;

	/**
	 * Render markup.
	 *
	 * @param array<string, mixed> $context Provider slug, instance id, etc.
	 * @return string
	 */
	public function render( array $context ): string;
}
