<?php
/**
 * Optional Elementor widget.
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the widget only when Elementor is active.
 */
final class Elementor {

	/**
	 * Hook Elementor.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'elementor/widgets/register', array( self::class, 'register_widget' ) );
	}

	/**
	 * Register widget instance.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Manager.
	 * @return void
	 */
	public static function register_widget( $widgets_manager ): void {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}

		require_once FORWP_BOOKING_PATH . 'src/Elementor_Widget.php';
		$widgets_manager->register( new Elementor_Widget() );
	}
}
