<?php
/**
 * Plugin Name:       4WP Booking
 * Plugin URI:        https://4wp.dev/plugin/4wp-booking/
 * Description:       Appointment booking with pluggable providers. ClinicCards is live; Google Calendar and Calendly are on the roadmap.
 * Version:           0.4.0
 * Requires at least: 6.4
 * Tested up to:      7.1
 * Requires PHP:      7.4
 * Author:            4wpdev
 * Author URI:        https://4wp.dev/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       4wp-booking
 * Domain Path:       /languages
 *
 * @package ForWP\Booking
 */

defined( 'ABSPATH' ) || exit;

define( 'FORWP_BOOKING_VERSION', '0.4.0' );
define( 'FORWP_BOOKING_FILE', __FILE__ );
define( 'FORWP_BOOKING_PATH', plugin_dir_path( __FILE__ ) );
define( 'FORWP_BOOKING_URL', plugin_dir_url( __FILE__ ) );

if ( file_exists( FORWP_BOOKING_PATH . 'vendor/autoload.php' ) ) {
	require_once FORWP_BOOKING_PATH . 'vendor/autoload.php';
} else {
	require_once FORWP_BOOKING_PATH . 'src/Autoload.php';
	ForWP\Booking\Autoload::register();
}

ForWP\Booking\Plugin::instance()->boot();
