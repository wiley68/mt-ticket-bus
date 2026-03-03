# MT Ticket Bus

A comprehensive WordPress plugin for bus ticket sales management integrated with WooCommerce.

## Features

- **Bus Fleet Management**: Manage your bus fleet with detailed information about each bus (seats, layout, features)
- **Route Management**: Create and manage bus routes with start/end stations and intermediate stops
- **Schedule Management**: Set up schedules for routes with departure/arrival times
- **WooCommerce Integration**: Create virtual products linked to bus routes for ticket sales
- **Multilingual Support**: Ready for translation (English/Bulgarian)

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- WooCommerce 9.9 or higher

## Installation

1. Upload the plugin files to `/wp-content/plugins/mt-ticket-bus/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Make sure WooCommerce is installed and activated
4. Go to 'Ticket Bus' menu in WordPress admin to configure

## Database Tables

The plugin creates three database tables:

- `wp_mt_ticket_buses` - Stores bus information
- `wp_mt_ticket_routes` - Stores route information
- `wp_mt_ticket_route_schedules` - Stores schedule information linking routes and buses

## Usage

### Managing Buses

1. Go to **Ticket Bus > Buses**
2. Add new buses with details like name, registration number, total seats, seat layout, and features
3. Edit or delete existing buses

### Managing Routes

1. Go to **Ticket Bus > Routes**
2. Create routes with start station, end station, intermediate stations, distance, and duration
3. Edit or delete existing routes

### Creating Ticket Products

1. Create a new WooCommerce product
2. Set it as a virtual product
3. In the product settings, select a bus route from the "Bus Route" dropdown
4. The product will be linked to the selected route

## Future Features

- Seat selection interface on product pages
- Dynamic pricing based on route and schedule
- Order tracking system
- QR code generation for tickets
- Print functionality
- Mobile app for drivers

## Translation

The plugin is translation-ready. Translation files are located in the `/languages/` directory.

## Order emails (ticket orders)

The plugin customizes WooCommerce order emails when the order contains bus tickets:

- **Subject** – Replaced with a ticket-oriented subject (e.g. "Your bus ticket – Order #123").
- **Heading** – Replaced with "Your bus ticket".
- **Additional content** – A short message that the reservation is confirmed and that the ticket can be printed or downloaded from the order page.

This applies to the customer emails "Processing order" and "Completed order". No theme changes are required; everything is done via plugin hooks.

### Attaching the ticket PDF to the email

You can attach the ticket as a PDF to these emails in two ways:

1. **Using Dompdf (recommended)**  
   Install [Dompdf](https://github.com/dompdf/dompdf) (e.g. via Composer in a must-use plugin or in the plugin directory):

   ```bash
   composer require dompdf/dompdf
   ```

   Load the autoloader before the plugin (e.g. in a small mu-plugin that requires `vendor/autoload.php` and then the plugin). The plugin will detect Dompdf and generate a PDF from the ticket print template and attach it automatically.

2. **Using the filter**  
   Implement the filter `mt_ticket_bus_ticket_pdf_path` in your theme or a small custom plugin to return the full path to a generated PDF file:
   ```php
   add_filter('mt_ticket_bus_ticket_pdf_path', function ($path, $order) {
       // Generate your PDF and return the file path, or return null.
       return '/path/to/generated-ticket.pdf';
   }, 10, 2);
   ```

Generated PDFs (when using Dompdf) are stored in `wp-content/uploads/mt-ticket-bus-pdfs/`. You may want to clean this folder periodically.

## Changelog

### Version 1.0.15 (2026-01-26)

**New Features:**

- **Segment-based pricing (intermediate stops)** – Ticket products can now be purchased for travel between intermediate stops along a route (not only full route start → end). The plugin determines the correct segment between the selected starting and final stop and applies the corresponding price for that segment. This price is used consistently in the cart, checkout, admin Reservations view and on the printed/PDF ticket (seat price reflects the actual segment price, excluding paid extras). Order meta and printed tickets also show the selected starting and final bus stop, so it is clear which part of the route is paid.

### Version 1.0.14 (2026-01-26)

**New Features:**

- **Appearance – color palettes** – In Settings → Appearance tab, one of four preset palettes (Default / Blue, Warm / Orange, Nature / Green, Slate / Neutral) can be selected for the blocks on the product page (date/time/location selection and ticket summary). Below the palette selection, fields for each color (picker + hex) are displayed to adjust manually if necessary. The saved colors are applied via CSS variables when the blocks are visualized.

**Enhancements:**

- After saving the settings, the active tab (Settings, Permissions, or Appearance) is saved even after a reload.

### Version 1.0.13 (2026-01-26)

**New Features:**

- **Paid extras** – Admins can define optional paid extras (name, optional code, price, status) in _Ticket Bus → Extras_. Each ticket product can have a multi-select of allowed extras. On the single product (ticket) page, customers see an optional “Paid extras” block (checkboxes with name and price); selecting extras updates the displayed total (base + extras per seat × number of seats). Selected extras are sent with each ticket to the cart. Cart and checkout show extras and the adjusted line price; order item meta stores extras as JSON. Order edit screen shows the extras meta with label “Extras” and readable value (e.g. “Extra name (+price)”). Reservations admin panel shows seat price and paid extras (with prices) for the selected seat. Ticket print/PDF shows total ticket price under order number and, per ticket item, seat price and extras with prices. SweetAlert confirmation when deleting an extra in admin. Extras admin page uses the same two-column layout as Buses (form left, list right).

### Version 1.0.12 (2026-01-26)

**New Features:**

- **Purchase ticket for someone else** – New setting _Allow buying ticket for someone else_ (default: enabled). When enabled and the cart contains ticket products, checkout shows optional passenger fields (checkbox “Passenger details” / “Would you like to send the ticket to someone else?” and fields: first name, last name, email, phone). Supported on both **block checkout** and **shortcode/classic checkout**. On block checkout the passenger block is shown/hidden based on the checkbox; on classic checkout the fields block is toggled with JavaScript. Passenger data is saved to the order and to reservations; ticket PDF and print use the reservation passenger name/email/phone when “for someone else” is used. Customer and admin order emails (Processing, Completed, New order) include an “Additional information” section with the passenger data when present. On order-received and view-order the same section is displayed (classic orders via plugin output; block orders via WooCommerce Blocks). Option to hide the “Additional information” block when the checkbox was not checked; styling for paler labels on block checkout and consistent layout on order-received.

### Version 1.0.11 (2026-01-26)

**New Features:**

- **New Reservation (manual order)** – Admins can create a reservation/order from the store backend. Under _Ticket Bus → Reservations_ a new submenu _New Reservation_ opens a form to create a WooCommerce order on behalf of a customer or guest: select customer (or enter guest name, email, phone), ticket product, departure date (only dates valid for the schedule), course/time, and one or more seats from a bus-style seat map. Order status and payment method (COD or first available) can be set. The created order has correct line item meta (route, schedule, date, time, seat), totals, and reservation records; admin and customer emails include ticket details. For existing users, billing and reservation names are taken from their profile. When an order is permanently deleted, its reservations are removed automatically. The departure date field is restricted to days when the selected schedule runs (same logic as frontend); seats are shown in bus rows (e.g. A1 A2 | B1 B2 per row).

### Version 1.0.10 (2026-01-26)

**New Features:**

- **Export to XLSX** – On the Reservations page, when a course is selected (date, route, schedule, departure time), an _Export to XLSX_ button is shown next to the bus information. It downloads an Excel file with one row per reserved/confirmed seat. Columns match the "Reservation Information" panel: Order ID, Order Date, Product/Ticket, Order Status, Payment Method, Order Notes, Seat Number, Passenger Name, Passenger Email, Passenger Phone, Departure Date, Departure Time, Status. Requires [PhpSpreadsheet](https://github.com/PHPOffice/PhpSpreadsheet) (`composer require phpoffice/phpspreadsheet`).

**Enhancements:**

- Order ID, Seat Number and Passenger Phone are exported as text so Excel does not show them as numbers (e.g. phone without scientific notation).

### Version 1.0.9 (2026-01-28)

**New Features:**

- **Dashboard widget** – Added a WordPress Dashboard widget _MT Ticket Bus – Sales for the year_ that shows the same bar chart as on the plugin Overview page (tickets sold and revenue by month for the current year). Chart.js is loaded in the admin head when the widget is enabled; the chart initializes when the widget is visible.
- **Setting: Show dashboard widget** – New option in plugin Settings: _Show dashboard widget_. Checkbox to show or hide the sales chart on the main WordPress Dashboard. Default is enabled for new installations.

**Enhancements:**

- Dashboard widget uses the same data source and labels as the Overview chart; i18n for the chart is provided via `mtTicketBusAdmin` when only the dashboard is loaded (no full admin script). Retry logic ensures the chart draws even if Chart.js loads after the widget markup.

### Version 1.0.8 (2026-01-28)

**New Features:**

- **Overview – second row** – Added a second row on the Overview page with two blocks (50/50): _Sales for the year_ and _Best customers_.
- **Sales for the year** – Column chart (Chart.js) for the current year: 12 months with two series – tickets sold and revenue from ticket products. Data from WooCommerce paid orders; translatable labels via `wp_localize_script`.
- **Best customers** – Top 3 customers by total ticket purchase amount for the year. Each customer shown in a single-row card: Gravatar, name, email, ticket count and total, link to last order. Cards have thin border, rounded corners and light background.
- **Welcome block links** – Under the welcome text, added links that depend on admin language (BG/EN): Application website, Demo site, Documentation (PDF), Version control (news). Locale detected via `get_user_locale` / `get_locale`.

**Enhancements:**

- Overview chart and best-customers block use slightly reduced height (e.g. 20% lower) and compact padding for the second row.
- Best-customers cards are full-width flex rows with overflow kept inside the parent (box-sizing, min-width).

### Version 1.0.7 (2026-01-27)

**New Features:**

- **Reservations dashboard** – On the admin Reservations page, when no course filter is selected, a dashboard is shown: a grid of blocks (10 columns, configurable number of days). Each block represents one day (from today); inside it, each course with reserved tickets is listed with route name, departure time, and ticket count. Clicking a course opens the seat map for that date, route, schedule and course. Total tickets for the period is shown at the top; ticket counts are highlighted in a distinct color.
- **Setting: Reservations dashboard period** – New option in plugin Settings: _Reservations dashboard period (days)_. Positive integer from 3 to 90, default 30. Controls how many days the dashboard displays (from today). Prevents overly large grids.

**Enhancements:**

- Filter form on Reservations page: compact single-row layout; primary action label "Search", secondary "All" button to return to the dashboard view. Ticket count numbers (dashboard and per-day) use a highlighted style for better visibility.
- Product/ticket name shown in reservation details popup (admin seat map) when viewing a reserved seat.

### Version 1.0.6 (2026-01-27)

**Enhancements:**

- **Ticket print layout** – More compact design for the printed/PDF ticket: reduced spacing between rows and sections, smaller fonts and padding so that one or two seats fit on a single page.
- **Reservation status on ticket** – The ticket now shows order status, payment method, and reservation status (Reserved / Confirmed / Cancelled) in the Order Information section, using the same translated labels as in the admin reservations view.

### Version 1.0.5 (2026-01-26)

**New Features:**

- **Order email customization for ticket orders** – When an order contains bus tickets, the plugin customizes the WooCommerce customer emails (Processing order, Completed order): subject and heading become ticket-oriented, and additional text is added to the body.
- **Optional PDF ticket attachment** – The ticket can be attached as a PDF to the same emails. Use Dompdf (Composer) or the filter `mt_ticket_bus_ticket_pdf_path` to provide a PDF path; if Dompdf is available, the plugin can generate the PDF automatically from the ticket print template.

### Version 1.0.4 (2026-01-26)

**Enhancements:**

- Changed reservation cleanup to retain historical data for one year instead of deleting all past reservations
- Added script loading for ticket print/download buttons on My Account view-order page
- Added comprehensive PHP documentation (DocBlocks) to all plugin files according to WordPress Coding Standards

### Version 1.0.2 (2026-01-25)

**New Features:**

- Added `mt_ticket_seats` shortcode for viewing seat maps by order number
- Implemented ticket seat visualization with read-only seat map display
- Added special highlighting for customer's seats in seat map view
- Added versioning system for CSS/JS assets based on file modification time (Cloudflare cache busting)

**Enhancements:**

- Improved search form layout with title "BUY TICKET" and better structure
- Enhanced mobile responsiveness for search form (removed padding, added horizontal divider)
- Added Select2 integration for station selection dropdowns
- Improved form styling with transparent background and better spacing
- All user-facing texts moved to translation system

**Bug Fixes:**

- Fixed order lookup by order number in ticket seats view
- Fixed asset versioning to use file modification time instead of plugin version
- Improved compatibility with different WooCommerce versions

**Technical:**

- Added Intelephense stubs for WP_Query and WC_Order::get_order_number()
- Improved code documentation with PHPDoc comments
- Better organized CSS styles with separate responsive sections

### Version 1.0.1

- Initial release with core functionality

### Version 1.0.0

- Initial release

## Support

For support and updates, please visit [Plugin Website](https://example.com)

## License

This plugin is licensed under GPL v2 or later.
