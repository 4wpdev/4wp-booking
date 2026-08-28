<?php
/**
 * Shortcode [forwp_booking].
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking;

defined( 'ABSPATH' ) || exit;

/**
 * Front-end shortcode.
 */
final class Shortcode {

	/**
	 * Register shortcode.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( 'forwp_booking', array( self::class, 'render' ) );
	}

	/**
	 * Render shortcode.
	 *
	 * @param array<string, mixed>|string $atts Attributes.
	 * @return string
	 */
	public static function render( $atts ): string {
		$atts = shortcode_atts(
			array(
				'provider' => '',
				'template' => '',
				'flow'     => '',
			),
			is_array( $atts ) ? $atts : array(),
			'forwp_booking'
		);

		return Plugin::render( $atts );
	}
}
