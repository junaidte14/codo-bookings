=== CodoBookings ===
Contributors: junaidte14
Tags: bookings, appointments, calendar, scheduling, standalone, pmpro-extension, woocommerce, google-calendar
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

CodoBookings is a lightweight yet powerful WordPress booking management plugin designed for developers and site owners who want complete flexibility.
Originally built as a Paid Memberships Pro (PMPro) extension, it has now evolved into a standalone booking engine, providing the foundation for managing appointments, schedules, and customer interactions — without requiring any dependencies.

Future extensions (coming soon) will seamlessly integrate with:
* Paid Memberships Pro (membership-based bookings)
* WooCommerce (sell bookings as products)
* Google Calendar (sync bookings with personal or business calendars)
* Email & Notifications (customized reminders, confirmations, and admin alerts)

== Installation ==

1. Download the plugin ZIP file or clone the repository.
2. Upload the folder to `/wp-content/plugins/codobookings/`.
3. Activate CodoBookings through the WordPress admin dashboard.
4. Access the CodoBookings menu to manage calendars, bookings and settings.

== Frequently Asked Questions ==

= How do I create a calendar? =
Go to CodoBookings → Calendars in the WordPress admin and use the interface to create weekly or monthly calendars with available slots.

= How can I manage bookings? =
Bookings are managed via CodoBookings → Bookings. You can edit, cancel, or confirm bookings, and recurring bookings are automatically handled.

= Can I extend CodoBookings? =
Yes! The plugin provides developer hooks and is modularly structured for extensions like PMPro, WooCommerce, Google Calendar, and email templates.

== Changelog ==

= 1.1.0 =
* Standalone booking plugin core
* Admin dashboard widget for stats
* Booking list and management UI
* Recurring booking logic (weekly)
* Modular architecture for future extensions

= 1.0.0 =
* developed as a PMPro extension
* Booking list and management UI

== Upgrade Notice ==

Initial release.

== Additional Information ==

CodoBookings provides several developer hooks to extend its behavior:

* `codobookings_admin_overview_stats` – Extend the dashboard widget with custom stats.
* `codobookings_booking_created` – Triggered when a new booking is created.
* `codobookings_booking_status_changed` – Fires when a booking status is updated.
* `codobookings_emails_sent` – Fires when a booking confirmation email is sent.
* `codobookings_status_email_sent` – Fires when a booking status change email is sent.

Planned extensions:

| Extension | Description | Status |
|-----------|-------------|--------|
| PMPro Integration | Restrict or enable bookings based on membership level. | Coming soon |
| WooCommerce Integration | Convert bookings into WooCommerce products with checkout flow. | Coming soon |
| Google Calendar Sync | Allow users and admins to link and sync bookings to Google Calendar. | Coming soon |
| Email Templates | Customizable email notifications for bookings, cancellations, and reminders. | Coming soon |

CodoBookings is structured for scalability:
* Each functional area resides in its own file under `/includes/`.
* Hooks and filters are available for extension developers.

== License ==

This plugin is licensed under the GPLv2 or later license. You are free to use, modify, and redistribute it under the same license.

== Links ==

* Website: https://codoplex.com
