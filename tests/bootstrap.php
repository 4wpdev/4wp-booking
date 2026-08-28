<?php
/**
 * PHPUnit bootstrap for 4wp-booking.
 *
 * @package ForWP\Booking
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

$autoload = dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! is_readable( $autoload ) ) {
	fwrite( STDERR, "Run: composer install (requires 4wp-dev-toolkit path repo).\n" );
	exit( 1 );
}

require_once $autoload;
