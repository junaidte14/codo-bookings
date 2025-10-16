# 🗓️ CodoBookings – WordPress Booking & PMPro Integration Plugin

**CodoBookings** is a lightweight and extensible booking management plugin for WordPress that integrates seamlessly with **Paid Memberships Pro (PMPro)** to manage time-slot-based bookings, availability calendars, and automated order linking.

It’s designed for service-based businesses, consultants, or membership sites that need **slot booking + payment flow** in one place.

---

## 📋 Table of Contents

1. [Description](#-description)
2. [Key Features](#-key-features)
3. [How It Works](#-how-it-works)
4. [Installation](#-installation)
5. [Setup & Configuration](#-setup--configuration)
6. [Usage Guide](#-usage-guide)
7. [PMPro Integration](#-pmpro-integration)
8. [Shortcodes](#-shortcodes)
9. [Admin Management](#-admin-management)
10. [Customization](#-customization)
11. [Changelog](#-changelog)
12. [Developer Notes](#-developer-notes)
13. [License](#-license)

---

## 🧭 Description

**CodoBookings** provides an intuitive booking system where users can:
- Select a date and time slot based on admin-defined availability.
- Confirm the booking through **Paid Memberships Pro** checkout.
- Have their booking automatically linked to a PMPro order.

The plugin is structured to support both free and premium booking flows, powered by the flexibility of PMPro membership levels.

---

## 🚀 Key Features

✅ **Frontend Booking Calendar**
- Displays available dates and time slots.
- Past and fully booked days automatically disabled.
- Shows remaining available slots (e.g., `15 [3 slots]`).
- Booked or partially filled days are clearly indicated.

✅ **Dynamic Slot Management**
- Define slots per weekday (e.g., “Mon → 09:00,10:00,11:00”).
- Automatically hide or disable past and booked slots.
- Real-time AJAX calendar updates on month navigation.

✅ **PMPro Integration**
- Automatically creates PMPro order on successful booking.
- Bookings linked to PMPro orders via meta fields.
- Access control via PMPro membership levels.

✅ **Admin Dashboard**
- Full booking list with filters and search.
- Quick view of booking status and linked PMPro order.
- Cancel or modify bookings directly from admin.

✅ **Custom Post Type**
- `codo_booking` post type for all bookings.
- Supports meta fields:
  - `_codo_date`
  - `_codo_time`
  - `_codo_status`
  - `_codo_user_id`
  - `_codo_order_id`

✅ **Smart Availability Logic**
- Automatically greys out fully booked days in the calendar grid.
- Days partially booked display available slot count.
- Past days and cancelled bookings are excluded from availability.

---

## ⚙️ How It Works

1. **Admin defines slot availability** in plugin settings:
- Monday → 09:00, 10:00, 11:00
- Tuesday → 10:00, 11:00, 12:00

2. **User opens booking page**, selects a date, and chooses an available slot.

3. On confirmation:
- A **CodoBooking post** is created.
- A **PMPro order** is generated and linked.
- Both are visible in their respective admin pages.

4. Booked slots are **disabled automatically** and the day shows fewer available slots next time the calendar loads.

---

## 🧩 Installation

1. Upload the plugin folder to:
- /wp-content/plugins/codo-bookings/
2. Activate via **Plugins → Installed Plugins**.
3. Ensure **Paid Memberships Pro** is active.
4. Visit **Bookings → Settings** to define time slots per weekday.

---

## 🔧 Setup & Configuration

### Step 1: Define Available Slots
Navigate to:
Bookings → Settings
Then add weekday slot timings in the following format:

| Day | Slots |
|-----|-------|
| Mon | 09:00, 10:00, 11:00 |
| Tue | 10:00, 11:00 |
| Wed | 09:00, 10:00, 11:00, 12:00 |

All times are treated as string labels and displayed exactly as entered.

---

### Step 2: Add Booking Form to a Page

Add this shortcode to any WordPress page:
```html
[codo_booking_form]
This displays:
- Calendar grid with available dates.
- Time slots for the selected day.
- Confirmation button linked to PMPro checkout.

---

### Step 3: Connect to PMPro

- When a user completes checkout, an associated booking post is created.
- Bookings and orders are linked automatically using meta fields.
- Admin can view order directly from the booking details page.

---

## 💳 PMPro Integration

- Meta `_codo_order_id` holds the PMPro order ID.
- Booking admin table includes direct link to PMPro order.
- Hooks available for customization:  
  - `codo_booking_before_checkout`  
  - `codo_booking_after_checkout`

---

## 🧩 Shortcodes

| Shortcode | Description |
|------------|-------------|
| `[codo_my_bookings]` | Shows a simple table of the current user's bookings. |
| `[codo_booking_levels]` | Display PMPro membership levels with bookings enabled in a 3-column layout. |

---

## 🧑‍💼 Admin Management

### Bookings List
Located under `Dashboard → Bookings`, columns include:
- Date
- Time
- User
- Status
- Linked PMPro Order

### Status Management
- **Pending**, **Confirmed**, **Cancelled**
- Cancelled bookings are excluded from slot counts and availability.

---

## 🧠 Customization

**Filters:**
- `codo_booking_slots` – Modify slot list per day.
- `codo_booking_save_meta` – Add or modify booking metadata.
- `codo_booking_display_label` – Customize day label in calendar.

**Actions:**
- `codo_booking_created` – Fires when a booking is created.
- `codo_booking_cancelled` – Fires when a booking is cancelled.

---

## 🧾 Changelog

### 1.0.0 (Initial Release)
- Calendar-based booking interface.
- Integrated PMPro order creation.
- Weekday slot management.
- Booked slot disabling and availability counting.

---

## 📜 License

Released under [GPL-2.0+ License](https://www.gnu.org/licenses/gpl-2.0.html)  
© 2025 **Codoplex**

---

## 🌐 References

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)  
- [Paid Memberships Pro Docs](https://www.paidmembershipspro.com/documentation/)  
- [WordPress REST API Handbook](https://developer.wordpress.org/rest-api/)  
- [WP_Query Meta Parameters](https://developer.wordpress.org/reference/classes/wp_query/#custom-field-post-meta-parameters)  
- [PHP DateTime Manual](https://www.php.net/manual/en/book.datetime.php)
