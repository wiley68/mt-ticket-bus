<?php

/**
 * Settings page template
 *
 * @package MT_Ticket_Bus
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['mt_ticket_bus_settings']) && check_admin_referer('mt_ticket_bus_settings')) {
    // Sanitize and save settings
    $settings_to_save = array_map('sanitize_text_field', $_POST['mt_ticket_bus_settings']);
    update_option('mt_ticket_bus_settings', $settings_to_save);
    echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved.', 'mt-ticket-bus') . '</p></div>';
}

$settings = get_option('mt_ticket_bus_settings', array());
?>

<div class="wrap mt-ticket-bus-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('mt_ticket_bus_settings'); ?>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="mt_ticket_bus_timezone"><?php esc_html_e('Timezone', 'mt-ticket-bus'); ?></label>
                    </th>
                    <td>
                        <select id="mt_ticket_bus_timezone" name="mt_ticket_bus_settings[timezone]">
                            <?php
                            $timezones = timezone_identifiers_list();
                            $selected_timezone = isset($settings['timezone']) ? $settings['timezone'] : get_option('timezone_string');
                            foreach ($timezones as $timezone) {
                                echo '<option value="' . esc_attr($timezone) . '" ' . selected($selected_timezone, $timezone, false) . '>' . esc_html($timezone) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description"><?php esc_html_e('Timezone for bus schedules and ticket times.', 'mt-ticket-bus'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="mt_ticket_bus_calendar_week_start"><?php esc_html_e('Calendar Week Start', 'mt-ticket-bus'); ?></label>
                    </th>
                    <td>
                        <select id="mt_ticket_bus_calendar_week_start" name="mt_ticket_bus_settings[calendar_week_start]">
                            <?php
                            $week_start_options = array(
                                'monday' => __('Monday', 'mt-ticket-bus'),
                                'sunday' => __('Sunday', 'mt-ticket-bus'),
                            );
                            $selected_week_start = isset($settings['calendar_week_start']) ? $settings['calendar_week_start'] : 'monday';
                            foreach ($week_start_options as $value => $label) {
                                echo '<option value="' . esc_attr($value) . '" ' . selected($selected_week_start, $value, false) . '>' . esc_html($label) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description"><?php esc_html_e('Choose which day the calendar week should start on. Monday is common in Europe, Sunday is common in North America.', 'mt-ticket-bus'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="mt_ticket_bus_show_short_description"><?php esc_html_e('Display Short Description', 'mt-ticket-bus'); ?></label>
                    </th>
                    <td>
                        <?php
                        // Default to 'yes' if not set (checked by default)
                        $show_short_description = isset($settings['show_short_description']) ? $settings['show_short_description'] : 'yes';
                        ?>
                        <input type="hidden" name="mt_ticket_bus_settings[show_short_description]" value="no" />
                        <label for="mt_ticket_bus_show_short_description">
                            <input type="checkbox" id="mt_ticket_bus_show_short_description" name="mt_ticket_bus_settings[show_short_description]" value="yes" <?php checked($show_short_description, 'yes'); ?> />
                            <?php esc_html_e('Show short description in ticket summary', 'mt-ticket-bus'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Enable this option to display the product short description in the ticket summary block.', 'mt-ticket-bus'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="mt_ticket_bus_show_bus_name"><?php esc_html_e('Display Bus Name', 'mt-ticket-bus'); ?></label>
                    </th>
                    <td>
                        <?php
                        // Default to 'yes' if not set (checked by default)
                        $show_bus_name = isset($settings['show_bus_name']) ? $settings['show_bus_name'] : 'yes';
                        ?>
                        <input type="hidden" name="mt_ticket_bus_settings[show_bus_name]" value="no" />
                        <label for="mt_ticket_bus_show_bus_name">
                            <input type="checkbox" id="mt_ticket_bus_show_bus_name" name="mt_ticket_bus_settings[show_bus_name]" value="yes" <?php checked($show_bus_name, 'yes'); ?> />
                            <?php esc_html_e('Show bus name in ticket summary', 'mt-ticket-bus'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Enable this option to display the bus name below the short description in the ticket summary block.', 'mt-ticket-bus'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="mt_ticket_bus_show_route_info"><?php esc_html_e('Display Route Info', 'mt-ticket-bus'); ?></label>
                    </th>
                    <td>
                        <?php
                        // Default to 'yes' if not set (checked by default)
                        $show_route_info = isset($settings['show_route_info']) ? $settings['show_route_info'] : 'yes';
                        ?>
                        <input type="hidden" name="mt_ticket_bus_settings[show_route_info]" value="no" />
                        <label for="mt_ticket_bus_show_route_info">
                            <input type="checkbox" id="mt_ticket_bus_show_route_info" name="mt_ticket_bus_settings[show_route_info]" value="yes" <?php checked($show_route_info, 'yes'); ?> />
                            <?php esc_html_e('Show route information in ticket summary', 'mt-ticket-bus'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Enable this option to display the route stops (start, intermediate, end) below the bus name in the ticket summary block.', 'mt-ticket-bus'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="mt_ticket_bus_show_route_distance"><?php esc_html_e('Display Route Distance', 'mt-ticket-bus'); ?></label>
                    </th>
                    <td>
                        <?php
                        // Default to 'yes' if not set (checked by default)
                        $show_route_distance = isset($settings['show_route_distance']) ? $settings['show_route_distance'] : 'yes';
                        ?>
                        <input type="hidden" name="mt_ticket_bus_settings[show_route_distance]" value="no" />
                        <label for="mt_ticket_bus_show_route_distance">
                            <input type="checkbox" id="mt_ticket_bus_show_route_distance" name="mt_ticket_bus_settings[show_route_distance]" value="yes" <?php checked($show_route_distance, 'yes'); ?> />
                            <?php esc_html_e('Show route distance in ticket summary', 'mt-ticket-bus'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Enable this option to display the route distance below the route stops in the ticket summary block.', 'mt-ticket-bus'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="mt_ticket_bus_show_route_duration"><?php esc_html_e('Display Route Duration', 'mt-ticket-bus'); ?></label>
                    </th>
                    <td>
                        <?php
                        // Default to 'yes' if not set (checked by default)
                        $show_route_duration = isset($settings['show_route_duration']) ? $settings['show_route_duration'] : 'yes';
                        ?>
                        <input type="hidden" name="mt_ticket_bus_settings[show_route_duration]" value="no" />
                        <label for="mt_ticket_bus_show_route_duration">
                            <input type="checkbox" id="mt_ticket_bus_show_route_duration" name="mt_ticket_bus_settings[show_route_duration]" value="yes" <?php checked($show_route_duration, 'yes'); ?> />
                            <?php esc_html_e('Show route duration in ticket summary', 'mt-ticket-bus'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Enable this option to display the route duration (in minutes) below the route distance in the ticket summary block.', 'mt-ticket-bus'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="mt_ticket_bus_show_bus_extras"><?php esc_html_e('Display Bus Extras', 'mt-ticket-bus'); ?></label>
                    </th>
                    <td>
                        <?php
                        // Default to 'yes' if not set (checked by default)
                        $show_bus_extras = isset($settings['show_bus_extras']) ? $settings['show_bus_extras'] : 'yes';
                        ?>
                        <input type="hidden" name="mt_ticket_bus_settings[show_bus_extras]" value="no" />
                        <label for="mt_ticket_bus_show_bus_extras">
                            <input type="checkbox" id="mt_ticket_bus_show_bus_extras" name="mt_ticket_bus_settings[show_bus_extras]" value="yes" <?php checked($show_bus_extras, 'yes'); ?> />
                            <?php esc_html_e('Show bus extras in ticket summary', 'mt-ticket-bus'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Enable this option to display the bus extras (features) below the route duration in the ticket summary block.', 'mt-ticket-bus'); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php submit_button(__('Save Settings', 'mt-ticket-bus')); ?>
    </form>
</div>