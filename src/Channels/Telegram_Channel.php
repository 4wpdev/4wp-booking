<?php
/**
 * Telegram Bot API channel.
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking\Channels;

use ForWP\Booking\Admin_Settings;
use ForWP\Booking\Contracts\Channel_Provider_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Sends booking notices to one or more chats.
 */
final class Telegram_Channel implements Channel_Provider_Interface {

	public const SLUG = 'telegram';

	/**
	 * Channel slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return self::SLUG;
	}

	/**
	 * Admin label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Telegram', '4wp-booking' );
	}

	/**
	 * Live.
	 *
	 * @return bool
	 */
	public function is_implemented(): bool {
		return true;
	}

	/**
	 * Token + at least one chat id.
	 *
	 * @return bool
	 */
	public function is_ready(): bool {
		$settings = Admin_Settings::instance();

		return '' !== $settings->get_channel_token( self::SLUG )
			&& array() !== $settings->get_channel_targets( self::SLUG );
	}

	/**
	 * POST sendMessage for each chat id.
	 *
	 * @param string $text Message.
	 * @return true|\WP_Error
	 */
	public function send( string $text ) {
		if ( ! $this->is_ready() ) {
			return new \WP_Error(
				'forwp_booking_telegram_not_ready',
				__( 'Telegram is not configured.', '4wp-booking' )
			);
		}

		$settings = Admin_Settings::instance();
		$token    = $settings->get_channel_token( self::SLUG );
		$sent     = 0;
		$last     = null;

		foreach ( $settings->get_channel_targets( self::SLUG ) as $chat_id ) {
			$url      = 'https://api.telegram.org/bot' . $token . '/sendMessage';
			$response = wp_remote_post(
				$url,
				array(
					'timeout' => 15,
					'headers' => array(
						'Content-Type' => 'application/json',
					),
					'body'    => wp_json_encode(
						array(
							'chat_id'                  => $chat_id,
							'text'                     => $text,
							'disable_web_page_preview' => true,
						)
					),
				)
			);
			if ( is_wp_error( $response ) ) {
				$last = $response;
				continue;
			}
			$code = (int) wp_remote_retrieve_response_code( $response );
			if ( $code >= 400 ) {
				$last = new \WP_Error( 'forwp_booking_telegram', __( 'Telegram request failed.', '4wp-booking' ) );
				continue;
			}
			++$sent;
		}

		if ( $sent > 0 ) {
			return true;
		}

		return $last instanceof \WP_Error
			? $last
			: new \WP_Error( 'forwp_booking_telegram', __( 'Telegram request failed.', '4wp-booking' ) );
	}
}
