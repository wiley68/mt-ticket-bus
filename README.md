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

## Support

For support and updates, please visit [Plugin Website](https://example.com)

## License

This plugin is licensed under GPL v2 or later.
