<?php
/**
 * @package ForWP\Booking
 */

declare( strict_types=1 );

namespace ForWP\Booking\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use ForWP\Booking\Template_Registry;
use ForWP\Booking\Templates\Advanced_Template;
use ForWP\Booking\Templates\Calendar_Template;
use PHPUnit\Framework\TestCase;

/**
 * Template registry unit tests.
 */
class Template_RegistryTest extends TestCase {

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
		Template_Registry::reset();
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		Template_Registry::reset();
		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function test_calendar_is_registered(): void {
		$template = Template_Registry::get( Calendar_Template::SLUG );
		$this->assertNotNull( $template );
		$this->assertSame( Calendar_Template::SLUG, $template->get_slug() );
	}

	/**
	 * @return void
	 */
	public function test_advanced_is_registered(): void {
		$template = Template_Registry::get( Advanced_Template::SLUG );
		$this->assertNotNull( $template );
		$this->assertSame( Advanced_Template::SLUG, $template->get_slug() );
		$this->assertTrue( Template_Registry::has( Advanced_Template::SLUG ) );
	}

	/**
	 * @return void
	 */
	public function test_unknown_slug_falls_back_to_calendar(): void {
		$template = Template_Registry::get( 'missing' );
		$this->assertNotNull( $template );
		$this->assertSame( Calendar_Template::SLUG, $template->get_slug() );
	}
}
