<?php
/**
 * Split work windows into free slots.
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking;

defined( 'ABSPATH' ) || exit;

/**
 * Provider-agnostic slot math.
 */
final class Slot_Engine {

	/**
	 * Generate start/end unix timestamps that fit in windows and avoid busy ranges.
	 *
	 * @param array<int, array{start: int, end: int}> $windows           Work windows.
	 * @param array<int, array{start: int, end: int}> $busy              Occupied ranges.
	 * @param int                                     $duration_minutes  Slot length.
	 * @param int                                     $step_minutes      Step between starts.
	 * @param int                                     $now               Ignore slots ending before this unix time.
	 * @return array<int, array{start: int, end: int}>
	 */
	public static function generate( array $windows, array $busy, int $duration_minutes, int $step_minutes, int $now = 0 ): array {
		if ( $duration_minutes < 5 || $step_minutes < 5 ) {
			return array();
		}

		$duration = $duration_minutes * MINUTE_IN_SECONDS;
		$step     = $step_minutes * MINUTE_IN_SECONDS;
		$slots    = array();

		foreach ( $windows as $window ) {
			$start = isset( $window['start'] ) ? (int) $window['start'] : 0;
			$end   = isset( $window['end'] ) ? (int) $window['end'] : 0;
			if ( $end <= $start ) {
				continue;
			}

			$cursor = $start;
			while ( ( $cursor + $duration ) <= $end ) {
				$slot_end = $cursor + $duration;
				if ( $slot_end > $now && ! self::overlaps( $cursor, $slot_end, $busy ) ) {
					$slots[] = array(
						'start' => $cursor,
						'end'   => $slot_end,
					);
				}
				$cursor += $step;
			}
		}

		return $slots;
	}

	/**
	 * Whether [start, end) overlaps any busy range.
	 *
	 * @param int                                     $start Start unix.
	 * @param int                                     $end   End unix.
	 * @param array<int, array{start: int, end: int}> $busy  Busy ranges.
	 * @return bool
	 */
	private static function overlaps( int $start, int $end, array $busy ): bool {
		foreach ( $busy as $range ) {
			$busy_start = isset( $range['start'] ) ? (int) $range['start'] : 0;
			$busy_end   = isset( $range['end'] ) ? (int) $range['end'] : 0;
			if ( $start < $busy_end && $end > $busy_start ) {
				return true;
			}
		}

		return false;
	}
}
