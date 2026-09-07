<?php
/**
 * Calendar booking markup.
 *
 * @package ForWP\Booking
 *
 * @var string               $forwp_booking_provider Provider slug.
 * @var string               $forwp_booking_uid      Unique instance id.
 * @var string               $forwp_booking_flow     staff|date|service default tab
 * @var array<string,string> $forwp_booking_copy     Front-end copy.
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $forwp_booking_copy ) || ! is_array( $forwp_booking_copy ) ) {
	$forwp_booking_copy = array();
}
$forwp_booking_flow        = isset( $forwp_booking_flow ) ? (string) $forwp_booking_flow : 'staff';
$forwp_booking_desc        = isset( $forwp_booking_copy['offerings_description'] ) ? (string) $forwp_booking_copy['offerings_description'] : '';
$forwp_booking_tab_service = isset( $forwp_booking_copy['tab_service'] ) ? (string) $forwp_booking_copy['tab_service'] : '';
$forwp_booking_tab_date    = isset( $forwp_booking_copy['tab_date'] ) ? (string) $forwp_booking_copy['tab_date'] : '';
$forwp_booking_tab_staff   = isset( $forwp_booking_copy['tab_staff'] ) ? (string) $forwp_booking_copy['tab_staff'] : '';
?>
<div
	class="forwp-booking forwp-booking--calendar"
	id="<?php echo esc_attr( $forwp_booking_uid ); ?>"
	data-forwp-booking
	data-forwp-provider="<?php echo esc_attr( $forwp_booking_provider ); ?>"
	data-forwp-template="calendar"
	data-forwp-flow="<?php echo esc_attr( $forwp_booking_flow ); ?>"
>
	<p class="forwp-booking__status" data-forwp-status hidden></p>

	<div class="forwp-booking__chooser" data-forwp-chooser>
		<div class="forwp-booking__tabs" role="tablist">
			<button
				type="button"
				class="forwp-booking__tab<?php echo 'service' === $forwp_booking_flow ? ' is-active' : ''; ?>"
				role="tab"
				data-forwp-tab="service"
				aria-selected="<?php echo 'service' === $forwp_booking_flow ? 'true' : 'false'; ?>"
			><?php echo esc_html( $forwp_booking_tab_service ); ?></button>
			<button
				type="button"
				class="forwp-booking__tab<?php echo 'staff' === $forwp_booking_flow ? ' is-active' : ''; ?>"
				role="tab"
				data-forwp-tab="staff"
				aria-selected="<?php echo 'staff' === $forwp_booking_flow ? 'true' : 'false'; ?>"
			><?php echo esc_html( $forwp_booking_tab_staff ); ?></button>
			<button
				type="button"
				class="forwp-booking__tab<?php echo 'date' === $forwp_booking_flow ? ' is-active' : ''; ?>"
				role="tab"
				data-forwp-tab="date"
				aria-selected="<?php echo 'date' === $forwp_booking_flow ? 'true' : 'false'; ?>"
			><?php echo esc_html( $forwp_booking_tab_date ); ?></button>
		</div>

		<section class="forwp-booking__step<?php echo 'date' === $forwp_booking_flow ? '' : ' is-active'; ?>" data-forwp-step="offerings"<?php echo 'date' === $forwp_booking_flow ? ' hidden' : ''; ?>>
			<h3 class="forwp-booking__title" data-forwp-list-title><?php echo esc_html( 'service' === $forwp_booking_flow ? ( isset( $forwp_booking_copy['select_service'] ) ? $forwp_booking_copy['select_service'] : '' ) : ( isset( $forwp_booking_copy['offerings_title'] ) ? $forwp_booking_copy['offerings_title'] : '' ) ); ?></h3>
			<button type="button" class="forwp-booking__doctor-chip" data-forwp-picked-service hidden>
				<span class="forwp-booking__avatar forwp-booking__avatar--sm" data-forwp-picked-service-initials aria-hidden="true"></span>
				<span data-forwp-picked-service-label></span>
			</button>
			<div class="forwp-booking__offerings" data-forwp-offerings></div>
			<?php if ( '' !== $forwp_booking_desc ) : ?>
				<p class="forwp-booking__lead"><?php echo esc_html( $forwp_booking_desc ); ?></p>
			<?php endif; ?>
		</section>

		<section class="forwp-booking__step<?php echo 'date' === $forwp_booking_flow ? ' is-active' : ''; ?>" data-forwp-step="calendar"<?php echo 'date' === $forwp_booking_flow ? '' : ' hidden'; ?>>
			<button type="button" class="forwp-booking__doctor-chip" data-forwp-clear-doctor hidden>
				<span class="forwp-booking__avatar forwp-booking__avatar--sm" data-forwp-offering-initials aria-hidden="true"></span>
				<span data-forwp-offering-label><?php echo 'date' === $forwp_booking_flow ? esc_html( isset( $forwp_booking_copy['select_date'] ) ? $forwp_booking_copy['select_date'] : '' ) : ''; ?></span>
			</button>
			<?php if ( '' !== $forwp_booking_desc ) : ?>
				<p class="forwp-booking__lead" data-forwp-calendar-lead<?php echo 'date' === $forwp_booking_flow ? '' : ' hidden'; ?>><?php echo esc_html( $forwp_booking_desc ); ?></p>
			<?php endif; ?>
			<div class="forwp-booking__picker">
				<div class="forwp-booking__panel forwp-booking__month">
					<div class="forwp-booking__month-nav">
						<button type="button" class="forwp-booking__nav" data-forwp-prev-month aria-label="<?php echo esc_attr__( 'Previous month', '4wp-booking' ); ?>">
							<svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path d="M10.2 3.2 5.4 8l4.8 4.8" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
						</button>
						<h4 class="forwp-booking__month-label" data-forwp-month-label></h4>
						<button type="button" class="forwp-booking__nav" data-forwp-next-month aria-label="<?php echo esc_attr__( 'Next month', '4wp-booking' ); ?>">
							<svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path d="M5.8 3.2 10.6 8l-4.8 4.8" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
						</button>
					</div>
					<div class="forwp-booking__weekdays" data-forwp-weekdays></div>
					<div class="forwp-booking__days" data-forwp-days></div>
				</div>
				<div class="forwp-booking__panel forwp-booking__times">
					<h4 class="forwp-booking__times-title"><?php echo esc_html( isset( $forwp_booking_copy['select_time'] ) ? $forwp_booking_copy['select_time'] : '' ); ?></h4>
					<div class="forwp-booking__slots" data-forwp-slots></div>
				</div>
			</div>
		</section>
	</div>

	<?php require FORWP_BOOKING_PATH . 'src/views/templates/partials/guest-form.php'; ?>
</div>
