<?php
/**
 * Shared front-end calendar assets.
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue CSS/JS once per request.
 */
final class Front_Assets {

	/**
	 * Calendar shell style + picker script.
	 *
	 * @return void
	 */
	public static function enqueue(): void {
		if ( wp_script_is( 'forwp-booking-calendar', 'enqueued' ) ) {
			return;
		}

		$css = FORWP_BOOKING_PATH . 'assets/css/calendar.css';
		$js  = FORWP_BOOKING_PATH . 'assets/js/calendar.js';
		wp_enqueue_style(
			'forwp-booking-calendar',
			FORWP_BOOKING_URL . 'assets/css/calendar.css',
			array(),
			(string) ( file_exists( $css ) ? filemtime( $css ) : FORWP_BOOKING_VERSION )
		);
		wp_enqueue_script(
			'forwp-booking-calendar',
			FORWP_BOOKING_URL . 'assets/js/calendar.js',
			array(),
			(string) ( file_exists( $js ) ? filemtime( $js ) : FORWP_BOOKING_VERSION ),
			true
		);
		$copy = Admin_Settings::instance()->get_copy();
		$inline = Appearance::inline_css();
		if ( '' !== $inline ) {
			wp_add_inline_style( 'forwp-booking-calendar', $inline );
		}

		$locale = Plugin::resolve_locale();
		wp_localize_script(
			'forwp-booking-calendar',
			'forwpBooking',
			array(
				'restUrl' => rest_url( 'forwp-booking/v1/' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'locale'  => str_replace( '_', '-', $locale ),
				'strings' => array(
					'loading'       => __( 'Loading…', '4wp-booking' ),
					'error'         => __( 'Something went wrong. Please try again.', '4wp-booking' ),
					'noOfferings'   => $copy['no_offerings'],
					'noServices'    => isset( $copy['no_services'] ) ? $copy['no_services'] : '',
					'noSlots'       => $copy['no_slots'],
					'noMonthSlots'  => $copy['no_month_slots'],
					'selectService' => isset( $copy['select_service'] ) ? $copy['select_service'] : '',
					'selectDoctor'  => $copy['offerings_title'],
					'selectDate'    => $copy['select_date'],
					'selectTime'    => $copy['select_time'],
					'success'       => $copy['success'],
					'errorRequired' => isset( $copy['error_required'] ) ? $copy['error_required'] : '',
					'errorName'     => isset( $copy['error_name'] ) ? $copy['error_name'] : '',
					'errorPhone'    => isset( $copy['error_phone'] ) ? $copy['error_phone'] : '',
					'errorEmail'    => isset( $copy['error_email'] ) ? $copy['error_email'] : '',
					'weekdays'      => array(
						__( 'Mon', '4wp-booking' ),
						__( 'Tue', '4wp-booking' ),
						__( 'Wed', '4wp-booking' ),
						__( 'Thu', '4wp-booking' ),
						__( 'Fri', '4wp-booking' ),
						__( 'Sat', '4wp-booking' ),
						__( 'Sun', '4wp-booking' ),
					),
				),
			)
		);
	}
}
