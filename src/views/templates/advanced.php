<?php
/**
 * Advanced booking board (doctor | calendar | times).
 *
 * @package ForWP\Booking
 *
 * @var string               $forwp_booking_provider Provider slug.
 * @var string               $forwp_booking_uid      Unique instance id.
 * @var string               $forwp_booking_flow     staff|date|service default tab
 * @var array<string,string> $forwp_booking_copy     Front-end copy.
 */

defined( 'ABSPATH' ) || exit;

$copy        = is_array( $forwp_booking_copy ) ? $forwp_booking_copy : array();
$flow        = isset( $forwp_booking_flow ) ? (string) $forwp_booking_flow : 'staff';
$desc        = isset( $copy['offerings_description'] ) ? (string) $copy['offerings_description'] : '';
$tab_service = isset( $copy['tab_service'] ) ? (string) $copy['tab_service'] : '';
$tab_date    = isset( $copy['tab_date'] ) ? (string) $copy['tab_date'] : '';
$tab_staff   = isset( $copy['tab_staff'] ) ? (string) $copy['tab_staff'] : '';
?>
<div
	class="forwp-booking forwp-booking--calendar forwp-booking--advanced"
	id="<?php echo esc_attr( $forwp_booking_uid ); ?>"
	data-forwp-booking
	data-forwp-provider="<?php echo esc_attr( $forwp_booking_provider ); ?>"
	data-forwp-template="advanced"
	data-forwp-flow="<?php echo esc_attr( $flow ); ?>"
	data-forwp-calendar="on"
>
	<p class="forwp-booking__status" data-forwp-status hidden></p>

	<div class="forwp-booking__chooser" data-forwp-chooser>
		<div class="forwp-booking__tabs" role="tablist">
			<button
				type="button"
				class="forwp-booking__tab<?php echo 'service' === $flow ? ' is-active' : ''; ?>"
				role="tab"
				data-forwp-tab="service"
				aria-selected="<?php echo 'service' === $flow ? 'true' : 'false'; ?>"
			><?php echo esc_html( $tab_service ); ?></button>
			<button
				type="button"
				class="forwp-booking__tab<?php echo 'staff' === $flow ? ' is-active' : ''; ?>"
				role="tab"
				data-forwp-tab="staff"
				aria-selected="<?php echo 'staff' === $flow ? 'true' : 'false'; ?>"
			><?php echo esc_html( $tab_staff ); ?></button>
			<button
				type="button"
				class="forwp-booking__tab<?php echo 'date' === $flow ? ' is-active' : ''; ?>"
				role="tab"
				data-forwp-tab="date"
				aria-selected="<?php echo 'date' === $flow ? 'true' : 'false'; ?>"
			><?php echo esc_html( $tab_date ); ?></button>
		</div>
		<section class="forwp-booking__step is-active" data-forwp-step="calendar">
			<div class="forwp-booking__board">
				<aside class="forwp-booking__aside">
					<p class="forwp-booking__aside-kicker" data-forwp-list-title><?php echo esc_html( 'service' === $flow ? ( isset( $copy['select_service'] ) ? $copy['select_service'] : '' ) : ( isset( $copy['offerings_title'] ) ? $copy['offerings_title'] : '' ) ); ?></p>
					<button type="button" class="forwp-booking__doctor-chip" data-forwp-picked-service hidden>
						<span class="forwp-booking__avatar forwp-booking__avatar--sm" data-forwp-picked-service-initials aria-hidden="true"></span>
						<span data-forwp-picked-service-label></span>
					</button>
					<div class="forwp-booking__doctors" data-forwp-doctors hidden></div>
					<div class="forwp-booking__identity">
						<span class="forwp-booking__avatar forwp-booking__avatar--lg" data-forwp-selected-initials aria-hidden="true"></span>
						<h3 class="forwp-booking__identity-name" data-forwp-selected-name></h3>
						<?php if ( '' !== $desc ) : ?>
							<p class="forwp-booking__lead"><?php echo esc_html( $desc ); ?></p>
						<?php endif; ?>
					</div>
				</aside>
				<div class="forwp-booking__month">
					<div class="forwp-booking__month-nav">
						<h4 class="forwp-booking__month-label" data-forwp-month-label></h4>
						<div class="forwp-booking__month-arrows">
							<button type="button" class="forwp-booking__nav" data-forwp-prev-month aria-label="<?php echo esc_attr__( 'Previous month', '4wp-booking' ); ?>">
								<svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path d="M10.2 3.2 5.4 8l4.8 4.8" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
							</button>
							<button type="button" class="forwp-booking__nav" data-forwp-next-month aria-label="<?php echo esc_attr__( 'Next month', '4wp-booking' ); ?>">
								<svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path d="M5.8 3.2 10.6 8l-4.8 4.8" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
							</button>
						</div>
					</div>
					<div class="forwp-booking__weekdays" data-forwp-weekdays></div>
					<div class="forwp-booking__days" data-forwp-days></div>
				</div>
				<div class="forwp-booking__times">
					<h4 class="forwp-booking__times-title" data-forwp-times-title><?php echo esc_html( isset( $copy['select_date'] ) ? $copy['select_date'] : '' ); ?></h4>
					<div class="forwp-booking__slots" data-forwp-slots></div>
				</div>
			</div>
		</section>
	</div>

	<?php include FORWP_BOOKING_PATH . 'src/views/templates/partials/guest-form.php'; ?>
</div>
