=== 4WP Booking ===
Contributors: 4wpdev
Tags: booking, appointments, calendar, telegram, gutenberg
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.4.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Native appointment calendar for WordPress — Gutenberg, shortcode, and Elementor. Optional ClinicCards provider and Telegram alerts.

== Description ==

**4WP Booking** lets visitors pick a service, a date, a time, and submit their details. Requests go through WordPress. Provider API keys and bot tokens stay on the server.

A plugin by [4wp.dev](https://4wp.dev/). **4WP** is our project brand; the letters "WP" appear only as part of that brand name, not as a reference to WordPress. This plugin is **not** affiliated with, endorsed, or sponsored by **WordPress** or by **ClinicCards** / Cliniccards Corp.

= How it works =

1. Activate **4WP Booking**.
2. Open **4WP Booking** in wp-admin: choose a provider, set notifications, save.
3. Place the **4WP Booking** block, the `[forwp_booking]` shortcode, or the Elementor widget.
4. Visitors book through the calendar template.

= Key features =

* Pluggable providers (ClinicCards live; Google Calendar and Calendly planned)
* Calendar booking template (service → date → time → guest details)
* Gutenberg block, shortcode, optional Elementor widget
* Notifications tab: email and Telegram for bookings; optional form → Telegram (Contact Form 7, WPForms, Gravity Forms)
* Server-side API calls; credentials never sent to the browser

= Privacy =

Guest name, phone, email, and optional comment are sent to the selected provider only when the visitor submits the booking form. When Telegram or email notifications are enabled, the same booking (or form) fields are sent to those channels. API keys and bot tokens are stored as WordPress options and are not exposed in front-end HTML or JavaScript.

== External services ==

= ClinicCards =

When the **ClinicCards** provider is selected and a visitor uses a booking form, WordPress sends **server-side** HTTPS requests to `https://cliniccards.com/api` (staff, cabinets, schedule, patients, and visits). Requests include the clinic API token from plugin settings and the booking payload (name, phone, email, date, time).

This service is provided by **Cliniccards Corp**:

* Terms of use: https://cliniccards.com/
* Privacy policy: https://cliniccards.com/
* API documentation: https://cliniccards.com/api

= Telegram Bot API =

When Telegram notifications are enabled (bookings and/or form submissions), WordPress sends **server-side** HTTPS requests to `https://api.telegram.org/` to deliver messages to the chat IDs you configure. Requests include your bot token and the message text (booking or form fields).

Telegram is operated by Telegram FZ-LLC / Telegram Messenger Inc. See https://telegram.org/privacy and https://core.telegram.org/bots/api.

== Blocks ==

* forwp/booking — Appointment calendar (service, date, time, guest details)

== Installation ==

1. Upload the plugin to `/wp-content/plugins/4wp-booking/` or install from the Plugins screen.
2. Activate **4WP Booking**.
3. Go to **4WP Booking**, configure a provider and Notifications as needed.
4. Insert the block, shortcode `[forwp_booking]`, or the Elementor widget.

== Frequently Asked Questions ==

= Does this require Elementor? =

No. Elementor is optional. The shortcode and Gutenberg block work without it.

= Is this an official WordPress or ClinicCards product? =

No. 4WP Booking is an independent plugin from 4wp.dev. It is not affiliated with WordPress or with ClinicCards. ClinicCards is an optional third-party CRM connected only via their public API when you choose that provider.

= Can form plugins send to Telegram? =

Yes. On the Notifications tab you can forward Contact Form 7, WPForms, and Gravity Forms submissions to Telegram using the same bot token (optional separate chat IDs, with fallback to booking chats).

== Changelog ==

= 0.4.1 =
* WordPress.org T1: `Tested up to` only in readme; omit bundled translation binaries from the release ZIP.

= 0.4.0 =
* Notifications tab: email and Telegram for bookings; form → Telegram for CF7, WPForms, and Gravity Forms.
* Trademark disclaimers clarified for WordPress and ClinicCards.

= 0.3.0 =
* Advanced board: service, doctor, and date tabs; occupancy calendar on the service tab.

= 0.1.0 =
* Initial release: ClinicCards provider, calendar template, block, shortcode, Elementor widget.

== Development ==

Source: https://github.com/4wpdev/4wp-booking

Admin UI assets: `npm install && npm run build` (requires Node.js). PHP quality: `composer install && composer test`.

== Upgrade Notice ==

= 0.4.1 =
Directory review fixes: Tested up to header cleanup and release package without locale binaries.

= 0.4.0 =
Adds the Notifications tab (email, Telegram, optional form forwarding). Configure channels under 4WP Booking → Notifications after update.
