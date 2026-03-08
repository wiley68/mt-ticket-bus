=== MT Ticket Bus ===

Contributors: wiley68
Tags: bus, tickets, woocommerce, booking, transport, seat selection, reservations
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 1.0.16
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive WordPress plugin for bus ticket sales management integrated with WooCommerce.

== Description ==

MT Ticket Bus turns WordPress and WooCommerce into a bus ticket sales system. Manage buses, routes, schedules, and sell tickets with seat selection, segment-based pricing (travel between intermediate stops), paid extras, and optional PDF tickets.

**Features:**

* **Bus fleet** – Buses with seat layout, registration, features
* **Routes** – Start/end and intermediate stations, distance, duration
* **Schedules** – Courses (departure/arrival) per route, days of week
* **Ticket products** – WooCommerce virtual products linked to routes; date/time/seat selection on product page (block or classic theme)
* **Segment pricing** – Sell tickets between any two stops on the route; price and cart/order reflect the selected segment
* **Paid extras** – Optional extras per product (e.g. luggage); shown on product page and in cart/checkout
* **Reservations** – Admin view of reserved seats by date/route/schedule; manual “New reservation” orders; XLSX export
* **Ticket print/PDF** – Print or download ticket from order/account; optional PDF attachment to order emails (Dompdf)
* **Passenger details** – Optional “buy for someone else” with passenger name/email/phone on checkout
* **Search shortcode** – `[mt_ticket_search]` for route/date search and segment + extras selection
* **Appearance** – Four color palettes for product-page blocks; optional per-color overrides in Settings
* **Multilingual** – Translation-ready (e.g. English, Bulgarian)

**Requirements:** WooCommerce 8.0+ (tested up to 10.4.3). Optional: Dompdf (Composer) for PDF tickets; PhpSpreadsheet (Composer) for XLSX export.

== Installation ==

1. Install and activate WooCommerce.
2. Upload the plugin files to `/wp-content/plugins/mt-ticket-bus/`, or install through the Plugins screen.
3. Activate the plugin through the 'Plugins' menu.
4. Go to **Ticket Bus** in the admin menu to add Buses, Routes, Schedules, and Extras.
5. Create WooCommerce products and in the product data panel mark them as ticket products, then link a bus route and (optionally) allowed extras.
6. Add the ticket product to a page (single product) and/or use the `[mt_ticket_search]` shortcode for a search/booking flow.

**Optional – PDF tickets:** To attach a PDF ticket to order emails, install Dompdf in the plugin directory, e.g. `composer require dompdf/dompdf` from the plugin root. The plugin will detect it and generate PDFs from the ticket template.

**Optional – XLSX export:** For Excel export on the Reservations page, run `composer require phpoffice/phpspreadsheet` in the plugin directory.

== Frequently Asked Questions ==

= Do I need WooCommerce? =

Yes. MT Ticket Bus requires WooCommerce to be installed and active.

= Can customers choose a segment (e.g. not the full route)? =

Yes. On the product page and in the search results, customers can select a starting and final bus stop. The price updates to the segment price and is stored in the cart and order.

= How do I attach a PDF ticket to order emails? =

Install Dompdf via Composer in the plugin folder: `composer require dompdf/dompdf`. Ensure the `vendor/` folder is present when you deploy. The plugin will then generate a PDF from the ticket template and attach it to the Processing and Completed order emails when the order contains tickets.

== Changelog ==

= 1.0.16 =
* Search shortcode – route segment and paid extras support in search results; segment and extras reflected in price, cart, and ticket.

= 1.0.15 =
* Segment-based pricing – purchase tickets between intermediate stops; segment price in cart, checkout, and printed/PDF ticket; starting and final stop shown in order and on ticket.

= 1.0.14 =
* Appearance – color palettes for product page blocks (four presets + optional per-color overrides in Settings).
* After saving settings, the active tab is preserved on reload.

= 1.0.13 =
* Paid extras – define extras in Ticket Bus → Extras; assign to products; select on product page; shown in cart, order, reservations, and ticket print/PDF.

= 1.0.12 =
* Purchase ticket for someone else – optional passenger fields on checkout (block and classic); passenger data in order, reservations, and ticket.

= 1.0.11 =
* New Reservation – create orders from admin (Ticket Bus → Reservations → New Reservation) with customer, product, date, course, and seat map.

= 1.0.10 =
* Export to XLSX on Reservations page (requires PhpSpreadsheet).

= 1.0.9 =
* Dashboard widget “Sales for the year”; setting to show/hide it.

= 1.0.8 =
* Overview: Sales for the year chart and Best customers block.

= 1.0.7 =
* Reservations dashboard (grid by day); setting for dashboard period (days).

= 1.0.6 =
* Compact ticket print layout; reservation status on ticket.

= 1.0.5 =
* Order email customization for ticket orders; optional PDF ticket attachment (Dompdf or filter).

= 1.0.4 =
* Reservation cleanup retains one year of data; script loading on view-order; DocBlocks.

= 1.0.2 =
* Shortcode for seat map by order; translation-ready; Select2 and layout improvements.

= 1.0.1 =
* Initial release with core functionality.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.16 =
Search shortcode supports segment and paid extras; segment and extras in price and ticket.

= 1.0.15 =
Segment-based pricing: sell tickets between intermediate stops; segment price and start/end stop shown in orders and on tickets.
