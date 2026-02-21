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
- WooCommerce 5.0 or higher

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
