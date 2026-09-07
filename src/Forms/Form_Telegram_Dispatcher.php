<?php
/**
 * Dispatches form submissions to Telegram.
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking\Forms;

use ForWP\Booking\Admin_Settings;
use ForWP\Booking\Channel_Registry;
use ForWP\Booking\Channels\Telegram_Channel;

defined( 'ABSPATH' ) || exit;

/**
 * Fire-and-forget form → Telegram. Failures must not block the form plugin.
 */
final class Form_Telegram_Dispatcher {

	/**
	 * Send a formatted submission when form Telegram is enabled.
	 *
	 * @param string                                                      $source_slug Source slug.
	 * @param string                                                      $form_title  Form title.
	 * @param array<int, array{label?:string,value?:string,name?:string}> $fields Fields.
	 * @param int                                                         $form_id     Form id (for exclude lists).
	 * @return void
	 */
	public static function dispatch( string $source_slug, string $form_title, array $fields, int $form_id = 0 ): void {
		$settings = Admin_Settings::instance();
		if ( ! $settings->is_form_telegram_enabled() ) {
			return;
		}
		if ( ! $settings->is_form_source_enabled( $source_slug ) ) {
			return;
		}
		if ( $form_id > 0 && $settings->is_form_excluded( $source_slug, $form_id ) ) {
			return;
		}

		$source = Form_Source_Registry::get( $source_slug );
		$label  = $source ? $source->get_label() : $source_slug;
		$text   = Form_Message_Formatter::format( $form_title, $fields, $label );
		if ( '' === $text ) {
			return;
		}

		$channel = Channel_Registry::get( Telegram_Channel::SLUG );
		if ( ! $channel instanceof Telegram_Channel || ! $channel->is_ready_for_forms() ) {
			return;
		}

		try {
			$channel->send_to_chats( $text, $settings->get_form_telegram_chat_ids() );
		} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			unset( $e );
		}
	}
}
