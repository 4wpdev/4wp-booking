<?php
/**
 * Gutenberg block forwp/booking.
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking;

defined( 'ABSPATH' ) || exit;

/**
 * Dynamic booking block.
 */
final class Block {

	public const NAME = 'forwp/booking';

	/**
	 * Register block.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'register_block' ) );
	}

	/**
	 * Register from block.json.
	 *
	 * @return void
	 */
	public static function register_block(): void {
		$dir = FORWP_BOOKING_PATH . 'assets/blocks/booking';
		if ( ! is_readable( $dir . '/block.json' ) ) {
			return;
		}

		register_block_type(
			$dir,
			array(
				'render_callback' => array( self::class, 'render' ),
			)
		);
		wp_set_script_translations(
			'forwp-booking-editor-script',
			'4wp-booking',
			FORWP_BOOKING_PATH . 'languages'
		);
	}

	/**
	 * Server render.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string
	 */
	public static function render( $attributes ): string {
		return Plugin::render(
			array(
				'provider' => isset( $attributes['provider'] ) ? (string) $attributes['provider'] : '',
				'template' => isset( $attributes['template'] ) ? (string) $attributes['template'] : '',
				'flow'     => isset( $attributes['flow'] ) ? (string) $attributes['flow'] : '',
			)
		);
	}
}
