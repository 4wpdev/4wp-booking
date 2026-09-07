<?php
/**
 * Widget shell appearance (defaults from Elementor / theme.json).
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking;

defined( 'ABSPATH' ) || exit;

/**
 * Resolve, sanitize, and emit CSS variables for the booking widget.
 */
final class Appearance {

	public const KEYS = array(
		'border_radius',
		'box_shadow',
		'border_width',
		'border_color',
		'bg_color',
		'text_color',
		'font_family',
		'primary_color',
	);

	/**
	 * Cached appearance sources by context key.
	 *
	 * @var array<string, array<string, string>>
	 */
	private static $source_cache = array();

	/**
	 * Plugin fallbacks (current calendar.css).
	 *
	 * @return array<string, string>
	 */
	public static function plugin_fallbacks(): array {
		return array(
			'border_radius' => '20',
			'box_shadow'    => 'soft',
			'border_width'  => '1',
			'border_color'  => '#e5e7eb',
			'bg_color'      => '#ffffff',
			'text_color'    => '#111827',
			'font_family'   => 'inherit',
			'primary_color' => '#111827',
		);
	}

	/**
	 * Box-shadow CSS for stored slugs.
	 *
	 * @return array<string, string>
	 */
	public static function shadow_presets(): array {
		return array(
			'none'   => 'none',
			'soft'   => '0 16px 48px rgba(17, 24, 39, 0.07)',
			'medium' => '0 12px 32px rgba(17, 24, 39, 0.14)',
		);
	}

	/**
	 * Empty overrides (use site defaults).
	 *
	 * @return array<string, string>
	 */
	public static function empty_overrides(): array {
		$out = array();
		foreach ( self::KEYS as $key ) {
			$out[ $key ] = '';
		}

		return $out;
	}

	/**
	 * Site defaults: plugin → theme.json → Elementor (last wins).
	 *
	 * @return array<string, string>
	 */
	public static function site_defaults(): array {
		$out = self::plugin_fallbacks();
		foreach ( self::from_theme_json() as $key => $value ) {
			if ( '' !== $value ) {
				$out[ $key ] = $value;
			}
		}
		foreach ( self::from_elementor() as $key => $value ) {
			if ( '' !== $value ) {
				$out[ $key ] = $value;
			}
		}

		return $out;
	}

	/**
	 * Which project sources contributed at least one value.
	 *
	 * @return array<string, bool>
	 */
	public static function sources(): array {
		return array(
			'theme_json' => array() !== self::from_theme_json(),
			'elementor'  => array() !== self::from_elementor(),
		);
	}

	/**
	 * Stored overrides (empty string = site default).
	 *
	 * @return array<string, string>
	 */
	public static function get_overrides(): array {
		$options = Admin_Settings::instance()->get_options();
		$stored  = isset( $options['appearance'] ) && is_array( $options['appearance'] ) ? $options['appearance'] : array();

		return self::sanitize( $stored );
	}

	/**
	 * Persist overrides.
	 *
	 * @param array<string, mixed> $raw Posted appearance.
	 * @return void
	 */
	public static function set_overrides( array $raw ): void {
		$options               = Admin_Settings::instance()->get_options();
		$options['appearance'] = self::sanitize( $raw );
		Admin_Settings::instance()->save_options( $options );
	}

	/**
	 * Site default with overrides applied.
	 *
	 * @return array<string, string>
	 */
	public static function resolve(): array {
		$defaults  = self::site_defaults();
		$overrides = self::get_overrides();
		$out       = $defaults;
		foreach ( self::KEYS as $key ) {
			if ( isset( $overrides[ $key ] ) && '' !== $overrides[ $key ] ) {
				$out[ $key ] = $overrides[ $key ];
			}
		}

		return $out;
	}

	/**
	 * Sanitize a posted/stored map.
	 *
	 * @param array<string, mixed> $raw Raw map.
	 * @return array<string, string>
	 */
	public static function sanitize( array $raw ): array {
		$out = self::empty_overrides();
		foreach ( self::KEYS as $key ) {
			if ( ! array_key_exists( $key, $raw ) ) {
				continue;
			}
			$out[ $key ] = self::sanitize_value( $key, $raw[ $key ] );
		}

		return $out;
	}

	/**
	 * Inline CSS for the front-end widget.
	 *
	 * @return string
	 */
	public static function inline_css(): string {
		$vars = self::css_variables( self::resolve() );
		if ( array() === $vars ) {
			return '';
		}
		$parts = array();
		foreach ( $vars as $name => $value ) {
			$parts[] = $name . ': ' . $value;
		}

		return '.forwp-booking.forwp-booking--calendar{' . implode( ';', $parts ) . ';}';
	}

	/**
	 * CSS custom properties from a resolved map.
	 *
	 * @param array<string, string> $resolved Resolved appearance.
	 * @return array<string, string>
	 */
	public static function css_variables( array $resolved ): array {
		$shadows = self::shadow_presets();
		$shadow  = isset( $resolved['box_shadow'] ) ? (string) $resolved['box_shadow'] : 'soft';
		$shadow  = isset( $shadows[ $shadow ] ) ? $shadows[ $shadow ] : $shadows['soft'];

		$radius = isset( $resolved['border_radius'] ) ? (string) $resolved['border_radius'] : '20';
		$width  = isset( $resolved['border_width'] ) ? (string) $resolved['border_width'] : '1';
		$font   = isset( $resolved['font_family'] ) ? (string) $resolved['font_family'] : 'inherit';
		if ( 'inherit' !== $font && false === strpos( $font, ',' ) && false === strpos( $font, ' ' ) ) {
			$font = '"' . $font . '", sans-serif';
		}

		return array(
			'--forwp-booking-accent'       => isset( $resolved['primary_color'] ) ? (string) $resolved['primary_color'] : '#111827',
			'--forwp-booking-bg'           => isset( $resolved['bg_color'] ) ? (string) $resolved['bg_color'] : '#ffffff',
			'--forwp-booking-text'         => isset( $resolved['text_color'] ) ? (string) $resolved['text_color'] : '#111827',
			'--forwp-booking-border'       => isset( $resolved['border_color'] ) ? (string) $resolved['border_color'] : '#e5e7eb',
			'--forwp-booking-border-width' => $width . 'px',
			'--forwp-booking-radius'       => $radius . 'px',
			'--forwp-booking-shadow'       => $shadow,
			'--forwp-booking-font'         => $font,
		);
	}

	/**
	 * Sanitize one field.
	 *
	 * @param string $key   Field key.
	 * @param mixed  $value Raw value.
	 * @return string
	 */
	public static function sanitize_value( string $key, $value ): string {
		if ( is_int( $value ) || is_float( $value ) ) {
			$value = (string) $value;
		}
		if ( ! is_string( $value ) ) {
			return '';
		}
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}

		switch ( $key ) {
			case 'border_radius':
			case 'border_width':
				if ( ! preg_match( '/^\d{1,3}$/', $value ) ) {
					return '';
				}
				$n   = (int) $value;
				$max = 'border_radius' === $key ? 48 : 8;

				return (string) max( 0, min( $max, $n ) );
			case 'box_shadow':
				$value = sanitize_key( $value );

				return isset( self::shadow_presets()[ $value ] ) ? $value : '';
			case 'border_color':
			case 'bg_color':
			case 'text_color':
			case 'primary_color':
				$hex = function_exists( 'sanitize_hex_color' ) ? sanitize_hex_color( $value ) : '';
				if ( is_string( $hex ) && '' !== $hex ) {
					return $hex;
				}

				return preg_match( '/^#[0-9a-fA-F]{6}$/', $value ) ? strtolower( $value ) : '';
			case 'font_family':
				$clean = preg_replace( '/[^a-zA-Z0-9\s,\.\-\'"]+/', '', $value );

				return is_string( $clean ) ? trim( $clean ) : '';
			default:
				return '';
		}
	}

	/**
	 * Values from wp_get_global_styles / settings (theme.json).
	 *
	 * @return array<string, string>
	 */
	private static function from_theme_json(): array {
		if ( isset( self::$source_cache['theme_json'] ) ) {
			return self::$source_cache['theme_json'];
		}
		$out = array();
		if ( function_exists( 'wp_get_global_styles' ) ) {
			$styles = wp_get_global_styles();
			if ( is_array( $styles ) ) {
				if ( ! empty( $styles['color']['background'] ) ) {
					$out['bg_color'] = self::normalize_color( (string) $styles['color']['background'] );
				}
				if ( ! empty( $styles['color']['text'] ) ) {
					$out['text_color'] = self::normalize_color( (string) $styles['color']['text'] );
				}
				if ( ! empty( $styles['typography']['fontFamily'] ) ) {
					$out['font_family'] = self::normalize_font( (string) $styles['typography']['fontFamily'] );
				}
				if ( isset( $styles['border']['radius'] ) ) {
					$out['border_radius'] = self::css_size_to_px( $styles['border']['radius'] );
				}
				if ( isset( $styles['border']['width'] ) ) {
					$out['border_width'] = self::css_size_to_px( $styles['border']['width'] );
				}
				if ( ! empty( $styles['border']['color'] ) ) {
					$out['border_color'] = self::normalize_color( (string) $styles['border']['color'] );
				}
			}
		}

		if ( function_exists( 'wp_get_global_settings' ) ) {
			$settings = wp_get_global_settings();
			$palette  = array();
			if ( isset( $settings['color']['palette']['theme'] ) && is_array( $settings['color']['palette']['theme'] ) ) {
				$palette = $settings['color']['palette']['theme'];
			} elseif ( isset( $settings['color']['palette']['default'] ) && is_array( $settings['color']['palette']['default'] ) ) {
				$palette = $settings['color']['palette']['default'];
			}
			foreach ( $palette as $swatch ) {
				if ( ! is_array( $swatch ) ) {
					continue;
				}
				$slug  = isset( $swatch['slug'] ) ? (string) $swatch['slug'] : '';
				$color = isset( $swatch['color'] ) ? self::normalize_color( (string) $swatch['color'] ) : '';
				if ( '' === $color ) {
					continue;
				}
				if ( 'primary' === $slug || false !== strpos( $slug, 'primary' ) || 'button' === $slug ) {
					$out['primary_color'] = $color;
					break;
				}
			}
			if ( empty( $out['primary_color'] ) && isset( $palette[0] ) && is_array( $palette[0] ) && ! empty( $palette[0]['color'] ) ) {
				$out['primary_color'] = self::normalize_color( (string) $palette[0]['color'] );
			}
		}

		$filtered                         = array_filter(
			$out,
			static function ( $value ) {
				return is_string( $value ) && '' !== $value;
			}
		);
		self::$source_cache['theme_json'] = $filtered;

		return $filtered;
	}

	/**
	 * Values from the active Elementor kit.
	 *
	 * @return array<string, string>
	 */
	private static function from_elementor(): array {
		if ( isset( self::$source_cache['elementor'] ) ) {
			return self::$source_cache['elementor'];
		}
		$out = array();
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			self::$source_cache['elementor'] = array();

			return array();
		}

		try {
			$plugin = \Elementor\Plugin::$instance;
			if ( ! is_object( $plugin ) || empty( $plugin->kits_manager ) ) {
				self::$source_cache['elementor'] = array();

				return array();
			}
			$kits = $plugin->kits_manager;
			if ( ! method_exists( $kits, 'get_active_kit' ) ) {
				self::$source_cache['elementor'] = array();

				return array();
			}
			$kit = $kits->get_active_kit();
			if ( ! is_object( $kit ) || ! method_exists( $kit, 'get_settings_for_display' ) ) {
				self::$source_cache['elementor'] = array();

				return array();
			}

			$colors = $kit->get_settings_for_display( 'system_colors' );
			if ( is_array( $colors ) ) {
				foreach ( $colors as $row ) {
					if ( ! is_array( $row ) || empty( $row['_id'] ) || empty( $row['color'] ) ) {
						continue;
					}
					$id    = (string) $row['_id'];
					$color = self::normalize_color( (string) $row['color'] );
					if ( '' === $color ) {
						continue;
					}
					if ( 'primary' === $id ) {
						$out['primary_color'] = $color;
					} elseif ( 'text' === $id ) {
						$out['text_color'] = $color;
					}
				}
			}

			$typo = $kit->get_settings_for_display( 'system_typography' );
			if ( is_array( $typo ) ) {
				foreach ( $typo as $row ) {
					if ( ! is_array( $row ) || empty( $row['_id'] ) ) {
						continue;
					}
					if ( 'text' !== (string) $row['_id'] && 'primary' !== (string) $row['_id'] ) {
						continue;
					}
					$ff = '';
					if ( ! empty( $row['typography_font_family'] ) ) {
						$ff = (string) $row['typography_font_family'];
					}
					if ( '' !== $ff ) {
						$out['font_family'] = self::normalize_font( $ff );
						if ( 'text' === (string) $row['_id'] ) {
							break;
						}
					}
				}
			}

			$body_bg = $kit->get_settings_for_display( 'body_background_color' );
			if ( is_string( $body_bg ) && '' !== $body_bg ) {
				$out['bg_color'] = self::normalize_color( $body_bg );
			}
		} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			unset( $e );
		}

		$filtered                        = array_filter(
			$out,
			static function ( $value ) {
				return is_string( $value ) && '' !== $value;
			}
		);
		self::$source_cache['elementor'] = $filtered;

		return $filtered;
	}

	/**
	 * Reset source cache (tests).
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$source_cache = array();
	}

	/**
	 * Keep hex colors; drop CSS variables / presets we cannot resolve.
	 *
	 * @param string $raw Color.
	 * @return string
	 */
	private static function normalize_color( string $raw ): string {
		$raw = trim( $raw );
		if ( 0 === strpos( $raw, 'var:' ) || 0 === strpos( $raw, 'var(' ) ) {
			return '';
		}

		return self::sanitize_value( 'primary_color', $raw );
	}

	/**
	 * Strip preset wrappers from a font family.
	 *
	 * @param string $raw Font.
	 * @return string
	 */
	private static function normalize_font( string $raw ): string {
		$raw = trim( $raw );
		if ( 0 === strpos( $raw, 'var:' ) || 0 === strpos( $raw, 'var(' ) ) {
			return '';
		}
		$raw = trim( $raw, "\"' " );

		return self::sanitize_value( 'font_family', $raw );
	}

	/**
	 * Convert a theme.json size to a px integer string.
	 *
	 * @param mixed $raw Size.
	 * @return string
	 */
	private static function css_size_to_px( $raw ): string {
		if ( is_array( $raw ) ) {
			if ( isset( $raw['top'] ) ) {
				$raw = $raw['top'];
			} elseif ( isset( $raw['size'] ) ) {
				$unit = isset( $raw['unit'] ) ? (string) $raw['unit'] : 'px';
				$raw  = (string) $raw['size'] . $unit;
			}
		}
		if ( ! is_string( $raw ) && ! is_numeric( $raw ) ) {
			return '';
		}
		$raw = trim( (string) $raw );
		if ( preg_match( '/^(\d+(?:\.\d+)?)px$/', $raw, $m ) ) {
			return (string) (int) round( (float) $m[1] );
		}
		if ( preg_match( '/^\d+$/', $raw ) ) {
			return $raw;
		}

		return '';
	}
}
