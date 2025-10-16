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
