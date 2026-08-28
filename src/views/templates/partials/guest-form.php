<?php
/**
 * Guest details + done steps.
 *
 * @package ForWP\Booking
 *
 * @var string               $forwp_booking_uid Unique instance id.
 * @var array<string,string> $copy              Front-end copy.
 */

defined( 'ABSPATH' ) || exit;
?>
	<section class="forwp-booking__step" data-forwp-step="details" hidden>
		<button type="button" class="forwp-booking__back" data-forwp-back="calendar"><?php echo esc_html( isset( $copy['back'] ) ? $copy['back'] : '' ); ?></button>
		<h3 class="forwp-booking__title"><?php echo esc_html( isset( $copy['your_details'] ) ? $copy['your_details'] : '' ); ?></h3>
		<div class="forwp-booking__summary" data-forwp-summary>
			<div class="forwp-booking__summary-item">
				<span class="forwp-booking__summary-icon" aria-hidden="true">
					<svg width="16" height="16" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm0 1.5c-2.7 0-5 1.4-5 3.1V14h10v-1.4c0-1.7-2.3-3.1-5-3.1Z" fill="currentColor"/></svg>
				</span>
				<span data-forwp-summary-doctor></span>
			</div>
			<div class="forwp-booking__summary-item">
				<span class="forwp-booking__summary-icon" aria-hidden="true">
					<svg width="16" height="16" viewBox="0 0 16 16"><path d="M4 2.5h8A1.5 1.5 0 0 1 13.5 4v8A1.5 1.5 0 0 1 12 13.5H4A1.5 1.5 0 0 1 2.5 12V4A1.5 1.5 0 0 1 4 2.5ZM4 6h8v6H4V6Z" fill="currentColor"/></svg>
				</span>
				<span data-forwp-summary-date></span>
			</div>
			<div class="forwp-booking__summary-item">
				<span class="forwp-booking__summary-icon" aria-hidden="true">
					<svg width="16" height="16" viewBox="0 0 16 16"><path d="M8 1.5a6.5 6.5 0 1 0 0 13 6.5 6.5 0 0 0 0-13ZM8 4v4.2l2.6 1.5-.7 1.2L6.5 9V4H8Z" fill="currentColor"/></svg>
				</span>
				<span data-forwp-summary-time></span>
			</div>
		</div>
		<form class="forwp-booking__form" data-forwp-form novalidate>
			<p class="forwp-booking__honeypot">
				<label><?php echo esc_html__( 'Website', '4wp-booking' ); ?>
					<input type="text" name="website" value="" tabindex="-1" autocomplete="off" />
				</label>
			</p>
			<div class="forwp-booking__fields">
				<div class="forwp-booking__field" data-field="first_name">
					<label for="<?php echo esc_attr( $forwp_booking_uid ); ?>-first-name"><?php echo esc_html( isset( $copy['first_name'] ) ? $copy['first_name'] : '' ); ?> <span class="forwp-booking__req" aria-hidden="true">*</span></label>
					<input
						id="<?php echo esc_attr( $forwp_booking_uid ); ?>-first-name"
						type="text"
						name="first_name"
						autocomplete="given-name"
						maxlength="50"
						aria-required="true"
						aria-describedby="<?php echo esc_attr( $forwp_booking_uid ); ?>-first-name-error"
					/>
					<p class="forwp-booking__field-error" id="<?php echo esc_attr( $forwp_booking_uid ); ?>-first-name-error" data-error hidden></p>
				</div>
				<div class="forwp-booking__field" data-field="last_name">
					<label for="<?php echo esc_attr( $forwp_booking_uid ); ?>-last-name"><?php echo esc_html( isset( $copy['last_name'] ) ? $copy['last_name'] : '' ); ?> <span class="forwp-booking__req" aria-hidden="true">*</span></label>
					<input
						id="<?php echo esc_attr( $forwp_booking_uid ); ?>-last-name"
						type="text"
						name="last_name"
						autocomplete="family-name"
						maxlength="50"
						aria-required="true"
						aria-describedby="<?php echo esc_attr( $forwp_booking_uid ); ?>-last-name-error"
					/>
					<p class="forwp-booking__field-error" id="<?php echo esc_attr( $forwp_booking_uid ); ?>-last-name-error" data-error hidden></p>
				</div>
				<div class="forwp-booking__field" data-field="phone">
					<label for="<?php echo esc_attr( $forwp_booking_uid ); ?>-phone"><?php echo esc_html( isset( $copy['phone'] ) ? $copy['phone'] : '' ); ?> <span class="forwp-booking__req" aria-hidden="true">*</span></label>
					<input
						id="<?php echo esc_attr( $forwp_booking_uid ); ?>-phone"
						type="tel"
						name="phone"
						autocomplete="tel"
						inputmode="tel"
						placeholder="+380"
						aria-required="true"
						aria-describedby="<?php echo esc_attr( $forwp_booking_uid ); ?>-phone-error"
					/>
					<p class="forwp-booking__field-error" id="<?php echo esc_attr( $forwp_booking_uid ); ?>-phone-error" data-error hidden></p>
				</div>
				<div class="forwp-booking__field" data-field="email">
					<label for="<?php echo esc_attr( $forwp_booking_uid ); ?>-email"><?php echo esc_html( isset( $copy['email'] ) ? $copy['email'] : '' ); ?></label>
					<input
						id="<?php echo esc_attr( $forwp_booking_uid ); ?>-email"
						type="email"
						name="email"
						autocomplete="email"
						inputmode="email"
						aria-describedby="<?php echo esc_attr( $forwp_booking_uid ); ?>-email-error"
					/>
					<p class="forwp-booking__field-error" id="<?php echo esc_attr( $forwp_booking_uid ); ?>-email-error" data-error hidden></p>
				</div>
				<div class="forwp-booking__field forwp-booking__field--full" data-field="comment">
					<label for="<?php echo esc_attr( $forwp_booking_uid ); ?>-comment"><?php echo esc_html( isset( $copy['comment'] ) ? $copy['comment'] : '' ); ?></label>
					<textarea
						id="<?php echo esc_attr( $forwp_booking_uid ); ?>-comment"
						name="comment"
						rows="3"
						maxlength="400"
					></textarea>
				</div>
			</div>
			<button type="submit" class="forwp-booking__submit"><?php echo esc_html( isset( $copy['submit'] ) ? $copy['submit'] : '' ); ?></button>
		</form>
	</section>

	<section class="forwp-booking__step forwp-booking__step--done" data-forwp-step="done" hidden>
		<div class="forwp-booking__done-icon" aria-hidden="true">
			<svg width="28" height="28" viewBox="0 0 28 28"><path d="M12.1 18.4 8 14.3l1.4-1.4 2.7 2.7 6.5-6.5L20 10.5l-7.9 7.9Z" fill="currentColor"/></svg>
		</div>
		<h3 class="forwp-booking__title"><?php echo esc_html( isset( $copy['done_title'] ) ? $copy['done_title'] : '' ); ?></h3>
		<p class="forwp-booking__done" data-forwp-done></p>
	</section>
