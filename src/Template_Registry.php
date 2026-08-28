<?php
/**
 * Registered booking form templates.
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking;

use ForWP\Booking\Contracts\Booking_Template_Interface;
use ForWP\Booking\Templates\Advanced_Template;
use ForWP\Booking\Templates\Calendar_Template;

defined( 'ABSPATH' ) || exit;

/**
 * Template registry; extend via filter.
 */
final class Template_Registry {

	/**
	 * Cached map.
	 *
	 * @var array<string, Booking_Template_Interface>|null
	 */
	private static $templates = null;

	/**
	 * All templates keyed by slug.
	 *
	 * @return array<string, Booking_Template_Interface>
	 */
	public static function all(): array {
		if ( null !== self::$templates ) {
			return self::$templates;
		}

		$map = array(
			Advanced_Template::SLUG => new Advanced_Template(),
			Calendar_Template::SLUG => new Calendar_Template(),
		);

		/**
		 * Filter booking templates.
		 *
		 * @param array<string, Booking_Template_Interface> $map Templates.
		 */
		$filtered        = apply_filters( 'forwp_booking_templates', $map );
		self::$templates = is_array( $filtered ) ? $filtered : $map;

		return self::$templates;
	}

	/**
	 * Whether a slug is registered.
	 *
	 * @param string $slug Template slug.
	 * @return bool
	 */
	public static function has( string $slug ): bool {
		$slug = sanitize_key( $slug );
		$all  = self::all();

		return isset( $all[ $slug ] );
	}

	/**
	 * Resolve a template by slug.
	 *
	 * @param string $slug Template slug.
	 * @return Booking_Template_Interface|null
	 */
	public static function get( string $slug ) {
		$slug = sanitize_key( $slug );
		$all  = self::all();

		if ( isset( $all[ $slug ] ) ) {
			return $all[ $slug ];
		}

		return isset( $all[ Calendar_Template::SLUG ] ) ? $all[ Calendar_Template::SLUG ] : null;
	}

	/**
	 * Settings catalog rows.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_admin_rows(): array {
		$default = Admin_Settings::instance()->get_default_template();
		$rows    = array();
		foreach ( self::all() as $tpl ) {
			$rows[] = array(
				'slug'        => $tpl->get_slug(),
				'label'       => $tpl->get_label(),
				'description' => $tpl->get_description(),
				'is_default'  => $default === $tpl->get_slug(),
			);
		}

		return $rows;
	}

	/**
	 * Reset static cache (tests).
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$templates = null;
	}
}
