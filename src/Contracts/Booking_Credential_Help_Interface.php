<?php
/**
 * Optional admin hints for obtaining an upstream API key.
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Implemented by providers that require dashboard signup documentation links.
 */
interface Booking_Credential_Help_Interface {

	/**
	 * Short paragraph shown under the API key field.
	 *
	 * @return string
	 */
	public function get_api_key_help_intro(): string;

	/**
	 * HTTPS URL for creating or managing API keys.
	 *
	 * @return string
	 */
	public function get_api_key_docs_url(): string;

	/**
	 * Accessible label for the external documentation link.
	 *
	 * @return string
	 */
	public function get_api_key_docs_link_label(): string;
}
