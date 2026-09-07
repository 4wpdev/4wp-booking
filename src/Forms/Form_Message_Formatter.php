<?php
/**
 * Formats form field rows into a Telegram-friendly plain text body.
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Shared message builder for all form sources.
 */
final class Form_Message_Formatter {

	/**
	 * Build plain text from a form title and field rows.
	 *
	 * @param string                                                      $form_title Form title.
	 * @param array<int, array{label?:string,value?:string,name?:string}> $fields Field rows.
	 * @param string                                                      $source_label Optional source label (CF7, …).
	 * @return string
	 */
	public static function format( string $form_title, array $fields, string $source_label = '' ): string {
		$lines   = array();
		$lines[] = __( 'New form submission', '4wp-booking' );
		if ( '' !== $source_label ) {
			$lines[] = sprintf(
				/* translators: %s: form plugin name */
				__( 'Source: %s', '4wp-booking' ),
				$source_label
			);
		}
		$title = trim( $form_title );
		if ( '' !== $title ) {
			$lines[] = sprintf(
				/* translators: %s: form title */
				__( 'Form: %s', '4wp-booking' ),
				$title
			);
		}
		$lines[] = '';

		$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		if ( is_string( $site ) && '' !== $site ) {
			$lines[] = sprintf(
				/* translators: %s: site name */
				__( 'Site: %s', '4wp-booking' ),
				$site
			);
			$lines[] = '';
		}

		$added = 0;
		foreach ( $fields as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$label = isset( $row['label'] ) ? trim( (string) $row['label'] ) : '';
			if ( '' === $label && isset( $row['name'] ) ) {
				$label = trim( (string) $row['name'] );
			}
			$value = self::stringify_value( $row['value'] ?? '' );
			if ( '' === $value ) {
				continue;
			}
			if ( '' === $label ) {
				$label = __( 'Field', '4wp-booking' );
			}
			$lines[] = $label . ': ' . $value;
			++$added;
		}

		if ( 0 === $added ) {
			return '';
		}

		return implode( "\n", $lines );
	}

	/**
	 * Convert a field value to a single plain-text line.
	 *
	 * @param mixed $value Field value.
	 * @return string
	 */
	public static function stringify_value( $value ): string {
		if ( is_array( $value ) ) {
			$parts = array();
			foreach ( $value as $item ) {
				$item = self::stringify_value( $item );
				if ( '' !== $item ) {
					$parts[] = $item;
				}
			}

			return implode( ', ', $parts );
		}

		$text = wp_strip_all_tags( (string) $value );
		$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = preg_replace( '/[ \t]+/', ' ', $text );
		$text = trim( (string) $text );

		return $text;
	}

	/**
	 * Whether a field name should be skipped (CF7/meta noise).
	 *
	 * @param string $name Field name.
	 */
	public static function is_technical_field( string $name ): bool {
		$name = strtolower( trim( $name ) );
		if ( '' === $name ) {
			return true;
		}
		if ( 0 === strpos( $name, '_wpcf7' ) ) {
			return true;
		}
		if ( false !== strpos( $name, 'recaptcha' ) ) {
			return true;
		}
		if ( in_array( $name, array( 'g-recaptcha-response', 'h-captcha-response' ), true ) ) {
			return true;
		}

		return false;
	}
}
