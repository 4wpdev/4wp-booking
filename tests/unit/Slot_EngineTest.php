<?php
/**
 * @package ForWP\Booking
 */

declare( strict_types=1 );

namespace ForWP\Booking\Tests\Unit;

use ForWP\Booking\Slot_Engine;
use PHPUnit\Framework\TestCase;

/**
 * Slot engine tests (no HTTP).
 */
class Slot_EngineTest extends TestCase {

	/**
	 * @return void
	 */
	public function test_generates_half_hour_slots(): void {
		$windows = array(
			array(
				'start' => 1000,
				'end'   => 1000 + ( 2 * 3600 ),
			),
		);
		$slots   = Slot_Engine::generate( $windows, array(), 30, 30, 0 );
		$this->assertCount( 4, $slots );
		$this->assertSame( 1000, $slots[0]['start'] );
		$this->assertSame( 1000 + 1800, $slots[0]['end'] );
	}

	/**
	 * @return void
	 */
	public function test_skips_busy_overlap(): void {
		$windows = array(
			array(
				'start' => 0,
				'end'   => 3600,
			),
		);
		$busy    = array(
			array(
				'start' => 1800,
				'end'   => 3600,
			),
		);
		$slots   = Slot_Engine::generate( $windows, $busy, 30, 30, 0 );
		$this->assertCount( 1, $slots );
		$this->assertSame( 0, $slots[0]['start'] );
	}

	/**
	 * @return void
	 */
	public function test_ignores_past_slots(): void {
		$windows = array(
			array(
				'start' => 0,
				'end'   => 3600,
			),
		);
		$slots   = Slot_Engine::generate( $windows, array(), 30, 30, 2000 );
		$this->assertCount( 1, $slots );
		$this->assertSame( 1800, $slots[0]['start'] );
	}
}
