<?php
/**
 * Plugin bootstrap.
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking;

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin singleton.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Singleton accessor.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {}

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot(): void {
		add_filter( 'plugin_locale', array( $this, 'filter_plugin_locale' ), 10, 2 );
		add_action( 'init', array( $this, 'load_textdomain' ) );
		Admin_Settings::instance()->boot();
		Rest_Booking::register();
		Shortcode::register();
		Block::register();
		Elementor::register();
		Forms\Form_Source_Registry::boot();
	}

	/**
	 * Ukrainian is the bundled default; other locales load when a matching file exists.
	 *
	 * @param string $locale WordPress locale.
	 * @param string $domain Text domain.
	 * @return string
	 */
	public function filter_plugin_locale( $locale, $domain ): string {
		if ( '4wp-booking' !== $domain ) {
			return $locale;
		}

		return self::resolve_locale( (string) $locale );
	}

	/**
	 * Locale used for this plugin.
	 *
	 * @param string $requested WordPress locale (empty = current).
	 * @return string
	 */
	public static function resolve_locale( string $requested = '' ): string {
		if ( '' === $requested ) {
			$requested = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		}

		$dir = FORWP_BOOKING_PATH . 'languages';
		$wp  = defined( 'WP_LANG_DIR' ) ? WP_LANG_DIR . '/plugins' : '';
		$try = array( $requested );
		if ( false !== strpos( $requested, '_' ) ) {
			$try[] = explode( '_', $requested, 2 )[0];
		}

		foreach ( $try as $locale ) {
			if ( '' === $locale ) {
				continue;
			}
			if ( is_readable( $dir . '/4wp-booking-' . $locale . '.mo' ) ) {
				return $locale;
			}
			if ( '' !== $wp && is_readable( $wp . '/4wp-booking-' . $locale . '.mo' ) ) {
				return $locale;
			}
		}

		return 'uk';
	}

	/**
	 * Load bundled translations from /languages (Ukrainian ships with the plugin).
	 *
	 * WordPress.org also loads translations from the language packs when available.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		$locale = self::resolve_locale();
		$mofile = FORWP_BOOKING_PATH . 'languages/4wp-booking-' . $locale . '.mo';
		if ( is_readable( $mofile ) ) {
			load_textdomain( '4wp-booking', $mofile );
		}
	}

	/**
	 * Render a booking instance (block, shortcode, Elementor).
	 *
	 * @param array<string, mixed> $atts Attributes.
	 * @return string
	 */
	public static function render( array $atts ): string {
		$provider = isset( $atts['provider'] ) ? sanitize_key( (string) $atts['provider'] ) : '';
		$template = isset( $atts['template'] ) ? sanitize_key( (string) $atts['template'] ) : '';
		$flow     = isset( $atts['flow'] ) ? Admin_Settings::sanitize_booking_flow( (string) $atts['flow'] ) : '';
		if ( '' === $template ) {
			$template = Admin_Settings::instance()->get_default_template();
		}
		if ( '' === $flow ) {
			$flow = Admin_Settings::instance()->get_booking_flow();
		}
		$tpl = Template_Registry::get( $template );
		if ( ! $tpl ) {
			return '';
		}

		return $tpl->render(
			array(
				'provider' => $provider,
				'flow'     => $flow,
				'uid'      => isset( $atts['uid'] ) ? (string) $atts['uid'] : '',
			)
		);
	}
}
