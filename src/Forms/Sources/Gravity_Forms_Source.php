<?php
/**
 * Gravity Forms → Telegram.
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking\Forms\Sources;

use ForWP\Booking\Contracts\Form_Source_Interface;
use ForWP\Booking\Forms\Form_Message_Formatter;
use ForWP\Booking\Forms\Form_Telegram_Dispatcher;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks Gravity Forms after submission.
 */
final class Gravity_Forms_Source implements Form_Source_Interface {

	public const SLUG = 'gravityforms';

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
		return __( 'Gravity Forms', '4wp-booking' );
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
		return class_exists( '\GFForms' ) || class_exists( '\GFAPI' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function boot(): void {
		add_action( 'gform_after_submission', array( $this, 'on_after_submission' ), 20, 2 );
	}

	/**
	 * Handle Gravity Forms after submission.
	 *
	 * @param array<string, mixed> $entry Entry.
	 * @param array<string, mixed> $form  Form.
	 * @return void
	 */
	public function on_after_submission( $entry, $form ): void {
		if ( ! is_array( $entry ) || ! is_array( $form ) ) {
			return;
		}

		$form_id    = isset( $form['id'] ) ? (int) $form['id'] : 0;
		$form_title = isset( $form['title'] ) ? (string) $form['title'] : '';
		$fields_def = isset( $form['fields'] ) && is_array( $form['fields'] ) ? $form['fields'] : array();

		$rows = array();
		foreach ( $fields_def as $field ) {
			if ( ! is_object( $field ) && ! is_array( $field ) ) {
				continue;
			}
			$field_id = is_object( $field ) ? (string) ( $field->id ?? '' ) : (string) ( $field['id'] ?? '' );
			$label    = is_object( $field ) ? (string) ( $field->label ?? $field_id ) : (string) ( $field['label'] ?? $field_id );
			$type     = is_object( $field ) ? (string) ( $field->type ?? '' ) : (string) ( $field['type'] ?? '' );
			if ( in_array( $type, array( 'honeypot', 'captcha', 'page', 'section', 'html', 'password' ), true ) ) {
				continue;
			}
			if ( '' === $field_id || ! array_key_exists( $field_id, $entry ) ) {
				continue;
			}
			$value = $entry[ $field_id ];
			if ( Form_Message_Formatter::is_technical_field( $label ) ) {
				continue;
			}
			$rows[] = array(
				'name'  => $field_id,
				'label' => $label,
				'value' => $value,
			);
		}

		Form_Telegram_Dispatcher::dispatch( self::SLUG, $form_title, $rows, $form_id );
	}
}
