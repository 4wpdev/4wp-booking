<?php
/**
 * @package ForWP\Booking
 */

declare( strict_types=1 );

namespace ForWP\Booking\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use ForWP\Booking\Provider_Registry;
use ForWP\Booking\Providers\ClinicCards_Provider;
use PHPUnit\Framework\TestCase;

/**
 * Provider registry unit tests.
 */
class Provider_RegistryTest extends TestCase {

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'apply_filters' )->returnArg( 1 );
		Functions\when( 'sanitize_key' )->alias(
			static function ( $key ) {
				return strtolower( (string) preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) );
			}
		);
		Provider_Registry::reset();
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		Provider_Registry::reset();
		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function test_get_returns_cliniccards_provider(): void {
		$provider = Provider_Registry::get( ClinicCards_Provider::SLUG );
		$this->assertNotNull( $provider );
		$this->assertSame( ClinicCards_Provider::SLUG, $provider->get_slug() );
		$this->assertTrue( $provider->is_implemented() );
	}

	/**
	 * @return void
	 */
	public function test_get_returns_null_for_unknown_slug(): void {
		$this->assertNull( Provider_Registry::get( 'not-a-real-provider' ) );
	}

	/**
	 * @return void
	 */
	public function test_implemented_slugs_includes_cliniccards(): void {
		$this->assertContains( ClinicCards_Provider::SLUG, Provider_Registry::implemented_slugs() );
	}

	/**
	 * @return void
	 */
	public function test_google_calendar_is_stub(): void {
		$provider = Provider_Registry::get( 'google_calendar' );
		$this->assertNotNull( $provider );
		$this->assertFalse( $provider->is_implemented() );
	}
}
