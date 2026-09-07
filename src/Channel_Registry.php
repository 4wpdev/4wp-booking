<?php
/**
 * Messenger channel registry.
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking;

use ForWP\Booking\Channels\Placeholder_Channel;
use ForWP\Booking\Channels\Telegram_Channel;
use ForWP\Booking\Contracts\Channel_Provider_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Telegram is live; WhatsApp, Viber, Other are stubs.
 */
final class Channel_Registry {

	/**
	 * Registered channels keyed by slug.
	 *
	 * @var array<string, Channel_Provider_Interface>|null
	 */
	private static $channels = null;

	/**
	 * All channels keyed by slug.
	 *
	 * @return array<string, Channel_Provider_Interface>
	 */
	public static function all(): array {
		if ( null !== self::$channels ) {
			return self::$channels;
		}

		$map = array(
			Telegram_Channel::SLUG => new Telegram_Channel(),
			'whatsapp'             => new Placeholder_Channel( 'whatsapp', __( 'WhatsApp', '4wp-booking' ) ),
			'viber'                => new Placeholder_Channel( 'viber', __( 'Viber', '4wp-booking' ) ),
			'other'                => new Placeholder_Channel( 'other', __( 'Other', '4wp-booking' ) ),
		);

		/**
		 * Filter messenger channels.
		 *
		 * @param array<string, Channel_Provider_Interface> $map Channels.
		 */
		$filtered       = apply_filters( 'forwp_booking_channels', $map );
		self::$channels = is_array( $filtered ) ? $filtered : $map;

		return self::$channels;
	}

	/**
	 * Resolve by slug.
	 *
	 * @param string $slug Channel slug.
	 * @return Channel_Provider_Interface|null
	 */
	public static function get( string $slug ) {
		$slug = sanitize_key( $slug );
		$all  = self::all();

		return isset( $all[ $slug ] ) ? $all[ $slug ] : null;
	}

	/**
	 * Settings rows.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_admin_status_rows(): array {
		$rows = array();
		foreach ( self::all() as $channel ) {
			if ( $channel->is_implemented() ) {
				$status = $channel->is_ready()
					? __( 'Ready', '4wp-booking' )
					: __( 'Needs credentials', '4wp-booking' );
			} else {
				$status = __( 'Planned (stub)', '4wp-booking' );
			}

			$rows[] = array(
				'slug'        => $channel->get_slug(),
				'label'       => $channel->get_label(),
				'status'      => $status,
				'implemented' => $channel->is_implemented(),
				'ready'       => $channel->is_ready(),
			);
		}

		return $rows;
	}

	/**
	 * Reset cache (tests).
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$channels = null;
	}
}
