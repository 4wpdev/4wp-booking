<?php
/**
 * WPForms → Telegram.
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking\Forms\Sources;

use ForWP\Booking\Contracts\Form_Source_Interface;
use ForWP\Booking\Forms\Form_Message_Formatter;
use ForWP\Booking\Forms\Form_Telegram_Dispatcher;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks WPForms after a completed entry.
 */
final class WPForms_Source implements Form_Source_Interface {

	public const SLUG = 'wpforms';

	/**
	 * {@inheritdoc}
	 */
	public function get_slug(): string {
		return self::SLUG;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'WPForms', '4wp-booking' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_implemented(): bool {
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_plugin_active(): bool {
		return defined( 'WPFORMS_VERSION' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function boot(): void {
		add_action( 'wpforms_process_complete', array( $this, 'on_process_complete' ), 20, 4 );
	}

	/**
	 * Handle a completed WPForms submission.
	 *
	 * @param array<int|string, mixed> $fields    Field values.
	 * @param array<string, mixed>     $entry     Entry.
	 * @param array<string, mixed>     $form_data Form data.
	 * @param int                      $entry_id  Entry id.
	 * @return void
	 */
	public function on_process_complete( $fields, $entry, $form_data, $entry_id ): void {
		unset( $entry, $entry_id );

		if ( ! is_array( $fields ) || ! is_array( $form_data ) ) {
			return;
		}

		$form_id    = isset( $form_data['id'] ) ? (int) $form_data['id'] : 0;
		$form_title = '';
		if ( isset( $form_data['settings']['form_title'] ) ) {
			$form_title = (string) $form_data['settings']['form_title'];
		}

		$rows = array();
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			$name  = isset( $field['name'] ) ? (string) $field['name'] : '';
			$id    = isset( $field['id'] ) ? (string) $field['id'] : '';
			$label = isset( $field['name'] ) ? (string) $field['name'] : $id;
			$value = $field['value'] ?? '';
			if ( Form_Message_Formatter::is_technical_field( $name ) || Form_Message_Formatter::is_technical_field( $id ) ) {
				continue;
			}
			$rows[] = array(
				'name'  => '' !== $id ? $id : $name,
				'label' => $label,
				'value' => $value,
			);
		}

		Form_Telegram_Dispatcher::dispatch( self::SLUG, $form_title, $rows, $form_id );
	}
}
