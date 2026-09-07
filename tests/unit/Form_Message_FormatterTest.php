<?php
/**
 * Form message formatter tests.
 *
 * @package ForWP\Booking
 */

declare( strict_types=1 );

namespace ForWP\Booking\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use ForWP\Booking\Forms\Form_Message_Formatter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ForWP\Booking\Forms\Form_Message_Formatter
 */
class Form_Message_FormatterTest extends TestCase {

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'get_bloginfo' )->justReturn( 'Test Site' );
		Functions\when( 'wp_specialchars_decode' )->returnArg( 1 );
		Functions\when( 'wp_strip_all_tags' )->alias(
			static function ( $value ) {
				return strip_tags( (string) $value );
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
	public function test_format_includes_fields_and_skips_empty(): void {
		$text = Form_Message_Formatter::format(
			'Contact',
			array(
				array( 'label' => 'Name', 'value' => 'Ivan' ),
				array( 'label' => 'Empty', 'value' => '' ),
				array( 'label' => 'Phone', 'value' => array( '+380', '11' ) ),
			),
			'Contact Form 7'
		);

		$this->assertStringContainsString( 'New form submission', $text );
		$this->assertStringContainsString( 'Source: Contact Form 7', $text );
		$this->assertStringContainsString( 'Form: Contact', $text );
		$this->assertStringContainsString( 'Name: Ivan', $text );
		$this->assertStringContainsString( 'Phone: +380, 11', $text );
		$this->assertStringNotContainsString( 'Empty:', $text );
	}

	/**
	 * @return void
	 */
	public function test_format_empty_without_values(): void {
		$this->assertSame(
			'',
			Form_Message_Formatter::format( 'X', array( array( 'label' => 'A', 'value' => '' ) ) )
		);
	}

	/**
	 * @return void
	 */
	public function test_technical_fields_detected(): void {
		$this->assertTrue( Form_Message_Formatter::is_technical_field( '_wpcf7_version' ) );
		$this->assertTrue( Form_Message_Formatter::is_technical_field( 'g-recaptcha-response' ) );
		$this->assertFalse( Form_Message_Formatter::is_technical_field( 'your-name' ) );
	}
}
