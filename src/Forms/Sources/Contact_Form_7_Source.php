<?php
/**
 * Contact Form 7 → Telegram.
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking\Forms\Sources;

use ForWP\Booking\Contracts\Form_Source_Interface;
use ForWP\Booking\Forms\Form_Message_Formatter;
use ForWP\Booking\Forms\Form_Telegram_Dispatcher;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks CF7 after a successful mail send.
 */
final class Contact_Form_7_Source implements Form_Source_Interface {

	public const SLUG = 'contact-form-7';

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
		return __( 'Contact Form 7', '4wp-booking' );
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
		return defined( 'WPCF7_VERSION' ) || class_exists( '\WPCF7_ContactForm' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function boot(): void {
		add_action( 'wpcf7_mail_sent', array( $this, 'on_mail_sent' ), 20, 1 );
	}

	/**
	 * Handle Contact Form 7 after mail is sent.
	 *
	 * @param mixed $contact_form Form object.
	 * @return void
	 */
	public function on_mail_sent( $contact_form ): void {
		if ( ! is_object( $contact_form ) || ! is_a( $contact_form, 'WPCF7_ContactForm' ) ) {
			return;
		}
		if ( ! class_exists( '\WPCF7_Submission' ) ) {
			return;
		}

		$submission = \WPCF7_Submission::get_instance();
		if ( ! $submission ) {
			return;
		}

		$posted = $submission->get_posted_data();
		if ( ! is_array( $posted ) ) {
			return;
		}

		$fields = array();
		foreach ( $posted as $name => $value ) {
			$name = (string) $name;
			if ( Form_Message_Formatter::is_technical_field( $name ) ) {
				continue;
			}
			$fields[] = array(
				'name'  => $name,
				'label' => $name,
				'value' => $value,
			);
		}

		Form_Telegram_Dispatcher::dispatch(
			self::SLUG,
			(string) $contact_form->title(),
			$fields,
			(int) $contact_form->id()
		);
	}
}
