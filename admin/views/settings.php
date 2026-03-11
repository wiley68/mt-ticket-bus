<?php

/**
 * Settings Page Template
 *
 * This template displays the plugin settings page in the WordPress admin area.
 * It provides options for configuring timezone, calendar week start, and various
 * display options for ticket summary blocks.
 *
 * The page handles form submission, sanitizes input data, and saves settings
 * to the WordPress options table. Settings include:
 * - Timezone configuration for schedules and ticket times
 * - Calendar week start (Monday or Sunday)
 * - Display options for ticket summary (short description, bus name, route info, etc.)
 *
 * Expected variables:
 * - None (this is a standalone template that retrieves its own data)
 *
 * Form submission:
 * - POST data: $_POST['mt_ticket_bus_settings'] - Array of setting values
 * - Nonce: 'mt_ticket_bus_settings' - Security nonce for form validation
 *
 * Settings structure:
 * - reservation_period (string) - Number of days for reservations dashboard (3–90, default 30)
 * - timezone (string) - Timezone identifier (e.g. 'Europe/Sofia')
 * - calendar_week_start (string) - 'monday' or 'sunday'
 * - show_short_description (string) - 'yes' or 'no'
 * - show_bus_name (string) - 'yes' or 'no'
 * - show_route_info (string) - 'yes' or 'no'
 * - show_route_distance (string) - 'yes' or 'no'
 * - show_route_duration (string) - 'yes' or 'no'
 * - show_bus_extras (string) - 'yes' or 'no' – display free bus extras in ticket summary
 * - show_pay_extras (string) - 'yes' or 'no' – allow paid extras on ticket products
 *
 * @package MT_Ticket_Bus
 * @since 1.0.0
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['mt_ticket_bus_settings']) && check_admin_referer('mt_ticket_bus_settings')) {
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Array sanitized per key in loop below (sanitize_text_field, absint, whitelist).
    $raw = wp_unslash($_POST['mt_ticket_bus_settings']);
    $settings_to_save = array();
    foreach ($raw as $k => $v) {
        if ($k === 'appearance_colors' && is_array($v)) {
            continue;
        }
        $settings_to_save[$k] = sanitize_text_field($v);
    }
    $reservation_period = isset($raw['reservation_period']) ? absint($raw['reservation_period']) : 30;
    $settings_to_save['reservation_period'] = (string) max(3, min(90, $reservation_period));
    if (isset($raw['appearance_palette'])) {
        $pid = sanitize_text_field($raw['appearance_palette']);
        $settings_to_save['appearance_palette'] = in_array($pid, array('1', '2', '3', '4'), true) ? $pid : '1';
    }
    $palettes = MT_Ticket_Bus_Appearance_Palettes::get_palettes();
    $pid = isset($settings_to_save['appearance_palette']) ? $settings_to_save['appearance_palette'] : '1';
    $base = isset($palettes[$pid]['colors']) ? $palettes[$pid]['colors'] : $palettes['1']['colors'];
    $settings_to_save['appearance_colors'] = $base;
    if (isset($raw['appearance_colors']) && is_array($raw['appearance_colors'])) {
        $color_keys = MT_Ticket_Bus_Appearance_Palettes::get_color_keys();
        foreach ($color_keys as $key) {
            if (isset($raw['appearance_colors'][$key])) {
                $hex = sanitize_text_field($raw['appearance_colors'][$key]);
                if (MT_Ticket_Bus_Appearance_Palettes::is_valid_hex($hex)) {
                    $settings_to_save['appearance_colors'][$key] = MT_Ticket_Bus_Appearance_Palettes::normalize_hex($hex);
                }
            }
        }
    }
    // Merge with existing settings so we don't lose keys like license_key / license_status.
    $existing_settings = get_option('mt_ticket_bus_settings', array());
    if (! is_array($existing_settings)) {
        $existing_settings = array();
    }
    $merged_settings = array_merge($existing_settings, $settings_to_save);
    update_option('mt_ticket_bus_settings', $merged_settings);
    echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved.', 'mt-ticket-bus') . '</p></div>';
}

$settings = get_option('mt_ticket_bus_settings', array());
$palettes = MT_Ticket_Bus_Appearance_Palettes::get_palettes();
$color_keys = MT_Ticket_Bus_Appearance_Palettes::get_color_keys();
$color_labels = MT_Ticket_Bus_Appearance_Palettes::get_color_labels();
$appearance_palette = isset($settings['appearance_palette']) ? $settings['appearance_palette'] : '1';
$appearance_colors = MT_Ticket_Bus_Appearance_Palettes::get_effective_colors();

$allowed_tabs = array('settings', 'permissions', 'appearance');
if (!empty($_POST['mt_settings_active_tab']) && in_array(sanitize_text_field(wp_unslash($_POST['mt_settings_active_tab'])), $allowed_tabs, true)) {
    $active_tab = sanitize_text_field(wp_unslash($_POST['mt_settings_active_tab']));
} else {
    $active_tab = 'settings';
}
?>

<div class="wrap mt-ticket-bus-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <nav class="mt-settings-nav-tabs nav-tab-wrapper" aria-label="<?php esc_attr_e('Settings tabs', 'mt-ticket-bus'); ?>">
        <button type="button" class="nav-tab<?php echo $active_tab === 'settings' ? ' nav-tab-active' : ''; ?>" data-mt-tab="settings"><?php esc_html_e('Settings', 'mt-ticket-bus'); ?></button>
        <button type="button" class="nav-tab<?php echo $active_tab === 'permissions' ? ' nav-tab-active' : ''; ?>" data-mt-tab="permissions"><?php esc_html_e('Permissions', 'mt-ticket-bus'); ?></button>
        <button type="button" class="nav-tab<?php echo $active_tab === 'appearance' ? ' nav-tab-active' : ''; ?>" data-mt-tab="appearance"><?php esc_html_e('Appearance', 'mt-ticket-bus'); ?></button>
    </nav>

    <form method="post" action="">
        <?php wp_nonce_field('mt_ticket_bus_settings'); ?>
        <input type="hidden" name="mt_settings_active_tab" id="mt_settings_active_tab" value="<?php echo esc_attr($active_tab); ?>" />

        <div id="mt-settings-tab-settings" class="mt-settings-tab-pane" role="tabpanel" <?php echo $active_tab !== 'settings' ? ' hidden' : ''; ?>>
            <div class="mt-widget">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="mt_ticket_bus_reservation_period"><?php esc_html_e('Reservations dashboard period (days)', 'mt-ticket-bus'); ?></label>
                            </th>
                            <td>
                                <?php
                                $reservation_period = isset($settings['reservation_period']) ? absint($settings['reservation_period']) : 30;
                                $reservation_period = max(3, min(90, $reservation_period));
                                ?>
                                <input type="number" id="mt_ticket_bus_reservation_period" name="mt_ticket_bus_settings[reservation_period]" value="<?php echo esc_attr($reservation_period); ?>" min="3" max="90" step="1" class="small-text" />
                                <p class="description"><?php esc_html_e('Number of days to show in the reservations dashboard (from today). Minimum 3, maximum 90. Default 30.', 'mt-ticket-bus'); ?></p>
                            </td>
                        </tr>
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
                    </tbody>
                </table>
            </div>
        </div>

        <div id="mt-settings-tab-permissions" class="mt-settings-tab-pane" role="tabpanel" <?php echo $active_tab !== 'permissions' ? ' hidden' : ''; ?>>
            <div class="mt-settings-permissions-grid">
                <div class="mt-widget mt-settings-permissions-col">
                    <table class="form-table">
                        <tbody>
                            <?php
                            $show_dashboard_widget = isset($settings['show_dashboard_widget']) ? $settings['show_dashboard_widget'] : 'yes';
                            $show_short_description = isset($settings['show_short_description']) ? $settings['show_short_description'] : 'yes';
                            $show_bus_name = isset($settings['show_bus_name']) ? $settings['show_bus_name'] : 'yes';
                            $show_route_info = isset($settings['show_route_info']) ? $settings['show_route_info'] : 'yes';
                            $show_route_distance = isset($settings['show_route_distance']) ? $settings['show_route_distance'] : 'yes';
                            ?>
                            <tr>
                                <th scope="row"><label for="mt_ticket_bus_show_dashboard_widget"><?php esc_html_e('Show dashboard widget', 'mt-ticket-bus'); ?></label></th>
                                <td>
                                    <input type="hidden" name="mt_ticket_bus_settings[show_dashboard_widget]" value="no" />
                                    <label for="mt_ticket_bus_show_dashboard_widget"><input type="checkbox" id="mt_ticket_bus_show_dashboard_widget" name="mt_ticket_bus_settings[show_dashboard_widget]" value="yes" <?php checked($show_dashboard_widget, 'yes'); ?> /> <?php esc_html_e('Show dashboard widget', 'mt-ticket-bus'); ?></label>
                                    <p class="description"><?php esc_html_e('Show the "Sales for the year" chart on the main WordPress Dashboard.', 'mt-ticket-bus'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="mt_ticket_bus_show_short_description"><?php esc_html_e('Display Short Description', 'mt-ticket-bus'); ?></label></th>
                                <td>
                                    <input type="hidden" name="mt_ticket_bus_settings[show_short_description]" value="no" />
                                    <label for="mt_ticket_bus_show_short_description"><input type="checkbox" id="mt_ticket_bus_show_short_description" name="mt_ticket_bus_settings[show_short_description]" value="yes" <?php checked($show_short_description, 'yes'); ?> /> <?php esc_html_e('Show short description in ticket summary', 'mt-ticket-bus'); ?></label>
                                    <p class="description"><?php esc_html_e('Enable this option to display the product short description in the ticket summary block.', 'mt-ticket-bus'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="mt_ticket_bus_show_bus_name"><?php esc_html_e('Display Bus Name', 'mt-ticket-bus'); ?></label></th>
                                <td>
                                    <input type="hidden" name="mt_ticket_bus_settings[show_bus_name]" value="no" />
                                    <label for="mt_ticket_bus_show_bus_name"><input type="checkbox" id="mt_ticket_bus_show_bus_name" name="mt_ticket_bus_settings[show_bus_name]" value="yes" <?php checked($show_bus_name, 'yes'); ?> /> <?php esc_html_e('Show bus name in ticket summary', 'mt-ticket-bus'); ?></label>
                                    <p class="description"><?php esc_html_e('Enable this option to display the bus name below the short description in the ticket summary block.', 'mt-ticket-bus'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="mt_ticket_bus_show_route_info"><?php esc_html_e('Display Route Info', 'mt-ticket-bus'); ?></label></th>
                                <td>
                                    <input type="hidden" name="mt_ticket_bus_settings[show_route_info]" value="no" />
                                    <label for="mt_ticket_bus_show_route_info"><input type="checkbox" id="mt_ticket_bus_show_route_info" name="mt_ticket_bus_settings[show_route_info]" value="yes" <?php checked($show_route_info, 'yes'); ?> /> <?php esc_html_e('Show route information in ticket summary', 'mt-ticket-bus'); ?></label>
                                    <p class="description"><?php esc_html_e('Enable this option to display the route stops (start, intermediate, end) below the bus name in the ticket summary block.', 'mt-ticket-bus'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="mt_ticket_bus_show_route_distance"><?php esc_html_e('Display Route Distance', 'mt-ticket-bus'); ?></label></th>
                                <td>
                                    <input type="hidden" name="mt_ticket_bus_settings[show_route_distance]" value="no" />
                                    <label for="mt_ticket_bus_show_route_distance"><input type="checkbox" id="mt_ticket_bus_show_route_distance" name="mt_ticket_bus_settings[show_route_distance]" value="yes" <?php checked($show_route_distance, 'yes'); ?> /> <?php esc_html_e('Show route distance in ticket summary', 'mt-ticket-bus'); ?></label>
                                    <p class="description"><?php esc_html_e('Enable this option to display the route distance below the route stops in the ticket summary block.', 'mt-ticket-bus'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-widget mt-settings-permissions-col">
                    <table class="form-table">
                        <tbody>
                            <?php
                            $show_route_duration = isset($settings['show_route_duration']) ? $settings['show_route_duration'] : 'yes';
                            $show_bus_extras = isset($settings['show_bus_extras']) ? $settings['show_bus_extras'] : 'yes';
                            $show_pay_extras = isset($settings['show_pay_extras']) ? $settings['show_pay_extras'] : 'yes';
                            $allow_buy_for_other = isset($settings['allow_buy_for_other']) ? $settings['allow_buy_for_other'] : 'yes';

                            $license_status = (isset($settings['license_status']) && is_array($settings['license_status'])) ? $settings['license_status'] : array();
                            $license_plan = isset($license_status['plan']) ? (string) $license_status['plan'] : 'free';
                            $license_activated = !empty($license_status['activated']);
                            $license_pro_active = ($license_activated && $license_plan === 'pro');
                            ?>
                            <tr>
                                <th scope="row"><label for="mt_ticket_bus_show_route_duration"><?php esc_html_e('Display Route Duration', 'mt-ticket-bus'); ?></label></th>
                                <td>
                                    <input type="hidden" name="mt_ticket_bus_settings[show_route_duration]" value="no" />
                                    <label for="mt_ticket_bus_show_route_duration"><input type="checkbox" id="mt_ticket_bus_show_route_duration" name="mt_ticket_bus_settings[show_route_duration]" value="yes" <?php checked($show_route_duration, 'yes'); ?> /> <?php esc_html_e('Show route duration in ticket summary', 'mt-ticket-bus'); ?></label>
                                    <p class="description"><?php esc_html_e('Enable this option to display the route duration (in minutes) below the route distance in the ticket summary block.', 'mt-ticket-bus'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="mt_ticket_bus_show_bus_extras"><?php esc_html_e('Display Bus Extras', 'mt-ticket-bus'); ?></label></th>
                                <td>
                                    <input type="hidden" name="mt_ticket_bus_settings[show_bus_extras]" value="no" />
                                    <label for="mt_ticket_bus_show_bus_extras"><input type="checkbox" id="mt_ticket_bus_show_bus_extras" name="mt_ticket_bus_settings[show_bus_extras]" value="yes" <?php checked($show_bus_extras, 'yes'); ?> /> <?php esc_html_e('Show bus extras in ticket summary', 'mt-ticket-bus'); ?></label>
                                    <p class="description"><?php esc_html_e('Enable this option to display the bus extras (features) below the route duration in the ticket summary block.', 'mt-ticket-bus'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="mt_ticket_bus_show_pay_extras"><?php esc_html_e('Display Paid Extras', 'mt-ticket-bus'); ?></label></th>
                                <td>
                                    <input type="hidden" name="mt_ticket_bus_settings[show_pay_extras]" value="no" />
                                    <label for="mt_ticket_bus_show_pay_extras">
                                        <input
                                            type="checkbox"
                                            id="mt_ticket_bus_show_pay_extras"
                                            name="mt_ticket_bus_settings[show_pay_extras]"
                                            value="yes"
                                            <?php checked($show_pay_extras, 'yes'); ?>
                                            <?php echo $license_pro_active ? '' : ' disabled="disabled"'; ?> />
                                        <?php esc_html_e('Allow paid extras on ticket products', 'mt-ticket-bus'); ?>
                                        <span class="description" style="display:inline-block; margin-left:6px; color:#555d66;">
                                            <?php esc_html_e('Available only in the Pro version of the plugin.', 'mt-ticket-bus'); ?>
                                        </span>
                                    </label>
                                    <p class="description"><?php esc_html_e('When enabled, you can assign paid extras to ticket products on the product edit page; customers can then add them when buying a ticket. When disabled, the extras field is hidden and existing extras are not offered.', 'mt-ticket-bus'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="mt_ticket_bus_allow_buy_for_other"><?php esc_html_e('Allow buying ticket for someone else', 'mt-ticket-bus'); ?></label></th>
                                <td>
                                    <input type="hidden" name="mt_ticket_bus_settings[allow_buy_for_other]" value="no" />
                                    <label for="mt_ticket_bus_allow_buy_for_other">
                                        <input
                                            type="checkbox"
                                            id="mt_ticket_bus_allow_buy_for_other"
                                            name="mt_ticket_bus_settings[allow_buy_for_other]"
                                            value="yes"
                                            <?php checked($allow_buy_for_other, 'yes'); ?>
                                            <?php echo $license_pro_active ? '' : ' disabled="disabled"'; ?> />
                                        <?php esc_html_e('Allow "Buy for someone else" on checkout', 'mt-ticket-bus'); ?>
                                        <span class="description" style="display:inline-block; margin-left:6px; color:#555d66;">
                                            <?php esc_html_e('Available only in the Pro version of the plugin.', 'mt-ticket-bus'); ?>
                                        </span>
                                    </label>
                                    <p class="description"><?php esc_html_e('When enabled, the checkout shows passenger fields (first name, last name, email, phone). Reservations and ticket emails use this passenger data when filled; otherwise billing data is used.', 'mt-ticket-bus'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="mt-settings-tab-appearance" class="mt-settings-tab-pane" role="tabpanel" <?php echo $active_tab !== 'appearance' ? ' hidden' : ''; ?>>
            <div class="mt-widget">
                <h3 class="mt-settings-appearance-title"><?php esc_html_e('Color palette', 'mt-ticket-bus'); ?></h3>
                <p class="description"><?php esc_html_e('Choose a preset palette for the ticket product page blocks, or pick a palette and then adjust individual colors below.', 'mt-ticket-bus'); ?></p>
                <div class="mt-settings-palette-radios">
                    <?php foreach ($palettes as $pid => $palette) : ?>
                        <label class="mt-palette-option">
                            <input type="radio" name="mt_ticket_bus_settings[appearance_palette]" value="<?php echo esc_attr($pid); ?>" <?php checked($appearance_palette, $pid); ?> class="mt-palette-radio" data-palette-id="<?php echo esc_attr($pid); ?>" />
                            <span><?php echo esc_html($palette['label']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <h4 class="mt-settings-appearance-colors-title"><?php esc_html_e('Individual colors', 'mt-ticket-bus'); ?></h4>
                <p class="description"><?php esc_html_e('Override any color below. These values are used when rendering the seatmap and ticket summary blocks on the product page.', 'mt-ticket-bus'); ?></p>
                <div class="mt-settings-color-grid" id="mt-settings-color-inputs">
                    <?php foreach ($color_keys as $key) :
                        $label = isset($color_labels[$key]) ? $color_labels[$key] : $key;
                        $value = isset($appearance_colors[$key]) ? $appearance_colors[$key] : '#3b82f6';
                    ?>
                        <div class="mt-settings-color-row">
                            <label for="mt_appearance_color_<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label>
                            <input type="color" id="mt_appearance_color_<?php echo esc_attr($key); ?>" name="mt_ticket_bus_settings[appearance_colors][<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($value); ?>" class="mt-appearance-color-input" data-color-key="<?php echo esc_attr($key); ?>" />
                            <input type="text" class="mt-appearance-color-hex small-text" value="<?php echo esc_attr($value); ?>" data-color-key="<?php echo esc_attr($key); ?>" maxlength="7" aria-label="<?php echo esc_attr($label); ?>" />
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php submit_button(__('Save Settings', 'mt-ticket-bus')); ?>
    </form>
</div>

<script>
    (function() {
        var nav = document.querySelector('.mt-settings-nav-tabs');
        var panes = document.querySelectorAll('.mt-settings-tab-pane');
        if (!nav || !panes.length) return;
        var activeTabInput = document.getElementById('mt_settings_active_tab');
        nav.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-mt-tab]');
            if (!btn) return;
            var tab = btn.getAttribute('data-mt-tab');
            nav.querySelectorAll('.nav-tab').forEach(function(t) {
                t.classList.remove('nav-tab-active');
            });
            btn.classList.add('nav-tab-active');
            panes.forEach(function(pane) {
                var id = pane.id;
                var paneTab = id && id.replace('mt-settings-tab-', '');
                pane.hidden = (paneTab !== tab);
            });
            if (activeTabInput) activeTabInput.value = tab;
        });

        var palettesData = <?php echo wp_json_encode(array_map(function ($p) {
                                return $p['colors'];
                            }, $palettes)); ?>;
        var radios = document.querySelectorAll('.mt-palette-radio');
        var colorInputs = document.querySelectorAll('.mt-appearance-color-input');
        var hexInputs = document.querySelectorAll('.mt-appearance-color-hex');

        function setColorInputsFromPalette(paletteId) {
            var colors = palettesData[paletteId];
            if (!colors) return;
            colorInputs.forEach(function(inp) {
                var key = inp.getAttribute('data-color-key');
                if (colors[key]) {
                    inp.value = colors[key];
                    var hexEl = document.querySelector('.mt-appearance-color-hex[data-color-key="' + key + '"]');
                    if (hexEl) hexEl.value = colors[key];
                }
            });
        }

        radios.forEach(function(radio) {
            radio.addEventListener('change', function() {
                setColorInputsFromPalette(this.getAttribute('data-palette-id'));
            });
        });

        function syncHexToColor(hexEl) {
            var key = hexEl.getAttribute('data-color-key');
            var val = hexEl.value.trim();
            if (/^#[0-9a-fA-F]{3}$/.test(val)) {
                val = '#' + val[1] + val[1] + val[2] + val[2] + val[3] + val[3];
            }
            if (/^#[0-9a-fA-F]{6}$/.test(val)) {
                var colorEl = document.querySelector('.mt-appearance-color-input[data-color-key="' + key + '"]');
                if (colorEl) colorEl.value = val;
            }
        }

        function syncColorToHex(colorEl) {
            var key = colorEl.getAttribute('data-color-key');
            var hexEl = document.querySelector('.mt-appearance-color-hex[data-color-key="' + key + '"]');
            if (hexEl) hexEl.value = colorEl.value;
        }

        colorInputs.forEach(function(inp) {
            inp.addEventListener('input', function() {
                syncColorToHex(this);
            });
        });
        hexInputs.forEach(function(inp) {
            inp.addEventListener('input', function() {
                syncHexToColor(this);
            });
            inp.addEventListener('change', function() {
                syncHexToColor(this);
            });
        });
    })();
</script>