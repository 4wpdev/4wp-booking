<?php
/**
 * @package ForWP\Booking
 */

declare( strict_types=1 );

namespace ForWP\Booking\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use ForWP\Booking\Admin_Settings;
use PHPUnit\Framework\TestCase;

/**
 * Booking flow and copy defaults.
 */
class Copy_Flow_Test extends TestCase {

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'sanitize_key' )->alias(
			static function ( $key ) {
				return strtolower( (string) preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) );
			}
		);
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function test_sanitize_booking_flow_accepts_staff_and_date(): void {
		$this->assertSame( 'staff', Admin_Settings::sanitize_booking_flow( 'staff' ) );
		$this->assertSame( 'date', Admin_Settings::sanitize_booking_flow( 'date' ) );
		$this->assertSame( 'service', Admin_Settings::sanitize_booking_flow( 'service' ) );
		$this->assertSame( '', Admin_Settings::sanitize_booking_flow( 'wizard' ) );
	}

	/**
	 * @return void
	 */
	public function test_default_copy_includes_heading_and_description(): void {
		$copy = Admin_Settings::default_copy();
		$this->assertArrayHasKey( 'tab_service', $copy );
		$this->assertArrayHasKey( 'tab_date', $copy );
		$this->assertArrayHasKey( 'tab_staff', $copy );
		$this->assertArrayHasKey( 'select_service', $copy );
		$this->assertArrayHasKey( 'no_services', $copy );
		$this->assertArrayHasKey( 'error_required', $copy );
		$this->assertArrayHasKey( 'error_phone', $copy );
		$this->assertSame( 'Select a doctor', $copy['offerings_title'] );
		$this->assertSame( 'By service', $copy['tab_service'] );
		$this->assertSame( 'Select a service', $copy['select_service'] );
	}
}
