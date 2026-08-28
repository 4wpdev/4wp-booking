<?php
/**
 * Editor script dependencies for the booking block.
 *
 * @package ForWP\Booking
 */

defined( 'ABSPATH' ) || exit;

return array(
	'dependencies' => array(
		'wp-blocks',
		'wp-element',
		'wp-block-editor',
		'wp-i18n',
		'wp-server-side-render',
	),
	'version'      => FORWP_BOOKING_VERSION,
);
