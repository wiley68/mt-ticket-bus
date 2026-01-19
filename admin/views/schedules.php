<?php

/**
 * Schedules management page template
 *
 * @package MT_Ticket_Bus
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

$schedules = MT_Ticket_Bus_Schedules::get_instance()->get_all_schedules();
$edit_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
$edit_schedule = $edit_id ? MT_Ticket_Bus_Schedules::get_instance()->get_schedule($edit_id) : null;

// Get routes for dropdown
$routes = MT_Ticket_Bus_Routes::get_instance()->get_all_routes(array('status' => 'all'));

// Parse days of week if editing
$days_of_week = array();
if ($edit_schedule && !empty($edit_schedule->days_of_week)) {
    $days_of_week = MT_Ticket_Bus_Schedules::get_instance()->parse_days_of_week($edit_schedule->days_of_week);
}
?>

<div class="wrap mt-ticket-bus-schedules">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=mt-ticket-bus-schedules')); ?>" class="page-title-action"><?php esc_html_e('New Schedule', 'mt-ticket-bus'); ?></a>
    <hr class="wp-header-end">

    <?php
    // Show success message after save
    if (isset($_GET['saved']) && $_GET['saved'] == '1') {
        $edit_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
        $message = $edit_id > 0
            ? __('Schedule updated successfully.', 'mt-ticket-bus')
            : __('Schedule created successfully.', 'mt-ticket-bus');
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
    }
    ?>

    <div class="mt-schedules-container">
        <div class="mt-schedules-form">
            <h2><?php echo $edit_schedule ? esc_html__('Edit Schedule', 'mt-ticket-bus') : esc_html__('Add New Schedule', 'mt-ticket-bus'); ?></h2>

            <form id="mt-schedule-form" method="post">
                <?php wp_nonce_field('mt_ticket_bus_admin', 'nonce'); ?>
                <input type="hidden" name="action" value="mt_save_schedule" />
                <?php if ($edit_schedule) : ?>
                    <input type="hidden" name="id" value="<?php echo esc_attr($edit_id); ?>" />
                <?php endif; ?>

                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="schedule_name"><?php esc_html_e('Schedule Name', 'mt-ticket-bus'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="schedule_name" name="name" value="<?php echo $edit_schedule ? esc_attr($edit_schedule->name) : ''; ?>" class="regular-text" />
                                <p class="description"><?php esc_html_e('Optional: A descriptive name for this schedule.', 'mt-ticket-bus'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="route_id"><?php esc_html_e('Route', 'mt-ticket-bus'); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <select id="route_id" name="route_id" class="regular-text" required>
                                    <option value=""><?php esc_html_e('Select a route...', 'mt-ticket-bus'); ?></option>
                                    <?php foreach ($routes as $route) : ?>
                                        <?php
                                        $stations_display = '';
                                        
                                        // Only show stations in brackets if there are intermediate stations
                                        if (!empty($route->intermediate_stations)) {
                                            $stations = array();
                                            $intermediate = array_filter(array_map('trim', explode("\n", $route->intermediate_stations)));
                                            
                                            // Only proceed if we have intermediate stations
                                            if (!empty($intermediate)) {
                                                // Add start station
                                                if (!empty($route->start_station)) {
                                                    $stations[] = $route->start_station;
                                                }
                                                
                                                // Add intermediate stations
                                                $stations = array_merge($stations, $intermediate);
                                                
                                                // Add end station
                                                if (!empty($route->end_station)) {
                                                    $stations[] = $route->end_station;
                                                }
                                                
                                                if (!empty($stations)) {
                                                    $stations_display = ' (' . esc_html(implode(', ', $stations)) . ')';
                                                }
                                            }
                                        }
                                        ?>
                                        <option value="<?php echo esc_attr($route->id); ?>" <?php selected($edit_schedule ? $edit_schedule->route_id : '', $route->id); ?>>
                                            <?php echo esc_html($route->name) . $stations_display; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="departure_time"><?php esc_html_e('Departure Time', 'mt-ticket-bus'); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <input type="time" id="departure_time" name="departure_time" value="<?php echo $edit_schedule ? esc_attr($edit_schedule->departure_time) : ''; ?>" class="regular-text" required />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="arrival_time"><?php esc_html_e('Arrival Time', 'mt-ticket-bus'); ?></label>
                            </th>
                            <td>
                                <input type="time" id="arrival_time" name="arrival_time" value="<?php echo $edit_schedule ? esc_attr($edit_schedule->arrival_time) : ''; ?>" class="regular-text" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="frequency_type"><?php esc_html_e('Frequency Type', 'mt-ticket-bus'); ?></label>
                            </th>
                            <td>
                                <select id="frequency_type" name="frequency_type" class="regular-text">
                                    <option value="single" <?php selected($edit_schedule ? $edit_schedule->frequency_type : 'single', 'single'); ?>><?php esc_html_e('Single (one time)', 'mt-ticket-bus'); ?></option>
                                    <option value="multiple" <?php selected($edit_schedule ? $edit_schedule->frequency_type : '', 'multiple'); ?>><?php esc_html_e('Multiple (recurring)', 'mt-ticket-bus'); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e('Single: one-time schedule. Multiple: recurring schedule based on days of week.', 'mt-ticket-bus'); ?></p>
                            </td>
                        </tr>

                        <tr id="days_of_week_row" style="<?php echo ($edit_schedule && $edit_schedule->frequency_type === 'multiple') || (!$edit_schedule) ? '' : 'display:none;'; ?>">
                            <th scope="row">
                                <label><?php esc_html_e('Days of Week', 'mt-ticket-bus'); ?></label>
                            </th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="radio" name="days_of_week_type" value="all" <?php checked($days_of_week === 'all' || (is_string($days_of_week) && $days_of_week === 'all')); ?> />
                                        <?php esc_html_e('Every day', 'mt-ticket-bus'); ?>
                                    </label><br>
                                    <label>
                                        <input type="radio" name="days_of_week_type" value="weekdays" <?php checked($days_of_week === 'weekdays' || (is_string($days_of_week) && $days_of_week === 'weekdays')); ?> />
                                        <?php esc_html_e('Weekdays only (Monday-Friday)', 'mt-ticket-bus'); ?>
                                    </label><br>
                                    <label>
                                        <input type="radio" name="days_of_week_type" value="weekend" <?php checked($days_of_week === 'weekend' || (is_string($days_of_week) && $days_of_week === 'weekend')); ?> />
                                        <?php esc_html_e('Weekend only (Saturday-Sunday)', 'mt-ticket-bus'); ?>
                                    </label><br>
                                    <label>
                                        <input type="radio" name="days_of_week_type" value="custom" <?php checked(is_array($days_of_week) && !in_array($days_of_week, array('all', 'weekdays', 'weekend'))); ?> />
                                        <?php esc_html_e('Custom days:', 'mt-ticket-bus'); ?>
                                    </label><br>
                                    <div id="custom_days" style="margin-left: 20px; margin-top: 5px;">
                                        <?php
                                        $week_days = array(
                                            'monday' => __('Monday', 'mt-ticket-bus'),
                                            'tuesday' => __('Tuesday', 'mt-ticket-bus'),
                                            'wednesday' => __('Wednesday', 'mt-ticket-bus'),
                                            'thursday' => __('Thursday', 'mt-ticket-bus'),
                                            'friday' => __('Friday', 'mt-ticket-bus'),
                                            'saturday' => __('Saturday', 'mt-ticket-bus'),
                                            'sunday' => __('Sunday', 'mt-ticket-bus'),
                                        );
                                        $selected_days = is_array($days_of_week) ? $days_of_week : array();
                                        foreach ($week_days as $day_key => $day_label) :
                                        ?>
                                            <label style="display: inline-block; margin-right: 15px;">
                                                <input type="checkbox" name="days_of_week[]" value="<?php echo esc_attr($day_key); ?>" <?php checked(in_array($day_key, $selected_days)); ?> />
                                                <?php echo esc_html($day_label); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </fieldset>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="price"><?php esc_html_e('Price Override', 'mt-ticket-bus'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="price" name="price" value="<?php echo $edit_schedule ? esc_attr($edit_schedule->price) : ''; ?>" class="small-text" step="0.01" min="0" />
                                <p class="description"><?php esc_html_e('Optional: Override product price for this specific schedule. Leave empty to use product price.', 'mt-ticket-bus'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="status"><?php esc_html_e('Status', 'mt-ticket-bus'); ?></label>
                            </th>
                            <td>
                                <select id="status" name="status">
                                    <option value="active" <?php selected($edit_schedule ? $edit_schedule->status : 'active', 'active'); ?>><?php esc_html_e('Active', 'mt-ticket-bus'); ?></option>
                                    <option value="inactive" <?php selected($edit_schedule ? $edit_schedule->status : '', 'inactive'); ?>><?php esc_html_e('Inactive', 'mt-ticket-bus'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button($edit_schedule ? __('Update Schedule', 'mt-ticket-bus') : __('Add Schedule', 'mt-ticket-bus')); ?>
            </form>
        </div>

        <div class="mt-schedules-list">
            <h2><?php esc_html_e('Schedules List', 'mt-ticket-bus'); ?></h2>

            <?php if (empty($schedules)) : ?>
                <p><?php esc_html_e('No schedules found.', 'mt-ticket-bus'); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('ID', 'mt-ticket-bus'); ?></th>
                            <th><?php esc_html_e('Name', 'mt-ticket-bus'); ?></th>
                            <th><?php esc_html_e('Route', 'mt-ticket-bus'); ?></th>
                            <th><?php esc_html_e('Departure Time', 'mt-ticket-bus'); ?></th>
                            <th><?php esc_html_e('Arrival Time', 'mt-ticket-bus'); ?></th>
                            <th><?php esc_html_e('Frequency', 'mt-ticket-bus'); ?></th>
                            <th><?php esc_html_e('Status', 'mt-ticket-bus'); ?></th>
                            <th><?php esc_html_e('Actions', 'mt-ticket-bus'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $schedule) : ?>
                            <?php
                            $days_display = '';
                            if ($schedule->frequency_type === 'multiple' && !empty($schedule->days_of_week)) {
                                $parsed_days = MT_Ticket_Bus_Schedules::get_instance()->parse_days_of_week($schedule->days_of_week);
                                if ($parsed_days === 'all') {
                                    $days_display = __('Every day', 'mt-ticket-bus');
                                } elseif ($parsed_days === 'weekdays') {
                                    $days_display = __('Weekdays', 'mt-ticket-bus');
                                } elseif ($parsed_days === 'weekend') {
                                    $days_display = __('Weekend', 'mt-ticket-bus');
                                } elseif (is_array($parsed_days)) {
                                    $days_display = implode(', ', array_map('ucfirst', $parsed_days));
                                }
                            }
                            
                            // Get route name
                            $route_name = '—';
                            if (!empty($schedule->route_id)) {
                                $route = MT_Ticket_Bus_Routes::get_instance()->get_route($schedule->route_id);
                                if ($route) {
                                    $route_name = esc_html($route->name);
                                }
                            }
                            ?>
                            <tr class="<?php echo $schedule->status === 'inactive' ? 'mt-schedule-inactive' : ''; ?>">
                                <td><?php echo esc_html($schedule->id); ?></td>
                                <td><?php echo $schedule->name ? esc_html($schedule->name) : '—'; ?></td>
                                <td><?php echo $route_name; ?></td>
                                <td><?php echo esc_html($schedule->departure_time); ?></td>
                                <td><?php echo $schedule->arrival_time ? esc_html($schedule->arrival_time) : '—'; ?></td>
                                <td><?php echo $schedule->frequency_type === 'multiple' ? esc_html($days_display) : esc_html__('Single', 'mt-ticket-bus'); ?></td>
                                <td><?php echo esc_html(ucfirst($schedule->status)); ?></td>
                                <td class="mt-schedule-actions">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=mt-ticket-bus-schedules&edit=' . $schedule->id)); ?>"><?php esc_html_e('Edit', 'mt-ticket-bus'); ?></a> |
                                    <a href="#" class="mt-delete-schedule" data-id="<?php echo esc_attr($schedule->id); ?>"><?php esc_html_e('Delete', 'mt-ticket-bus'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>