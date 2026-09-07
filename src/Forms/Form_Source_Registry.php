<?php
/**
 * Registry of form → Telegram sources.
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking\Forms;

use ForWP\Booking\Contracts\Form_Source_Interface;
use ForWP\Booking\Forms\Sources\Contact_Form_7_Source;
use ForWP\Booking\Forms\Sources\Gravity_Forms_Source;
use ForWP\Booking\Forms\Sources\WPForms_Source;

defined( 'ABSPATH' ) || exit;

/**
 * CF7 / WPForms / Gravity Forms adapters.
 */
final class Form_Source_Registry {

	/**
	 * Registered form sources keyed by slug.
	 *
	 * @var array<string, Form_Source_Interface>|null
	 */
	private static $sources = null;

	/**
	 * Boot enabled sources.
	 *
	 * @return void
	 */
	public static function boot(): void {
		foreach ( self::all() as $source ) {
			if ( ! $source->is_implemented() ) {
				continue;
			}
			$source->boot();
		}
	}

	/**
	 * All registered form sources.
	 *
	 * @return array<string, Form_Source_Interface>
	 */
	public static function all(): array {
		if ( null !== self::$sources ) {
			return self::$sources;
		}

		$map = array(
			Contact_Form_7_Source::SLUG => new Contact_Form_7_Source(),
			WPForms_Source::SLUG        => new WPForms_Source(),
			Gravity_Forms_Source::SLUG  => new Gravity_Forms_Source(),
		);

		/**
		 * Filter form Telegram sources.
		 *
		 * @param array<string, Form_Source_Interface> $map Sources.
		 */
		$filtered      = apply_filters( 'forwp_booking_form_sources', $map );
		self::$sources = is_array( $filtered ) ? $filtered : $map;

		return self::$sources;
	}

	/**
	 * Get one source by slug.
	 *
	 * @param string $slug Source slug.
	 * @return Form_Source_Interface|null
	 */
	public static function get( string $slug ): ?Form_Source_Interface {
		$slug    = sanitize_key( $slug );
		$sources = self::all();

		return isset( $sources[ $slug ] ) ? $sources[ $slug ] : null;
	}

	/**
	 * Admin UI rows.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_admin_rows(): array {
		$rows = array();
		foreach ( self::all() as $source ) {
			$rows[] = array(
				'slug'          => $source->get_slug(),
				'label'         => $source->get_label(),
				'implemented'   => $source->is_implemented(),
				'plugin_active' => $source->is_plugin_active(),
				'status'        => self::status_message( $source ),
			);
		}

		return $rows;
	}

	/**
	 * Human-readable status for the admin UI.
	 *
	 * @param Form_Source_Interface $source Source.
	 * @return string
	 */
	private static function status_message( Form_Source_Interface $source ): string {
		if ( ! $source->is_implemented() ) {
			return __( 'Planned', '4wp-booking' );
		}
		if ( ! $source->is_plugin_active() ) {
			return __( 'Plugin not active', '4wp-booking' );
		}

		return __( 'Ready', '4wp-booking' );
	}
}
