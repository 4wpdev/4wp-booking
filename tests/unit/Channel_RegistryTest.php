<?php
/**
 * @package ForWP\Booking
 */

declare( strict_types=1 );

namespace ForWP\Booking\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use ForWP\Booking\Channel_Registry;
use ForWP\Booking\Channels\Telegram_Channel;
use PHPUnit\Framework\TestCase;

/**
 * Messenger channel registry.
 */
class Channel_RegistryTest extends TestCase {

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
		Channel_Registry::reset();
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		Channel_Registry::reset();
		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function test_telegram_is_implemented(): void {
		$channel = Channel_Registry::get( Telegram_Channel::SLUG );
		$this->assertNotNull( $channel );
		$this->assertSame( Telegram_Channel::SLUG, $channel->get_slug() );
		$this->assertTrue( $channel->is_implemented() );
	}

	/**
	 * @return void
	 */
	public function test_whatsapp_viber_other_are_stubs(): void {
		foreach ( array( 'whatsapp', 'viber', 'other' ) as $slug ) {
			$channel = Channel_Registry::get( $slug );
			$this->assertNotNull( $channel );
			$this->assertFalse( $channel->is_implemented() );
			$this->assertFalse( $channel->is_ready() );
		}
	}

	/**
	 * @return void
	 */
	public function test_get_returns_null_for_unknown_slug(): void {
		$this->assertNull( Channel_Registry::get( 'not-a-real-channel' ) );
	}
}
