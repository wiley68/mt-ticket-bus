<?php

/**
 * Routes Management Page Template
 *
 * This template displays the routes management page in the WordPress admin area.
 * It provides a form for creating and editing bus routes, and displays a list
 * of all existing routes.
 *
 * The page handles:
 * - Creating new routes with start/end stations, intermediate stations, coordinates, distance, duration, and status
 * - Editing existing routes via GET parameter 'edit'
 * - Displaying all routes in a table with actions (Edit, Delete)
 * - AJAX form submission for saving and deleting routes
 *
 * Expected GET parameters:
 * - edit (int) Optional. Route ID to edit. If provided, form is pre-filled with route data.
 * - saved (string) Optional. Set to '1' to display success message after save.
 *
 * Form submission:
 * - POST data: Form fields (name, start_station, end_station, intermediate_stations, etc.)
 * - AJAX action: 'mt_save_route' - Saves route via AJAX
 * - Nonce: 'mt_ticket_bus_admin' - Security nonce for form validation
 *
 * Route data structure:
 * - name (string) Required. Route name (e.g., 'Sofia - Plovdiv').
 * - start_station (string) Required. Starting station name.
 * - start_station_address (string) Optional. Starting station address.
 * - start_station_latitude (float) Optional. Starting station latitude coordinate.
 * - start_station_longitude (float) Optional. Starting station longitude coordinate.
 * - end_station (string) Required. Ending station name.
 * - end_station_address (string) Optional. Ending station address.
 * - end_station_latitude (float) Optional. Ending station latitude coordinate.
 * - end_station_longitude (float) Optional. Ending station longitude coordinate.
 * - intermediate_stations (string) Optional. JSON array of station objects with 'name' and 'duration' (minutes from start).
 * - distance (float) Optional. Route distance in kilometers.
 * - duration (int) Optional. Route duration in minutes.
 * - status (string) Route status ('active' or 'inactive'). Default 'active'.
 *
 * @package MT_Ticket_Bus
 * @since 1.0.0
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

$routes = MT_Ticket_Bus_Routes::get_instance()->get_all_routes(array('status' => 'all'));
$edit_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
$edit_route = $edit_id ? MT_Ticket_Bus_Routes::get_instance()->get_route($edit_id) : null;
?>

<div class="wrap mt-ticket-bus-routes">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=mt-ticket-bus-routes')); ?>" class="page-title-action"><?php esc_html_e('New Route', 'mt-ticket-bus'); ?></a>
    <hr class="wp-header-end">

    <?php
    // Show success message after save
    if (isset($_GET['saved']) && $_GET['saved'] == '1') {
        $edit_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
        $message = $edit_id > 0
            ? __('Route updated successfully.', 'mt-ticket-bus')
            : __('Route created successfully.', 'mt-ticket-bus');
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
    }
    ?>

    <div class="mt-routes-container">
        <div class="mt-routes-form">
            <h2><?php echo $edit_route ? esc_html__('Edit Route', 'mt-ticket-bus') : esc_html__('Add New Route', 'mt-ticket-bus'); ?></h2>

            <form id="mt-route-form" method="post">
                <?php wp_nonce_field('mt_ticket_bus_admin', 'nonce'); ?>
                <input type="hidden" name="action" value="mt_save_route" />
                <?php if ($edit_route) : ?>
                    <input type="hidden" name="id" value="<?php echo esc_attr($edit_id); ?>" />
                <?php endif; ?>

                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="route_name"><?php esc_html_e('Route Name', 'mt-ticket-bus'); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <input type="text" id="route_name" name="name" value="<?php echo $edit_route ? esc_attr($edit_route->name) : ''; ?>" class="regular-text" required />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="start_station"><?php esc_html_e('Start Station', 'mt-ticket-bus'); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <input type="text" id="start_station" name="start_station" value="<?php echo $edit_route ? esc_attr($edit_route->start_station) : ''; ?>" class="regular-text" required />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="start_station_address"><?php esc_html_e('Start Station Address', 'mt-ticket-bus'); ?></label>
                            </th>
                            <td>
                                <textarea id="start_station_address" name="start_station_address" rows="2" class="large-text"><?php echo $edit_route ? esc_textarea($edit_route->start_station_address ?? '') : ''; ?></textarea>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label><?php esc_html_e('Start Station Coordinates', 'mt-ticket-bus'); ?></label>
                            </th>
                            <td>
                                <label for="start_station_latitude" style="display: inline-block; margin-right: 10px;">
                                    <?php esc_html_e('Latitude:', 'mt-ticket-bus'); ?>
                                    <input type="text" id="start_station_latitude" name="start_station_latitude" value="<?php echo $edit_route ? esc_attr($edit_route->start_station_latitude ?? '') : ''; ?>" class="regular-text" style="width: 200px; margin-left: 5px;" placeholder="<?php esc_attr_e('e.g., 42.6977', 'mt-ticket-bus'); ?>" />
                                </label>
                                <label for="start_station_longitude" style="display: inline-block;">
                                    <?php esc_html_e('Longitude:', 'mt-ticket-bus'); ?>
                                    <input type="text" id="start_station_longitude" name="start_station_longitude" value="<?php echo $edit_route ? esc_attr($edit_route->start_station_longitude ?? '') : ''; ?>" class="regular-text" style="width: 200px; margin-left: 5px;" placeholder="<?php esc_attr_e('e.g., 23.3219', 'mt-ticket-bus'); ?>" />
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="end_station"><?php esc_html_e('End Station', 'mt-ticket-bus'); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <input type="text" id="end_station" name="end_station" value="<?php echo $edit_route ? esc_attr($edit_route->end_station) : ''; ?>" class="regular-text" required />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="end_station_address"><?php esc_html_e('End Station Address', 'mt-ticket-bus'); ?></label>
                            </th>
                            <td>
                                <textarea id="end_station_address" name="end_station_address" rows="2" class="large-text"><?php echo $edit_route ? esc_textarea($edit_route->end_station_address ?? '') : ''; ?></textarea>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label><?php esc_html_e('End Station Coordinates', 'mt-ticket-bus'); ?></label>
                            </th>
                            <td>
                                <label for="end_station_latitude" style="display: inline-block; margin-right: 10px;">
                                    <?php esc_html_e('Latitude:', 'mt-ticket-bus'); ?>
                                    <input type="text" id="end_station_latitude" name="end_station_latitude" value="<?php echo $edit_route ? esc_attr($edit_route->end_station_latitude ?? '') : ''; ?>" class="regular-text" style="width: 200px; margin-left: 5px;" placeholder="<?php esc_attr_e('e.g., 42.6977', 'mt-ticket-bus'); ?>" />
                                </label>
                                <label for="end_station_longitude" style="display: inline-block;">
                                    <?php esc_html_e('Longitude:', 'mt-ticket-bus'); ?>
                                    <input type="text" id="end_station_longitude" name="end_station_longitude" value="<?php echo $edit_route ? esc_attr($edit_route->end_station_longitude ?? '') : ''; ?>" class="regular-text" style="width: 200px; margin-left: 5px;" placeholder="<?php esc_attr_e('e.g., 23.3219', 'mt-ticket-bus'); ?>" />
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label><?php esc_html_e('Intermediate Stations', 'mt-ticket-bus'); ?></label>
                            </th>
                            <td>
                                <div class="mt-intermediate-stations-container">
                                    <div class="mt-station-inputs">
                                        <label for="station_name" style="display: inline-block; margin-right: 10px;">
                                            <?php esc_html_e('Station Name:', 'mt-ticket-bus'); ?>
                                            <input type="text" id="station_name" class="regular-text" style="width: 200px; margin-left: 5px;" placeholder="<?php esc_attr_e('Station name', 'mt-ticket-bus'); ?>" />
                                        </label>
                                        <label for="station_duration" style="display: inline-block; margin-right: 10px;">
                                            <?php esc_html_e('Duration (minutes):', 'mt-ticket-bus'); ?>
                                            <input type="number" id="station_duration" class="small-text" style="width: 100px; margin-left: 5px;" min="0" step="1" placeholder="0" />
                                        </label>
                                        <button type="button" id="add_station_btn" class="button"><?php esc_html_e('Add Station', 'mt-ticket-bus'); ?></button>
                                    </div>
                                    <div id="station_error" style="color: #dc3232; margin-top: 5px; display: none;"></div>
                                    <hr style="margin: 15px 0;" />
                                    <div id="stations_list" class="mt-stations-list">
                                        <?php
                                        // Load existing intermediate stations if editing
                                        $stations = array();
                                        if ($edit_route && !empty($edit_route->intermediate_stations)) {
                                            // Try to decode as JSON first
                                            $decoded = json_decode($edit_route->intermediate_stations, true);
                                            if (is_array($decoded) && !empty($decoded)) {
                                                $stations = $decoded;
                                            } else {
                                                // Fallback: treat as line-separated text (backward compatibility)
                                                $lines = explode("\n", $edit_route->intermediate_stations);
                                                foreach ($lines as $index => $line) {
                                                    $line = trim($line);
                                                    if (!empty($line)) {
                                                        $stations[] = array(
                                                            'name' => $line,
                                                            'duration' => 0 // Default duration for old entries
                                                        );
                                                    }
                                                }
                                            }
                                        }
                                        foreach ($stations as $station) {
                                            if (isset($station['name'])) {
                                                $duration = isset($station['duration']) ? intval($station['duration']) : 0;
                                                echo '<span class="mt-station-badge" data-name="' . esc_attr($station['name']) . '" data-duration="' . esc_attr($duration) . '">';
                                                echo '<span class="mt-station-info">' . esc_html($station['name']) . ' (' . esc_html($duration) . ' ' . esc_html__('min', 'mt-ticket-bus') . ')</span>';
                                                echo '<button type="button" class="mt-remove-station" aria-label="' . esc_attr__('Remove station', 'mt-ticket-bus') . '">×</button>';
                                                echo '</span>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <input type="hidden" id="intermediate_stations_json" name="intermediate_stations" value="<?php
                                                                                                                                if ($edit_route && !empty($edit_route->intermediate_stations)) {
                                                                                                                                    $decoded = json_decode($edit_route->intermediate_stations, true);
                                                                                                                                    if (is_array($decoded) && !empty($decoded)) {
                                                                                                                                        // Filter out any invalid entries
                                                                                                                                        $valid_stations = array();
                                                                                                                                        foreach ($decoded as $station) {
                                                                                                                                            if (isset($station['name']) && !empty(trim($station['name']))) {
                                                                                                                                                $valid_stations[] = $station;
                                                                                                                                            }
                                                                                                                                        }
                                                                                                                                        if (!empty($valid_stations)) {
                                                                                                                                            echo esc_attr(json_encode($valid_stations));
                                                                                                                                        }
                                                                                                                                    } else {
                                                                                                                                        // Convert old format to new format
                                                                                                                                        $lines = explode("\n", $edit_route->intermediate_stations);
                                                                                                                                        $stations_array = array();
                                                                                                                                        foreach ($lines as $line) {
                                                                                                                                            $line = trim($line);
                                                                                                                                            if (!empty($line)) {
                                                                                                                                                $stations_array[] = array('name' => $line, 'duration' => 0);
                                                                                                                                            }
                                                                                                                                        }
                                                                                                                                        if (!empty($stations_array)) {
                                                                                                                                            echo esc_attr(json_encode($stations_array));
                                                                                                                                        }
                                                                                                                                    }
                                                                                                                                }
                                                                                                                                ?>" />
                                    <p class="description"><?php esc_html_e('Add intermediate stations with duration from start station. Each station must have a duration greater than the previous one.', 'mt-ticket-bus'); ?></p>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="distance"><?php esc_html_e('Distance (km)', 'mt-ticket-bus'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="distance" name="distance" value="<?php echo $edit_route ? esc_attr($edit_route->distance) : ''; ?>" class="small-text" step="0.01" min="0" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="duration"><?php esc_html_e('Duration (minutes)', 'mt-ticket-bus'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="duration" name="duration" value="<?php echo $edit_route ? esc_attr($edit_route->duration) : ''; ?>" class="small-text" min="0" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="status"><?php esc_html_e('Status', 'mt-ticket-bus'); ?></label>
                            </th>
                            <td>
                                <select id="status" name="status">
                                    <option value="active" <?php selected($edit_route ? $edit_route->status : 'active', 'active'); ?>><?php esc_html_e('Active', 'mt-ticket-bus'); ?></option>
                                    <option value="inactive" <?php selected($edit_route ? $edit_route->status : '', 'inactive'); ?>><?php esc_html_e('Inactive', 'mt-ticket-bus'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button($edit_route ? __('Update Route', 'mt-ticket-bus') : __('Add Route', 'mt-ticket-bus')); ?>
            </form>
        </div>

        <div class="mt-routes-list">
            <h2><?php esc_html_e('Routes List', 'mt-ticket-bus'); ?></h2>

            <?php if (empty($routes)) : ?>
                <p><?php esc_html_e('No routes found.', 'mt-ticket-bus'); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('ID', 'mt-ticket-bus'); ?></th>
                            <th class="mt-route-name-col"><?php esc_html_e('Name', 'mt-ticket-bus'); ?></th>
                            <th><?php esc_html_e('Start Station', 'mt-ticket-bus'); ?></th>
                            <th><?php esc_html_e('End Station', 'mt-ticket-bus'); ?></th>
                            <th><?php esc_html_e('Actions', 'mt-ticket-bus'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($routes as $route) : ?>
                            <tr class="<?php echo $route->status === 'inactive' ? 'mt-route-inactive' : ''; ?>">
                                <td><?php echo esc_html($route->id); ?></td>
                                <td class="mt-route-name-col"><?php echo esc_html($route->name); ?></td>
                                <td><?php echo esc_html($route->start_station); ?></td>
                                <td><?php echo esc_html($route->end_station); ?></td>
                                <td class="mt-route-actions">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=mt-ticket-bus-routes&edit=' . $route->id)); ?>"><?php esc_html_e('Edit', 'mt-ticket-bus'); ?></a> |
                                    <a href="#" class="mt-delete-route" data-id="<?php echo esc_attr($route->id); ?>"><?php esc_html_e('Delete', 'mt-ticket-bus'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>