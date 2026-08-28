=== 4WP Booking ===
Contributors: 4wpdev
Tags: booking, appointments, calendar, cliniccards, gutenberg
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Native appointment calendar for WordPress — Gutenberg, shortcode, and Elementor. ClinicCards is the first live provider.

== Description ==

**4WP Booking** lets visitors pick a service, a date, a time, and submit their details — the same flow as Calendly or Google Calendar appointment pages. Requests go through WordPress. Provider API keys stay on the server.

A plugin by [4wp.dev](https://4wp.dev/). **4WP** is our project brand; the letters "WP" appear only as part of that brand name, not as a reference to WordPress. This plugin is not affiliated with, endorsed, or sponsored by WordPress.

= How it works =

1. Activate **4WP Booking**.
2. Open **4WP Booking** in wp-admin, choose a provider, and save credentials.
3. Place the **4WP Booking** block, the `[forwp_booking]` shortcode, or the Elementor widget.
4. Visitors book through the calendar template.

= Key features =

* Pluggable providers (ClinicCards live; Google Calendar and Calendly planned)
* Calendar booking template (service → date → time → guest details)
* Gutenberg block, shortcode, optional Elementor widget
* Server-side API calls; credentials never sent to the browser

= Privacy =

Guest name, phone, email, and optional comment are sent to the selected provider only when the visitor submits the booking form. The provider API key is stored as a WordPress option and is not exposed in front-end HTML or JavaScript.

== External services ==

When the **ClinicCards** provider is selected and a visitor uses a booking form, WordPress sends **server-side** HTTPS requests to `https://cliniccards.com/api` (staff, cabinets, schedule, patients, and visits). Requests include the clinic API token from plugin settings and the booking payload (name, phone, email, date, time).

This service is provided by **Cliniccards Corp**:

* Terms of use: https://cliniccards.com/
* Privacy policy: https://cliniccards.com/
* API documentation: https://cliniccards.com/api

== Installation ==

1. Upload the plugin to `/wp-content/plugins/4wp-booking/` or install from the Plugins screen.
2. Activate **4WP Booking**.
3. Go to **4WP Booking**, add your ClinicCards API key (Settings → Other → Clinic settings in ClinicCards).
4. Insert the block, shortcode `[forwp_booking]`, or the Elementor widget.

== Frequently Asked Questions ==

= Does this require Elementor? =

No. Elementor is optional. The shortcode and Gutenberg block work without it.

= Is this an official ClinicCards plugin? =

No. ClinicCards is a third-party CRM. 4WP Booking is an independent product that can talk to it via their public API.

== Changelog ==

= 0.3.0 =
* Advanced board: service, doctor, and date tabs; occupancy calendar on the service tab.

= 0.1.0 =
* Initial release: ClinicCards provider, calendar template, block, shortcode, Elementor widget.

== Development ==

Source: https://github.com/4wpdev/4wp-booking

== Upgrade Notice ==

= 0.1.0 =
Initial public structure.
