<?php
/**
 * Outbound booking notices (email + messenger channels).
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking;

defined( 'ABSPATH' ) || exit;

/**
 * Fire-and-forget after a successful booking. Failures must not block the guest.
 */
final class Notifier {

	/**
	 * Send email copies and ready messenger channels.
	 *
	 * @param array<string, mixed> $booking Booking payload.
	 * @return void
	 */
	public static function dispatch( array $booking ): void {
		$text = self::format_text( $booking );
		if ( '' === $text ) {
			return;
		}

		try {
			self::send_email( $booking, $text );
		} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			unset( $e );
		}

		foreach ( Channel_Registry::all() as $channel ) {
			if ( ! $channel->is_implemented() || ! $channel->is_ready() ) {
				continue;
			}
			try {
				$channel->send( $text );
			} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				unset( $e );
			}
		}
	}

	/**
	 * Plain-text body including date/time.
	 *
	 * @param array<string, mixed> $booking Payload.
	 * @return string
	 */
	public static function format_text( array $booking ): string {
		$date = isset( $booking['date'] ) ? (string) $booking['date'] : '';
		$time = isset( $booking['time'] ) ? (string) $booking['time'] : '';
		if ( '' === $date || '' === $time ) {
			return '';
		}

		$lines   = array();
		$lines[] = __( 'New booking', '4wp-booking' );
		$lines[] = '';
		$doctor  = isset( $booking['doctor'] ) ? (string) $booking['doctor'] : '';
		if ( '' !== $doctor ) {
			$lines[] = sprintf(
				/* translators: %s: doctor name */
				__( 'Doctor: %s', '4wp-booking' ),
				$doctor
			);
		}
		$lines[] = sprintf(
			/* translators: %s: YYYY-MM-DD */
			__( 'Date: %s', '4wp-booking' ),
			$date
		);
		$lines[] = sprintf(
			/* translators: %s: HH:MM */
			__( 'Time: %s', '4wp-booking' ),
			$time
		);
		$name = trim(
			( isset( $booking['first_name'] ) ? (string) $booking['first_name'] : '' ) . ' ' .
			( isset( $booking['last_name'] ) ? (string) $booking['last_name'] : '' )
		);
		if ( '' !== $name ) {
			$lines[] = sprintf(
				/* translators: %s: guest name */
				__( 'Guest: %s', '4wp-booking' ),
				$name
			);
		}
		$phone = isset( $booking['phone'] ) ? (string) $booking['phone'] : '';
		if ( '' !== $phone ) {
			$lines[] = sprintf(
				/* translators: %s: phone */
				__( 'Phone: %s', '4wp-booking' ),
				$phone
			);
		}
		$email = isset( $booking['email'] ) ? (string) $booking['email'] : '';
		if ( '' !== $email ) {
			$lines[] = sprintf(
				/* translators: %s: email */
				__( 'Email: %s', '4wp-booking' ),
				$email
			);
		}
		$comment = isset( $booking['comment'] ) ? (string) $booking['comment'] : '';
		if ( '' !== $comment ) {
			$lines[] = sprintf(
				/* translators: %s: comment */
				__( 'Comment: %s', '4wp-booking' ),
				$comment
			);
		}

		return implode( "\n", $lines );
	}

	/**
	 * Send email via wp_mail to configured addresses (admin email if empty).
	 *
	 * @param array<string, mixed> $booking Payload.
	 * @param string               $text    Body.
	 * @return void
	 */
	private static function send_email( array $booking, string $text ): void {
		$to = Admin_Settings::instance()->get_notify_emails();
		if ( array() === $to ) {
			return;
		}

		$date = isset( $booking['date'] ) ? (string) $booking['date'] : '';
		$time = isset( $booking['time'] ) ? (string) $booking['time'] : '';
		$subj = sprintf(
			/* translators: 1: date, 2: time */
			__( 'New booking — %1$s %2$s', '4wp-booking' ),
			$date,
			$time
		);

		wp_mail( $to, $subj, $text );
	}
}
