<?php
/**
 * Plugin options and React admin shell.
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking;

use ForWP\Booking\Templates\Advanced_Template;

defined( 'ABSPATH' ) || exit;

/**
 * Top-level admin screen + REST-backed UI.
 */
final class Admin_Settings {

	public const OPTION_KEY = 'forwp_booking_options';

	/**
	 * Singleton.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Shared instance.
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
	 * Hooks.
	 *
	 * @return void
	 */
	public function boot(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		Rest_Settings::register();
	}

	/**
	 * Options page.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_menu_page(
			__( '4WP Booking', '4wp-booking' ),
			__( '4WP Booking', '4wp-booking' ),
			'manage_options',
			'forwp-booking',
			array( $this, 'render_settings_page' ),
			'dashicons-calendar-alt',
			58
		);
	}

	/**
	 * React mount markup.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div class="wrap forwp-booking-admin-shell">';
		echo '<h1 class="forwp-booking-admin-heading">';
		echo '<span class="forwp-booking-admin-heading__icon" aria-hidden="true">';
		echo wp_kses( self::heading_svg(), self::heading_svg_allowed_html() );
		echo '</span>';
		echo '<span class="forwp-booking-admin-heading__text">';
		echo esc_html__( '4WP Booking', '4wp-booking' );
		echo '</span>';
		echo '</h1>';
		echo '<div id="forwp-booking-admin-root" class="forwp-booking-admin-root" aria-live="polite"></div>';
		echo '</div>';
	}

	/**
	 * Allowed tags for the heading SVG.
	 *
	 * @return array<string, array<string, bool>>
	 */
	private static function heading_svg_allowed_html(): array {
		return array(
			'svg'  => array(
				'xmlns'       => true,
				'viewbox'     => true,
				'width'       => true,
				'height'      => true,
				'fill'        => true,
				'focusable'   => true,
				'aria-hidden' => true,
			),
			'path' => array(
				'd'               => true,
				'fill'            => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
			),
			'rect' => array(
				'x'      => true,
				'y'      => true,
				'width'  => true,
				'height' => true,
				'rx'     => true,
				'fill'   => true,
				'stroke' => true,
			),
		);
	}

	/**
	 * Calendar icon for the settings heading.
	 *
	 * @return string
	 */
	private static function heading_svg(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" fill="none" focusable="false" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="2" stroke="currentColor" stroke-width="1.75"/><path d="M8 3.5v3M16 3.5v3M3.5 10h17" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>';
	}

	/**
	 * Scripts and styles only on our screen.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		if ( 'toplevel_page_forwp-booking' !== $hook_suffix ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$asset_file = FORWP_BOOKING_PATH . 'build/admin/index.asset.php';
		if ( ! is_readable( $asset_file ) ) {
			return;
		}

		$asset = include $asset_file;

		wp_enqueue_style( 'wp-components' );
		wp_enqueue_style( 'dashicons' );

		$style_path = FORWP_BOOKING_PATH . 'build/admin/style-index.css';
		if ( is_readable( $style_path ) ) {
			wp_enqueue_style(
				'forwp-booking-admin',
				FORWP_BOOKING_URL . 'build/admin/style-index.css',
				array( 'wp-components' ),
				$asset['version']
			);
		}

		wp_enqueue_script(
			'forwp-booking-admin',
			FORWP_BOOKING_URL . 'build/admin/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);
		wp_set_script_translations(
			'forwp-booking-admin',
			'4wp-booking',
			FORWP_BOOKING_PATH . 'languages'
		);

		wp_localize_script(
			'forwp-booking-admin',
			'forwpBookingAdmin',
			array(
				'restRoot' => esc_url_raw( rest_url() ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

	/**
	 * Sanitize API key input.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_api_key( $value ): string {
		$value = is_string( $value ) ? $value : '';
		return sanitize_text_field( trim( $value ) );
	}

	/**
	 * Stored options.
	 *
	 * @return array<string, mixed>
	 */
	public function get_options(): array {
		$stored = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$defaults = array(
			'provider'         => 'cliniccards',
			'tokens'           => array(),
			'default_template' => 'advanced',
			'booking_flow'     => 'staff',
			'copy'             => array(),
			'notify_emails'    => '',
			'channel_tokens'   => array(),
			'channel_targets'  => array(),
			'form_telegram'    => array(),
			'appearance'       => array(),
		);

		return array_merge( $defaults, $stored );
	}

	/**
	 * Persist options array.
	 *
	 * @param array<string, mixed> $options Options.
	 * @return void
	 */
	public function save_options( array $options ): void {
		update_option( self::OPTION_KEY, $options, false );
	}

	/**
	 * Active provider slug.
	 *
	 * @return string
	 */
	public function get_provider_slug(): string {
		$options = $this->get_options();

		return isset( $options['provider'] ) ? sanitize_key( (string) $options['provider'] ) : 'cliniccards';
	}

	/**
	 * Set credential provider slug.
	 *
	 * @param string $slug Provider slug.
	 * @return void
	 */
	public function set_provider_slug( string $slug ): void {
		$options             = $this->get_options();
		$options['provider'] = sanitize_key( $slug );
		$this->save_options( $options );
	}

	/**
	 * API token for a provider.
	 *
	 * @param string $slug Provider slug.
	 * @return string
	 */
	public function get_provider_token( string $slug ): string {
		$options = $this->get_options();
		$tokens  = isset( $options['tokens'] ) && is_array( $options['tokens'] ) ? $options['tokens'] : array();

		return isset( $tokens[ $slug ] ) ? (string) $tokens[ $slug ] : '';
	}

	/**
	 * Store or clear a provider token.
	 *
	 * @param string $slug  Provider slug.
	 * @param string $token Token (empty clears).
	 * @return void
	 */
	public function set_provider_token( string $slug, string $token ): void {
		$options = $this->get_options();
		if ( ! isset( $options['tokens'] ) || ! is_array( $options['tokens'] ) ) {
			$options['tokens'] = array();
		}
		$slug = sanitize_key( $slug );
		if ( '' === $token ) {
			unset( $options['tokens'][ $slug ] );
		} else {
			$options['tokens'][ $slug ] = $token;
		}
		$this->save_options( $options );
	}

	/**
	 * Default form template slug.
	 *
	 * @return string
	 */
	public function get_default_template(): string {
		$options = $this->get_options();
		$slug    = isset( $options['default_template'] ) ? sanitize_key( (string) $options['default_template'] ) : 'advanced';
		if ( '' === $slug || ! Template_Registry::has( $slug ) ) {
			return Advanced_Template::SLUG;
		}

		return $slug;
	}

	/**
	 * Persist default template.
	 *
	 * @param string $slug Template slug.
	 * @return void
	 */
	public function set_default_template( string $slug ): void {
		$options                     = $this->get_options();
		$options['default_template'] = sanitize_key( $slug );
		$this->save_options( $options );
	}

	/**
	 * Allowed booking flows: staff, date, or service.
	 *
	 * @param string $flow Raw flow.
	 * @return string Empty if invalid (caller may fall back).
	 */
	public static function sanitize_booking_flow( string $flow ): string {
		$flow = sanitize_key( $flow );
		if ( in_array( $flow, array( 'staff', 'date', 'service' ), true ) ) {
			return $flow;
		}

		return '';
	}

	/**
	 * Active booking flow.
	 *
	 * @return string staff|date|service
	 */
	public function get_booking_flow(): string {
		$options = $this->get_options();
		$flow    = isset( $options['booking_flow'] ) ? self::sanitize_booking_flow( (string) $options['booking_flow'] ) : '';

		return '' !== $flow ? $flow : 'staff';
	}

	/**
	 * Persist booking flow.
	 *
	 * @param string $flow Flow slug.
	 * @return void
	 */
	public function set_booking_flow( string $flow ): void {
		$clean = self::sanitize_booking_flow( $flow );
		if ( '' === $clean ) {
			$clean = 'staff';
		}
		$options                 = $this->get_options();
		$options['booking_flow'] = $clean;
		$this->save_options( $options );
	}

	/**
	 * Default front-end copy (translatable).
	 *
	 * @return array<string, string>
	 */
	public static function default_copy(): array {
		return array(
			'offerings_title'       => __( 'Select a doctor', '4wp-booking' ),
			'offerings_description' => '',
			'tab_service'           => __( 'By service', '4wp-booking' ),
			'tab_date'              => __( 'By date', '4wp-booking' ),
			'tab_staff'             => __( 'By doctor', '4wp-booking' ),
			'select_service'        => __( 'Select a service', '4wp-booking' ),
			'select_date'           => __( 'Select a date', '4wp-booking' ),
			'select_time'           => __( 'Select a time', '4wp-booking' ),
			'your_details'          => __( 'Your details', '4wp-booking' ),
			'first_name'            => __( 'First name', '4wp-booking' ),
			'last_name'             => __( 'Last name', '4wp-booking' ),
			'phone'                 => __( 'Phone', '4wp-booking' ),
			'email'                 => __( 'Email', '4wp-booking' ),
			'comment'               => __( 'Comment', '4wp-booking' ),
			'submit'                => __( 'Confirm booking', '4wp-booking' ),
			'success'               => __( 'Your appointment request has been sent.', '4wp-booking' ),
			'done_title'            => __( 'Request sent', '4wp-booking' ),
			'back'                  => __( 'Back', '4wp-booking' ),
			'error_required'        => __( 'This field is required.', '4wp-booking' ),
			'error_name'            => __( 'Enter at least 2 characters.', '4wp-booking' ),
			'error_phone'           => __( 'Enter a valid phone number.', '4wp-booking' ),
			'error_email'           => __( 'Enter a valid email address.', '4wp-booking' ),
			'no_offerings'          => __( 'No doctors are available to book.', '4wp-booking' ),
			'no_services'           => __( 'No services are available to book.', '4wp-booking' ),
			'no_slots'              => __( 'No times on this day.', '4wp-booking' ),
			'no_month_slots'        => __( 'No free times this month.', '4wp-booking' ),
		);
	}

	/**
	 * Stored copy overrides (empty string = use default).
	 *
	 * @return array<string, string>
	 */
	public function get_copy_overrides(): array {
		$options = $this->get_options();
		$stored  = isset( $options['copy'] ) && is_array( $options['copy'] ) ? $options['copy'] : array();
		$out     = array();
		foreach ( array_keys( self::default_copy() ) as $key ) {
			$out[ $key ] = isset( $stored[ $key ] ) ? (string) $stored[ $key ] : '';
		}

		return $out;
	}

	/**
	 * Front-end copy with defaults, overrides, and gettext filter.
	 *
	 * @return array<string, string>
	 */
	public function get_copy(): array {
		$overrides = $this->get_copy_overrides();
		$defaults  = self::default_copy();
		$out       = array();
		foreach ( $defaults as $key => $default ) {
			$override    = isset( $overrides[ $key ] ) ? trim( $overrides[ $key ] ) : '';
			$value       = '' !== $override ? $override : $default;
			$out[ $key ] = (string) apply_filters( 'forwp_booking_copy_string', $value, $key );
		}

		return $out;
	}

	/**
	 * Persist copy overrides.
	 *
	 * @param array<string, mixed> $copy Posted copy.
	 * @return void
	 */
	public function set_copy( array $copy ): void {
		$clean = array();
		foreach ( array_keys( self::default_copy() ) as $key ) {
			if ( ! array_key_exists( $key, $copy ) ) {
				continue;
			}
			$raw = is_string( $copy[ $key ] ) ? $copy[ $key ] : '';
			if ( 'offerings_description' === $key ) {
				$clean[ $key ] = sanitize_textarea_field( $raw );
			} else {
				$clean[ $key ] = sanitize_text_field( $raw );
			}
		}
		$options         = $this->get_options();
		$options['copy'] = $clean;
		$this->save_options( $options );
	}

	/**
	 * Split a comma-separated email field. Empty field → fallback (admin email).
	 *
	 * @param string $raw      Posted or stored value.
	 * @param string $fallback Used when $raw is empty.
	 * @return string[]
	 */
	public static function parse_email_list( string $raw, string $fallback = '' ): array {
		$source = trim( $raw );
		if ( '' === $source ) {
			$source = trim( $fallback );
		}
		if ( '' === $source ) {
			return array();
		}

		$parts = preg_split( '/[,\s;]+/', $source );
		if ( ! is_array( $parts ) ) {
			$parts = array();
		}
		$out = array();
		foreach ( $parts as $part ) {
			$email = sanitize_email( (string) $part );
			if ( '' !== $email && is_email( $email ) && ! in_array( $email, $out, true ) ) {
				$out[] = $email;
			}
		}

		return $out;
	}

	/**
	 * Split comma-separated chat/target ids.
	 *
	 * @param string $raw Stored value.
	 * @return string[]
	 */
	public static function parse_id_list( string $raw ): array {
		$parts = preg_split( '/[,\s;]+/', trim( $raw ) );
		if ( ! is_array( $parts ) ) {
			$parts = array();
		}
		$out = array();
		foreach ( $parts as $part ) {
			$id = sanitize_text_field( (string) $part );
			if ( '' !== $id && ! in_array( $id, $out, true ) ) {
				$out[] = $id;
			}
		}

		return $out;
	}

	/**
	 * Notification recipients. Blank setting uses the WordPress admin email.
	 *
	 * @return string[]
	 */
	public function get_notify_emails(): array {
		$options  = $this->get_options();
		$stored   = isset( $options['notify_emails'] ) ? (string) $options['notify_emails'] : '';
		$fallback = (string) get_option( 'admin_email', '' );

		return self::parse_email_list( $stored, $fallback );
	}

	/**
	 * Raw notify_emails field (empty = use admin email).
	 *
	 * @return string
	 */
	public function get_notify_emails_raw(): string {
		$options = $this->get_options();

		return isset( $options['notify_emails'] ) ? (string) $options['notify_emails'] : '';
	}

	/**
	 * Persist notify emails field.
	 *
	 * @param string $raw Comma-separated emails or empty.
	 * @return void
	 */
	public function set_notify_emails( string $raw ): void {
		$options                  = $this->get_options();
		$options['notify_emails'] = sanitize_text_field( $raw );
		$this->save_options( $options );
	}

	/**
	 * Channel secret (bot token).
	 *
	 * @param string $slug Channel slug.
	 * @return string
	 */
	public function get_channel_token( string $slug ): string {
		$options = $this->get_options();
		$tokens  = isset( $options['channel_tokens'] ) && is_array( $options['channel_tokens'] ) ? $options['channel_tokens'] : array();
		$slug    = sanitize_key( $slug );

		return isset( $tokens[ $slug ] ) ? (string) $tokens[ $slug ] : '';
	}

	/**
	 * Store or clear a channel token.
	 *
	 * @param string $slug  Channel slug.
	 * @param string $token Token (empty clears).
	 * @return void
	 */
	public function set_channel_token( string $slug, string $token ): void {
		$options = $this->get_options();
		if ( ! isset( $options['channel_tokens'] ) || ! is_array( $options['channel_tokens'] ) ) {
			$options['channel_tokens'] = array();
		}
		$slug = sanitize_key( $slug );
		if ( '' === $token ) {
			unset( $options['channel_tokens'][ $slug ] );
		} else {
			$options['channel_tokens'][ $slug ] = $this->sanitize_api_key( $token );
		}
		$this->save_options( $options );
	}

	/**
	 * Channel targets (chat ids).
	 *
	 * @param string $slug Channel slug.
	 * @return string[]
	 */
	public function get_channel_targets( string $slug ): array {
		$options = $this->get_options();
		$stored  = isset( $options['channel_targets'] ) && is_array( $options['channel_targets'] ) ? $options['channel_targets'] : array();
		$slug    = sanitize_key( $slug );
		$raw     = isset( $stored[ $slug ] ) ? (string) $stored[ $slug ] : '';

		return self::parse_id_list( $raw );
	}

	/**
	 * Raw channel targets field.
	 *
	 * @param string $slug Channel slug.
	 * @return string
	 */
	public function get_channel_targets_raw( string $slug ): string {
		$options = $this->get_options();
		$stored  = isset( $options['channel_targets'] ) && is_array( $options['channel_targets'] ) ? $options['channel_targets'] : array();
		$slug    = sanitize_key( $slug );

		return isset( $stored[ $slug ] ) ? (string) $stored[ $slug ] : '';
	}

	/**
	 * Persist channel targets.
	 *
	 * @param string $slug Channel slug.
	 * @param string $raw  Comma-separated ids.
	 * @return void
	 */
	public function set_channel_targets( string $slug, string $raw ): void {
		$options = $this->get_options();
		if ( ! isset( $options['channel_targets'] ) || ! is_array( $options['channel_targets'] ) ) {
			$options['channel_targets'] = array();
		}
		$slug = sanitize_key( $slug );
		$raw  = sanitize_text_field( $raw );
		if ( '' === $raw ) {
			unset( $options['channel_targets'][ $slug ] );
		} else {
			$options['channel_targets'][ $slug ] = $raw;
		}
		$this->save_options( $options );
	}

	/**
	 * Form → Telegram settings (enabled, chat ids, per-source toggles).
	 *
	 * @return array<string, mixed>
	 */
	public function get_form_telegram_settings(): array {
		$options = $this->get_options();
		$stored  = isset( $options['form_telegram'] ) && is_array( $options['form_telegram'] ) ? $options['form_telegram'] : array();
		$sources = isset( $stored['sources'] ) && is_array( $stored['sources'] ) ? $stored['sources'] : array();

		$normalized_sources = array();
		foreach ( array( 'contact-form-7', 'wpforms', 'gravityforms' ) as $slug ) {
			$row                         = isset( $sources[ $slug ] ) && is_array( $sources[ $slug ] ) ? $sources[ $slug ] : array();
			$normalized_sources[ $slug ] = array(
				'enabled'     => ! array_key_exists( 'enabled', $row ) || ! empty( $row['enabled'] ),
				'exclude_ids' => isset( $row['exclude_ids'] ) ? sanitize_text_field( (string) $row['exclude_ids'] ) : '',
			);
		}

		return array(
			'enabled'  => ! empty( $stored['enabled'] ),
			'chat_ids' => isset( $stored['chat_ids'] ) ? (string) $stored['chat_ids'] : '',
			'sources'  => $normalized_sources,
		);
	}

	/**
	 * Persist form Telegram settings from REST.
	 *
	 * @param array<string, mixed> $input Payload.
	 * @return void
	 */
	public function set_form_telegram_settings( array $input ): void {
		$current = $this->get_form_telegram_settings();
		$sources = $current['sources'];

		if ( isset( $input['sources'] ) && is_array( $input['sources'] ) ) {
			foreach ( $input['sources'] as $slug => $row ) {
				$slug = sanitize_key( (string) $slug );
				if ( '' === $slug || ! isset( $sources[ $slug ] ) || ! is_array( $row ) ) {
					continue;
				}
				$sources[ $slug ] = array(
					'enabled'     => ! empty( $row['enabled'] ),
					'exclude_ids' => isset( $row['exclude_ids'] ) ? sanitize_text_field( (string) $row['exclude_ids'] ) : '',
				);
			}
		}

		$options                  = $this->get_options();
		$options['form_telegram'] = array(
			'enabled'  => ! empty( $input['enabled'] ),
			'chat_ids' => isset( $input['chat_ids'] ) ? sanitize_text_field( (string) $input['chat_ids'] ) : '',
			'sources'  => $sources,
		);
		$this->save_options( $options );
	}

	/**
	 * Whether form → Telegram is enabled.
	 */
	public function is_form_telegram_enabled(): bool {
		return ! empty( $this->get_form_telegram_settings()['enabled'] );
	}

	/**
	 * Whether a form source is enabled.
	 *
	 * @param string $slug Source slug.
	 */
	public function is_form_source_enabled( string $slug ): bool {
		$slug     = sanitize_key( $slug );
		$settings = $this->get_form_telegram_settings();
		$sources  = $settings['sources'];

		return isset( $sources[ $slug ] ) && ! empty( $sources[ $slug ]['enabled'] );
	}

	/**
	 * Whether a form id is excluded for a source.
	 *
	 * @param string $slug    Source slug.
	 * @param int    $form_id Form id.
	 */
	public function is_form_excluded( string $slug, int $form_id ): bool {
		if ( $form_id <= 0 ) {
			return false;
		}
		$slug     = sanitize_key( $slug );
		$settings = $this->get_form_telegram_settings();
		$sources  = $settings['sources'];
		$raw      = isset( $sources[ $slug ]['exclude_ids'] ) ? (string) $sources[ $slug ]['exclude_ids'] : '';
		$ids      = self::parse_id_list( $raw );

		return in_array( (string) $form_id, $ids, true );
	}

	/**
	 * Chat ids for form notifications.
	 * Empty form list falls back to booking Telegram chat ids.
	 *
	 * @return string[]
	 */
	public function get_form_telegram_chat_ids(): array {
		$settings = $this->get_form_telegram_settings();
		$form_ids = self::parse_id_list( (string) $settings['chat_ids'] );
		if ( array() !== $form_ids ) {
			return $form_ids;
		}

		return $this->get_channel_targets( 'telegram' );
	}

	/**
	 * Raw form chat ids field (empty means “use booking chats”).
	 */
	public function get_form_telegram_chat_ids_raw(): string {
		return (string) $this->get_form_telegram_settings()['chat_ids'];
	}
}
