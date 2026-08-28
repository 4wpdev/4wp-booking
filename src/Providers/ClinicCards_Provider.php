<?php
/**
 * ClinicCards CRM booking provider.
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking\Providers;

use ForWP\Booking\Admin_Settings;
use ForWP\Booking\Contracts\Booking_Credential_Help_Interface;
use ForWP\Booking\Contracts\Booking_Provider_Interface;
use ForWP\Booking\Slot_Engine;

defined( 'ABSPATH' ) || exit;

/**
 * Live provider using https://cliniccards.com/api
 */
final class ClinicCards_Provider implements Booking_Provider_Interface, Booking_Credential_Help_Interface {

	public const SLUG = 'cliniccards';

	private const API_BASE = 'https://cliniccards.com/api';

	/**
	 * Provider slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return self::SLUG;
	}

	/**
	 * Human-readable provider name.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'ClinicCards', '4wp-booking' );
	}

	/**
	 * Help text under the API key field.
	 *
	 * @return string
	 */
	public function get_api_key_help_intro(): string {
		return __( 'Create a key in ClinicCards: Settings → Other → Clinic settings → API key.', '4wp-booking' );
	}

	/**
	 * API documentation URL.
	 *
	 * @return string
	 */
	public function get_api_key_docs_url(): string {
		return 'https://cliniccards.com/api';
	}

	/**
	 * External link label.
	 *
	 * @return string
	 */
	public function get_api_key_docs_link_label(): string {
		return __( 'ClinicCards API documentation', '4wp-booking' );
	}

	/**
	 * Whether this provider is implemented.
	 *
	 * @return bool
	 */
	public function is_implemented(): bool {
		return true;
	}

	/**
	 * Whether an API token is saved.
	 *
	 * @return bool
	 */
	public function is_ready(): bool {
		return '' !== Admin_Settings::instance()->get_provider_token( self::SLUG );
	}

	/**
	 * Bookable services per specialist from online booking items.
	 *
	 * @return array<int, array<string, mixed>>|\WP_Error
	 */
	public function get_offerings() {
		$items = $this->get_cached( 'booking-items', 10 * MINUTE_IN_SECONDS );
		if ( is_wp_error( $items ) ) {
			return $items;
		}

		$roles     = $this->staff_roles();
		$offerings = array();
		if ( is_array( $items ) ) {
			foreach ( $items as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$specialist_id   = isset( $row['specialist_id'] ) ? (string) $row['specialist_id'] : '';
				$specialist_name = isset( $row['specialist_name'] ) ? (string) $row['specialist_name'] : '';
				if ( '' !== $specialist_id && ! $this->is_bookable_role( $roles[ $specialist_id ] ?? '' ) ) {
					continue;
				}
				$booking_items = isset( $row['booking_items'] ) && is_array( $row['booking_items'] ) ? $row['booking_items'] : array();
				foreach ( $booking_items as $item ) {
					if ( ! is_array( $item ) ) {
						continue;
					}
					$price_id = isset( $item['price_item_id'] ) ? (string) $item['price_item_id'] : '';
					if ( '' === $specialist_id || '' === $price_id ) {
						continue;
					}
					$duration = isset( $item['execution_time'] ) ? absint( $item['execution_time'] ) : 30;
					if ( $duration < 5 ) {
						$duration = 30;
					}
					$name        = isset( $item['price_item_name'] ) ? (string) $item['price_item_name'] : '';
					$offerings[] = array(
						'id'                => $specialist_id . ':' . $price_id,
						'label'             => '' !== $specialist_name ? $specialist_name : $name,
						'specialist_label'  => $specialist_name,
						'service_label'     => $name,
						'duration'          => $duration,
						'specialist_id'     => $specialist_id,
						'service_id'        => $price_id,
					);
				}
			}
		}

		if ( array() !== $offerings ) {
			return $offerings;
		}

		return $this->offerings_from_staff_with_services();
	}

	/**
	 * Free slots for a specialist in a date range.
	 *
	 * @param string $offering_id Offering id.
	 * @param string $from        YYYY-MM-DD.
	 * @param string $to          YYYY-MM-DD.
	 * @return array<int, array{date: string, time_start: string, time_end: string, cabinet_id: string}>|\WP_Error
	 */
	public function get_slots( string $offering_id, string $from, string $to ) {
		$parsed = $this->parse_offering_id( $offering_id );
		if ( is_wp_error( $parsed ) ) {
			return $parsed;
		}

		$duration = $this->duration_for_offering( $offering_id );
		$settings = $this->get_cached( 'booking-settings', 10 * MINUTE_IN_SECONDS );
		$step     = $duration;
		if ( ! is_wp_error( $settings ) && is_array( $settings ) && isset( $settings['booking_interval'] ) ) {
			$interval = absint( $settings['booking_interval'] );
			if ( $interval >= 5 ) {
				$step = $interval;
			}
		}

		$query = array(
			'from' => $from,
			'to'   => $to,
		);

		$shifts = $this->get_cached( 'schedule-shifts', MINUTE_IN_SECONDS, $query );
		$visits = $this->get_cached( 'visits', MINUTE_IN_SECONDS, $query );
		$spaces = $this->get_cached( 'schedule-spaces', MINUTE_IN_SECONDS, $query );

		if ( is_wp_error( $shifts ) ) {
			return $shifts;
		}
		if ( is_wp_error( $visits ) ) {
			$visits = array();
		}
		if ( is_wp_error( $spaces ) ) {
			$spaces = array();
		}

		$timezone = wp_timezone();
		$now      = time();
		$windows  = array();

		foreach ( (array) $shifts as $shift ) {
			if ( ! is_array( $shift ) ) {
				continue;
			}
			$doctor_id = isset( $shift['doctor_id'] ) ? (string) $shift['doctor_id'] : '';
			if ( $parsed['specialist_id'] !== $doctor_id ) {
				continue;
			}
			$start = $this->parse_local_datetime( isset( $shift['shift_start'] ) ? (string) $shift['shift_start'] : '', $timezone );
			$end   = $this->parse_local_datetime( isset( $shift['shift_end'] ) ? (string) $shift['shift_end'] : '', $timezone );
			if ( null === $start || null === $end ) {
				continue;
			}
			$windows[] = array(
				'start'      => $start,
				'end'        => $end,
				'cabinet_id' => isset( $shift['schedule_cabinets_id'] ) ? (string) $shift['schedule_cabinets_id'] : '',
			);
		}

		$busy = array();
		foreach ( array_merge( (array) $visits, (array) $spaces ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$doctor_id = '';
			if ( isset( $row['doctor_id'] ) ) {
				$doctor_id = (string) $row['doctor_id'];
			} elseif ( isset( $row['created_by_id'] ) ) {
				$doctor_id = (string) $row['created_by_id'];
			}
			if ( '' !== $doctor_id && $parsed['specialist_id'] !== $doctor_id ) {
				continue;
			}
			$start_raw = isset( $row['visit_start'] ) ? (string) $row['visit_start'] : ( isset( $row['space_start'] ) ? (string) $row['space_start'] : '' );
			$end_raw   = isset( $row['visit_end'] ) ? (string) $row['visit_end'] : ( isset( $row['space_end'] ) ? (string) $row['space_end'] : '' );
			$start     = $this->parse_local_datetime( $start_raw, $timezone );
			$end       = $this->parse_local_datetime( $end_raw, $timezone );
			if ( null === $start || null === $end ) {
				continue;
			}
			$busy[] = array(
				'start' => $start,
				'end'   => $end,
			);
		}

		$generated        = Slot_Engine::generate( $windows, $busy, $duration, $step, $now );
		$fallback_cabinet = $this->first_cabinet_id();
		$out              = array();

		foreach ( $generated as $slot ) {
			$cabinet_id = $fallback_cabinet;
			foreach ( $windows as $window ) {
				if ( $slot['start'] >= $window['start'] && $slot['end'] <= $window['end'] && '' !== $window['cabinet_id'] ) {
					$cabinet_id = $window['cabinet_id'];
					break;
				}
			}
			$out[] = array(
				'date'       => wp_date( 'Y-m-d', $slot['start'], $timezone ),
				'time_start' => wp_date( 'H:i', $slot['start'], $timezone ),
				'time_end'   => wp_date( 'H:i', $slot['end'], $timezone ),
				'cabinet_id' => $cabinet_id,
			);
		}

		return $out;
	}

	/**
	 * Create patient if needed and add a BOOKING visit.
	 *
	 * @param array<string, mixed> $input Payload.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function create_booking( array $input ) {
		$offering_id = isset( $input['offering_id'] ) ? sanitize_text_field( (string) $input['offering_id'] ) : '';
		$date        = isset( $input['date'] ) ? sanitize_text_field( (string) $input['date'] ) : '';
		$time_start  = isset( $input['time_start'] ) ? sanitize_text_field( (string) $input['time_start'] ) : '';
		$parsed      = $this->parse_offering_id( $offering_id );
		if ( is_wp_error( $parsed ) ) {
			return $parsed;
		}

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) || ! preg_match( '/^\d{2}:\d{2}$/', $time_start ) ) {
			return new \WP_Error( 'forwp_booking_invalid_slot', __( 'Invalid date or time.', '4wp-booking' ) );
		}

		$slots = $this->get_slots( $offering_id, $date, $date );
		if ( is_wp_error( $slots ) ) {
			return $slots;
		}

		$match = null;
		foreach ( (array) $slots as $slot ) {
			if ( $slot['date'] === $date && $slot['time_start'] === $time_start ) {
				$match = $slot;
				break;
			}
		}
		if ( null === $match ) {
			return new \WP_Error( 'forwp_booking_slot_taken', __( 'That time is no longer available.', '4wp-booking' ) );
		}

		$phone = $this->digits_phone( isset( $input['phone'] ) ? (string) $input['phone'] : '' );
		if ( '' === $phone ) {
			return new \WP_Error( 'forwp_booking_phone', __( 'A phone number is required.', '4wp-booking' ) );
		}

		$patient_id = $this->find_or_create_patient( $input, $phone );
		if ( is_wp_error( $patient_id ) ) {
			return $patient_id;
		}

		$note = isset( $input['comment'] ) ? sanitize_text_field( (string) $input['comment'] ) : '';
		if ( '' === $note ) {
			$note = __( 'Online booking', '4wp-booking' );
		}

		$body = array(
			'status'     => 'BOOKING',
			'patient_id' => (string) $patient_id,
			'cabinet_id' => (string) $match['cabinet_id'],
			'doctor_id'  => $parsed['specialist_id'],
			'note'       => substr( $note, 0, 400 ),
			'date'       => $date,
			'time_start' => $time_start,
			'time_end'   => $match['time_end'],
		);

		$created = $this->request( 'POST', 'visits', array(), $body );
		if ( is_wp_error( $created ) ) {
			return $created;
		}

		return array(
			'ok'      => true,
			'date'    => $date,
			'time'    => $time_start,
			'message' => __( 'Your appointment request has been sent.', '4wp-booking' ),
		);
	}

	/**
	 * Fallback offerings from staff, crossed with price-list groups when online booking items are empty.
	 *
	 * @return array<int, array<string, mixed>>|\WP_Error
	 */
	private function offerings_from_staff_with_services() {
		$staff_offerings = $this->offerings_from_staff();
		if ( is_wp_error( $staff_offerings ) ) {
			return $staff_offerings;
		}

		$groups = $this->bookable_price_groups();
		if ( array() === $groups ) {
			return $staff_offerings;
		}

		$out = array();
		foreach ( $staff_offerings as $base ) {
			if ( ! is_array( $base ) || ! isset( $base['specialist_id'] ) ) {
				continue;
			}
			$specialist_id = (string) $base['specialist_id'];
			$label         = isset( $base['specialist_label'] ) ? (string) $base['specialist_label'] : '';
			$duration      = isset( $base['duration'] ) ? absint( $base['duration'] ) : 30;
			foreach ( $groups as $group ) {
				$out[] = array(
					'id'               => $specialist_id . ':' . $group['id'],
					'label'            => $label,
					'specialist_label' => $label,
					'service_label'    => $group['name'],
					'duration'         => $duration,
					'specialist_id'    => $specialist_id,
					'service_id'       => $group['id'],
				);
			}
		}

		return array() !== $out ? $out : $staff_offerings;
	}

	/**
	 * Clinical price-list groups (not retail products).
	 *
	 * @return array<int, array{id: string, name: string}>
	 */
	private function bookable_price_groups(): array {
		$prices = $this->get_cached( 'prices', 10 * MINUTE_IN_SECONDS );
		if ( is_wp_error( $prices ) || ! is_array( $prices ) ) {
			return array();
		}

		$groups = array();
		foreach ( $prices as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$gid   = isset( $row['group_id'] ) ? (string) $row['group_id'] : '';
			$gname = isset( $row['group_name'] ) ? trim( (string) $row['group_name'] ) : '';
			if ( '' === $gid || '' === $gname || isset( $groups[ $gid ] ) ) {
				continue;
			}
			if ( ! $this->is_bookable_price_group( $gname ) ) {
				continue;
			}
			$groups[ $gid ] = array(
				'id'   => $gid,
				'name' => $gname,
			);
		}

		return array_values( $groups );
	}

	/**
	 * Drop shop / lab groups from the booking service list.
	 *
	 * @param string $name Group name.
	 * @return bool
	 */
	private function is_bookable_price_group( string $name ): bool {
		$haystack = function_exists( 'mb_strtolower' ) ? mb_strtolower( $name, 'UTF-8' ) : strtolower( $name );
		$skip     = array( 'зубн', 'паст', 'ополіскувач', 'нитк', 'віск', 'борлаб', 'допоміжн', 'щітк' );
		foreach ( $skip as $needle ) {
			if ( function_exists( 'mb_strpos' ) ) {
				if ( false !== mb_strpos( $haystack, $needle, 0, 'UTF-8' ) ) {
					return false;
				}
			} elseif ( false !== strpos( $haystack, $needle ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Fallback offerings from staff list.
	 *
	 * @return array<int, array<string, mixed>>|\WP_Error
	 */
	private function offerings_from_staff() {
		$staff = $this->get_cached( 'staff', 10 * MINUTE_IN_SECONDS );
		if ( is_wp_error( $staff ) ) {
			return $staff;
		}

		$offerings = array();
		foreach ( (array) $staff as $member ) {
			if ( ! is_array( $member ) ) {
				continue;
			}
			$role = isset( $member['role'] ) ? strtoupper( (string) $member['role'] ) : '';
			if ( ! $this->is_bookable_role( $role ) ) {
				continue;
			}
			$id = isset( $member['doctor_id'] ) ? (string) $member['doctor_id'] : '';
			if ( '' === $id ) {
				continue;
			}
			$label       = trim( ( isset( $member['firstname'] ) ? (string) $member['firstname'] : '' ) . ' ' . ( isset( $member['lastname'] ) ? (string) $member['lastname'] : '' ) );
			$offerings[] = $this->default_offering( $id, $label );
		}

		if ( array() === $offerings ) {
			return new \WP_Error( 'forwp_booking_no_offerings', __( 'No bookable services or doctors were found.', '4wp-booking' ) );
		}

		return $offerings;
	}

	/**
	 * Staff id → role map.
	 *
	 * @return array<string, string>
	 */
	private function staff_roles(): array {
		$staff = $this->get_cached( 'staff', 10 * MINUTE_IN_SECONDS );
		if ( is_wp_error( $staff ) || ! is_array( $staff ) ) {
			return array();
		}

		$roles = array();
		foreach ( $staff as $member ) {
			if ( ! is_array( $member ) || ! isset( $member['doctor_id'] ) ) {
				continue;
			}
			$roles[ (string) $member['doctor_id'] ] = isset( $member['role'] ) ? strtoupper( (string) $member['role'] ) : '';
		}

		return $roles;
	}

	/**
	 * Whether a ClinicCards staff role can be booked.
	 *
	 * @param string $role Staff role.
	 * @return bool
	 */
	private function is_bookable_role( string $role ): bool {
		return in_array( $role, array( '', 'DOCTOR', 'SPECIALIST' ), true );
	}

	/**
	 * Offering when a doctor has no price items attached.
	 *
	 * @param string $specialist_id Specialist id.
	 * @param string $label         Display name.
	 * @return array<string, mixed>
	 */
	private function default_offering( string $specialist_id, string $label ): array {
		return array(
			'id'               => $specialist_id . ':default',
			'label'            => '' !== $label ? $label : $specialist_id,
			'specialist_label' => '' !== $label ? $label : $specialist_id,
			'service_label'    => '',
			'duration'         => 30,
			'specialist_id'    => $specialist_id,
			'service_id'       => 'default',
		);
	}

	/**
	 * Duration in minutes for an offering.
	 *
	 * @param string $offering_id Offering id.
	 * @return int
	 */
	private function duration_for_offering( string $offering_id ): int {
		$offerings = $this->get_offerings();
		if ( is_wp_error( $offerings ) ) {
			return 30;
		}
		foreach ( $offerings as $offering ) {
			if ( isset( $offering['id'] ) && $offering['id'] === $offering_id ) {
				return isset( $offering['duration'] ) ? absint( $offering['duration'] ) : 30;
			}
		}

		return 30;
	}

	/**
	 * Split offering id.
	 *
	 * @param string $offering_id Offering id.
	 * @return array{specialist_id: string, service_id: string}|\WP_Error
	 */
	private function parse_offering_id( string $offering_id ) {
		$parts = explode( ':', $offering_id, 2 );
		if ( 2 !== count( $parts ) || '' === $parts[0] ) {
			return new \WP_Error( 'forwp_booking_offering', __( 'Unknown service.', '4wp-booking' ) );
		}

		return array(
			'specialist_id' => sanitize_text_field( $parts[0] ),
			'service_id'    => sanitize_text_field( $parts[1] ),
		);
	}

	/**
	 * First cabinet id for visit create fallback.
	 *
	 * @return string
	 */
	private function first_cabinet_id(): string {
		$cabinets = $this->get_cached( 'cabinets', 10 * MINUTE_IN_SECONDS );
		if ( is_wp_error( $cabinets ) || ! is_array( $cabinets ) ) {
			return '';
		}
		foreach ( $cabinets as $cabinet ) {
			if ( is_array( $cabinet ) && isset( $cabinet['cabinet_id'] ) ) {
				return (string) $cabinet['cabinet_id'];
			}
		}

		return '';
	}

	/**
	 * Find patient by phone or create one.
	 *
	 * @param array<string, mixed> $input Guest fields.
	 * @param string               $phone Digits-only phone.
	 * @return string|\WP_Error
	 */
	private function find_or_create_patient( array $input, string $phone ) {
		$found = $this->request( 'GET', 'patients', array( 'phone' => $phone ) );
		if ( ! is_wp_error( $found ) && is_array( $found ) ) {
			foreach ( $found as $patient ) {
				if ( is_array( $patient ) && isset( $patient['patient_id'] ) ) {
					return (string) $patient['patient_id'];
				}
			}
		}

		$first = isset( $input['first_name'] ) ? sanitize_text_field( (string) $input['first_name'] ) : '';
		$last  = isset( $input['last_name'] ) ? sanitize_text_field( (string) $input['last_name'] ) : '';
		if ( '' === $first || '' === $last ) {
			return new \WP_Error( 'forwp_booking_name', __( 'First and last name are required.', '4wp-booking' ) );
		}

		$body = array(
			'firstname' => substr( $first, 0, 50 ),
			'lastname'  => substr( $last, 0, 50 ),
			'phone'     => $phone,
		);
		if ( isset( $input['email'] ) && is_email( (string) $input['email'] ) ) {
			$body['email'] = sanitize_email( (string) $input['email'] );
		}

		$created = $this->request( 'POST', 'patients', array(), $body );
		if ( is_wp_error( $created ) ) {
			return $created;
		}

		if ( is_array( $created ) ) {
			if ( isset( $created['patient_id'] ) ) {
				return (string) $created['patient_id'];
			}
			if ( isset( $created[0] ) && is_array( $created[0] ) && isset( $created[0]['patient_id'] ) ) {
				return (string) $created[0]['patient_id'];
			}
		}

		return new \WP_Error( 'forwp_booking_patient', __( 'Could not create a patient record.', '4wp-booking' ) );
	}

	/**
	 * Keep digits only for ClinicCards phone format.
	 *
	 * @param string $phone Raw phone.
	 * @return string
	 */
	private function digits_phone( string $phone ): string {
		return (string) preg_replace( '/\D+/', '', $phone );
	}

	/**
	 * Parse ClinicCards local datetime string.
	 *
	 * @param string        $value    Datetime string.
	 * @param \DateTimeZone $timezone Site timezone.
	 * @return int|null Unix timestamp.
	 */
	private function parse_local_datetime( string $value, \DateTimeZone $timezone ) {
		$value = trim( $value );
		if ( '' === $value ) {
			return null;
		}
		$dt = \DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $value, $timezone );
		if ( ! $dt ) {
			$dt = date_create_immutable( $value, $timezone );
		}

		return $dt ? $dt->getTimestamp() : null;
	}

	/**
	 * Invalidate ClinicCards GET cache (new API key / admin reload).
	 *
	 * @return void
	 */
	public static function bump_cache(): void {
		$n = (int) get_option( 'forwp_booking_cc_cache_gen', 1 );
		update_option( 'forwp_booking_cc_cache_gen', $n + 1, false );
	}

	/**
	 * Cached GET helper.
	 *
	 * @param string               $path  API path.
	 * @param int                  $ttl   Cache seconds.
	 * @param array<string, mixed> $query Query args.
	 * @return mixed|\WP_Error
	 */
	private function get_cached( string $path, int $ttl, array $query = array() ) {
		$gen    = (int) get_option( 'forwp_booking_cc_cache_gen', 1 );
		$key    = 'forwp_booking_cc_' . $gen . '_' . md5( $path . wp_json_encode( $query ) );
		$cached = get_transient( $key );
		if ( false !== $cached ) {
			return $cached;
		}

		$data = $this->request( 'GET', $path, $query );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		set_transient( $key, $data, $ttl );

		return $data;
	}

	/**
	 * HTTP to ClinicCards.
	 *
	 * @param string                    $method GET/POST.
	 * @param string                    $path   Path after /api/.
	 * @param array<string, mixed>      $query  Query args.
	 * @param array<string, mixed>|null $body   JSON body.
	 * @return mixed|\WP_Error
	 */
	private function request( string $method, string $path, array $query = array(), $body = null ) {
		if ( ! $this->is_ready() ) {
			return new \WP_Error(
				'forwp_booking_no_key',
				__( 'ClinicCards API key is not configured.', '4wp-booking' )
			);
		}

		$url = trailingslashit( self::API_BASE ) . ltrim( $path, '/' );
		if ( array() !== $query ) {
			$url = add_query_arg( $query, $url );
		}

		$args = array(
			'timeout' => 15,
			'headers' => array(
				'Token'        => Admin_Settings::instance()->get_provider_token( self::SLUG ),
				'Content-Type' => 'application/json',
			),
		);

		if ( 'GET' === $method ) {
			$response = wp_remote_get( esc_url_raw( $url ), $args );
		} else {
			$args['method'] = $method;
			if ( null !== $body ) {
				$args['body'] = wp_json_encode( $body );
			}
			$response = wp_remote_request( esc_url_raw( $url ), $args );
		}

		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'forwp_booking_transport',
				sprintf(
					/* translators: %s: transport error */
					__( 'Cannot reach ClinicCards: %s', '4wp-booking' ),
					$response->get_error_message()
				)
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = (string) wp_remote_retrieve_body( $response );
		$json = json_decode( $raw, true );
		if ( ! is_array( $json ) ) {
			return new \WP_Error( 'forwp_booking_bad_json', __( 'ClinicCards returned an unexpected response.', '4wp-booking' ) );
		}

		$result = isset( $json['result'] ) ? (string) $json['result'] : '';
		if ( 'fail' === $result || $code >= 400 ) {
			$error = isset( $json['error'] ) ? (string) $json['error'] : __( 'ClinicCards request failed.', '4wp-booking' );
			return new \WP_Error( 'forwp_booking_api', $error );
		}

		return array_key_exists( 'data', $json ) ? $json['data'] : $json;
	}
}
