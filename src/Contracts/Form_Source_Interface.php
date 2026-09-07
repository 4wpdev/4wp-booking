<?php
/**
 * Form submission source contract (CF7, WPForms, Gravity, …).
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * One pluggable form plugin bridge that can forward submissions to Telegram.
 */
interface Form_Source_Interface {

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
	 * Whether this source is fully implemented.
	 *
	 * @return bool
	 */
	public function is_implemented(): bool;

	/**
	 * Whether the form plugin is active on this site.
	 *
	 * @return bool
	 */
	public function is_plugin_active(): bool;

	/**
	 * Register submission hooks when enabled.
	 *
	 * @return void
	 */
	public function boot(): void;
}
