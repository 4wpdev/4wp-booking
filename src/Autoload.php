<?php
/**
 * Minimal PSR-4 autoloader fallback when Composer vendor is absent.
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking;

defined( 'ABSPATH' ) || exit;

/**
 * Registers a simple autoloader for this plugin namespace.
 */
final class Autoload {

	/**
	 * Register spl autoload.
	 *
	 * @return void
	 */
	public static function register(): void {
		spl_autoload_register(
			static function ( string $class_name ): void {
				$prefix = __NAMESPACE__ . '\\';
				if ( 0 !== strncmp( $prefix, $class_name, strlen( $prefix ) ) ) {
					return;
				}
				$relative = substr( $class_name, strlen( $prefix ) );
				$file     = FORWP_BOOKING_PATH . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
				if ( is_readable( $file ) ) {
					require_once $file;
				}
			}
		);
	}
}
