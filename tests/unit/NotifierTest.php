<?php
/**
 * @package ForWP\Booking
 */

declare( strict_types=1 );

namespace ForWP\Booking\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use ForWP\Booking\Admin_Settings;
use ForWP\Booking\Notifier;
use PHPUnit\Framework\TestCase;

/**
 * Booking notice copy and email list parsing.
 */
class NotifierTest extends TestCase {

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'sanitize_email' )->alias(
			static function ( $email ) {
				return strtolower( trim( (string) $email ) );
			}
		);
		Functions\when( 'is_email' )->alias(
			static function ( $email ) {
				return (bool) filter_var( (string) $email, FILTER_VALIDATE_EMAIL );
			}
		);
		Functions\when( 'sanitize_text_field' )->alias(
			static function ( $value ) {
				return trim( (string) $value );
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
	public function test_format_text_includes_date_and_time(): void {
		$text = Notifier::format_text(
			array(
				'doctor'     => 'Dr Test',
				'date'       => '2026-08-27',
				'time'       => '10:00',
				'first_name' => 'Ivan',
				'last_name'  => 'Petrenko',
				'phone'      => '+380000000000',
				'email'      => 'guest@example.com',
			)
		);

		$this->assertStringContainsString( 'New booking', $text );
		$this->assertStringContainsString( 'Date: 2026-08-27', $text );
		$this->assertStringContainsString( 'Time: 10:00', $text );
		$this->assertStringContainsString( 'Doctor: Dr Test', $text );
		$this->assertStringContainsString( 'Guest: Ivan Petrenko', $text );
	}

	/**
	 * @return void
	 */
	public function test_format_text_empty_without_date_or_time(): void {
		$this->assertSame( '', Notifier::format_text( array( 'date' => '2026-08-27' ) ) );
		$this->assertSame( '', Notifier::format_text( array( 'time' => '10:00' ) ) );
	}

	/**
	 * @return void
	 */
	public function test_parse_email_list_falls_back_when_empty(): void {
		$this->assertSame(
			array( 'admin@example.com' ),
			Admin_Settings::parse_email_list( '', 'admin@example.com' )
		);
	}

	/**
	 * @return void
	 */
	public function test_parse_email_list_accepts_comma_separated(): void {
		$this->assertSame(
			array( 'a@example.com', 'b@example.com' ),
			Admin_Settings::parse_email_list( 'a@example.com, b@example.com', 'admin@example.com' )
		);
	}

	/**
	 * @return void
	 */
	public function test_parse_id_list_splits_commas(): void {
		$this->assertSame(
			array( '111', '-100222' ),
			Admin_Settings::parse_id_list( '111, -100222' )
		);
	}
}
