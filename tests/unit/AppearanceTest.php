<?php
/**
 * @package ForWP\Booking
 */

declare( strict_types=1 );

namespace ForWP\Booking\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use ForWP\Booking\Appearance;
use PHPUnit\Framework\TestCase;

/**
 * Appearance sanitization and CSS variables.
 */
class AppearanceTest extends TestCase {

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'sanitize_key' )->alias(
			static function ( $key ) {
				return strtolower( (string) preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) );
			}
		);
		Functions\when( 'sanitize_hex_color' )->alias(
			static function ( $color ) {
				$color = (string) $color;
				if ( preg_match( '/^#([0-9a-fA-F]{6})$/', $color, $m ) ) {
					return '#' . strtolower( $m[1] );
				}

				return '';
			}
		);
		Appearance::reset();
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		Appearance::reset();
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function test_sanitize_keeps_valid_hex_and_radius(): void {
		$clean = Appearance::sanitize(
			array(
				'primary_color'  => '#6EC1E4',
				'border_radius'  => '12',
				'box_shadow'     => 'medium',
				'border_width'   => '99',
				'font_family'    => 'Roboto',
				'bg_color'       => 'not-a-color',
			)
		);
		$this->assertSame( '#6ec1e4', $clean['primary_color'] );
		$this->assertSame( '12', $clean['border_radius'] );
		$this->assertSame( 'medium', $clean['box_shadow'] );
		$this->assertSame( '8', $clean['border_width'] );
		$this->assertSame( 'Roboto', $clean['font_family'] );
		$this->assertSame( '', $clean['bg_color'] );
	}

	/**
	 * @return void
	 */
	public function test_css_variables_include_radius_and_primary(): void {
		$vars = Appearance::css_variables(
			array(
				'primary_color'  => '#6ec1e4',
				'bg_color'       => '#ffffff',
				'text_color'     => '#111827',
				'border_color'   => '#e5e7eb',
				'border_width'   => '2',
				'border_radius'  => '16',
				'box_shadow'     => 'none',
				'font_family'    => 'Roboto',
			)
		);
		$this->assertSame( '#6ec1e4', $vars['--forwp-booking-accent'] );
		$this->assertSame( '16px', $vars['--forwp-booking-radius'] );
		$this->assertSame( '2px', $vars['--forwp-booking-border-width'] );
		$this->assertSame( 'none', $vars['--forwp-booking-shadow'] );
		$this->assertStringContainsString( 'Roboto', $vars['--forwp-booking-font'] );
	}

	/**
	 * @return void
	 */
	public function test_plugin_fallbacks_include_required_keys(): void {
		$fallback = Appearance::plugin_fallbacks();
		foreach ( Appearance::KEYS as $key ) {
			$this->assertArrayHasKey( $key, $fallback );
		}
	}
}
