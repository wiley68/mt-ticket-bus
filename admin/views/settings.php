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
    // Save settings here
    update_option('mt_ticket_bus_settings', $_POST);
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
            </tbody>
        </table>

        <?php submit_button(__('Save Settings', 'mt-ticket-bus')); ?>
    </form>
</div>