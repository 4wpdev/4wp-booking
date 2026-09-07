<?php
/**
 * Form Telegram settings tests.
 *
 * @package ForWP\Booking
 */

declare( strict_types=1 );

namespace ForWP\Booking\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use ForWP\Booking\Admin_Settings;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ForWP\Booking\Admin_Settings
 */
class Form_Telegram_SettingsTest extends TestCase {

	/** @var array<string, mixed> */
	private $options = array();

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		$this->options = array();

		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'sanitize_key' )->alias(
			static function ( $key ) {
				return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) );
			}
		);
		Functions\when( 'sanitize_text_field' )->alias(
			static function ( $value ) {
				return trim( (string) $value );
			}
		);
		Functions\when( 'get_option' )->alias(
			function ( $key, $default = false ) {
				if ( Admin_Settings::OPTION_KEY === $key ) {
					return $this->options;
				}

				return $default;
			}
		);
		Functions\when( 'update_option' )->alias(
			function ( $key, $value ) {
				if ( Admin_Settings::OPTION_KEY === $key ) {
					$this->options = is_array( $value ) ? $value : array();
				}

				return true;
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
	public function test_form_chat_ids_fall_back_to_booking_chats(): void {
		$settings = Admin_Settings::instance();
		$settings->set_channel_targets( 'telegram', '-100111, -100222' );
		$settings->set_form_telegram_settings(
			array(
				'enabled'  => true,
				'chat_ids' => '',
				'sources'  => array(),
			)
		);

		$this->assertSame( array( '-100111', '-100222' ), $settings->get_form_telegram_chat_ids() );
	}

	/**
	 * @return void
	 */
	public function test_form_chat_ids_override_booking_chats(): void {
		$settings = Admin_Settings::instance();
		$settings->set_channel_targets( 'telegram', '-100111' );
		$settings->set_form_telegram_settings(
			array(
				'enabled'  => true,
				'chat_ids' => '-100999',
				'sources'  => array(),
			)
		);

		$this->assertSame( array( '-100999' ), $settings->get_form_telegram_chat_ids() );
	}

	/**
	 * @return void
	 */
	public function test_exclude_form_ids(): void {
		$settings = Admin_Settings::instance();
		$settings->set_form_telegram_settings(
			array(
				'enabled' => true,
				'sources' => array(
					'contact-form-7' => array(
						'enabled'     => true,
						'exclude_ids' => '12, 34',
					),
				),
			)
		);

		$this->assertTrue( $settings->is_form_excluded( 'contact-form-7', 12 ) );
		$this->assertFalse( $settings->is_form_excluded( 'contact-form-7', 99 ) );
	}
}
