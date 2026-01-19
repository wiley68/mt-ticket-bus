<?php

/**
 * Buses management page template
 *
 * @package MT_Ticket_Bus
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

$buses = MT_Ticket_Bus_Buses::get_instance()->get_all_buses();
$edit_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
$edit_bus = $edit_id ? MT_Ticket_Bus_Buses::get_instance()->get_bus($edit_id) : null;
?>

<div class="wrap mt-ticket-bus-buses">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=mt-ticket-bus-buses')); ?>" class="page-title-action"><?php esc_html_e('New Bus', 'mt-ticket-bus'); ?></a>
    <hr class="wp-header-end">

    <?php
    // Show success message after save
    if (isset($_GET['saved']) && $_GET['saved'] == '1') {
        $edit_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
        $message = $edit_id > 0
            ? __('Bus updated successfully.', 'mt-ticket-bus')
            : __('Bus created successfully.', 'mt-ticket-bus');
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
    }
    ?>

    <div class="mt-buses-container">
        <div class="mt-buses-form">
            <h2><?php echo $edit_bus ? esc_html__('Edit Bus', 'mt-ticket-bus') : esc_html__('Add New Bus', 'mt-ticket-bus'); ?></h2>

            <form id="mt-bus-form" method="post">
                <?php wp_nonce_field('mt_ticket_bus_admin', 'nonce'); ?>
                <input type="hidden" name="action" value="mt_save_bus" />
                <?php if ($edit_bus) : ?>
                    <input type="hidden" name="id" value="<?php echo esc_attr($edit_id); ?>" />
                <?php endif; ?>

                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="bus_name"><?php esc_html_e('Bus Name', 'mt-ticket-bus'); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <input type="text" id="bus_name" name="name" value="<?php echo $edit_bus ? esc_attr($edit_bus->name) : ''; ?>" class="regular-text" required />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="registration_number"><?php esc_html_e('Registration Number', 'mt-ticket-bus'); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <input type="text" id="registration_number" name="registration_number" value="<?php echo $edit_bus ? esc_attr($edit_bus->registration_number) : ''; ?>" class="regular-text" required />
                                <p class="description" id="registration_number_error" style="color: #dc3232; display: none;"></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label><?php esc_html_e('Seat Layout Configuration', 'mt-ticket-bus'); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <?php
                                $layout_config = array('left' => 0, 'right' => 0, 'rows' => 10);
                                if ($edit_bus && !empty($edit_bus->seat_layout)) {
                                    $parsed_layout = json_decode($edit_bus->seat_layout, true);
                                    if (isset($parsed_layout['config'])) {
                                        $layout_config = wp_parse_args($parsed_layout['config'], $layout_config);
                                    }
                                }
                                ?>
                                <table class="mt-seat-config-table">
                                    <tr>
                                        <td>
                                            <label for="left_column_seats"><?php esc_html_e('Left column, number of seats:', 'mt-ticket-bus'); ?></label>
                                            <select id="left_column_seats" name="left_column_seats" required>
                                                <option value="0" <?php selected($layout_config['left'], 0); ?>>0</option>
                                                <option value="1" <?php selected($layout_config['left'], 1); ?>>1</option>
                                                <option value="2" <?php selected($layout_config['left'], 2); ?>>2</option>
                                                <option value="3" <?php selected($layout_config['left'], 3); ?>>3</option>
                                            </select>
                                        </td>
                                        <td>
                                            <label for="right_column_seats"><?php esc_html_e('Right column, number of seats:', 'mt-ticket-bus'); ?></label>
                                            <select id="right_column_seats" name="right_column_seats" required>
                                                <option value="0" <?php selected($layout_config['right'], 0); ?>>0</option>
                                                <option value="1" <?php selected($layout_config['right'], 1); ?>>1</option>
                                                <option value="2" <?php selected($layout_config['right'], 2); ?>>2</option>
                                                <option value="3" <?php selected($layout_config['right'], 3); ?>>3</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="3">
                                            <label for="number_of_rows"><?php esc_html_e('Number of rows:', 'mt-ticket-bus'); ?></label>
                                            <input type="number" id="number_of_rows" name="number_of_rows" value="<?php echo esc_attr($layout_config['rows']); ?>" min="1" max="100" class="small-text" required />
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label><?php esc_html_e('Seat Layout', 'mt-ticket-bus'); ?></label>
                            </th>
                            <td>
                                <div id="mt-seat-layout-container" class="mt-seat-layout-container"></div>
                                <input type="hidden" id="seat_layout" name="seat_layout" value="<?php echo $edit_bus ? esc_attr($edit_bus->seat_layout) : ''; ?>" />
                                <p class="description"><?php esc_html_e('Click on seats to enable/disable them. Green = available, Red = disabled.', 'mt-ticket-bus'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="total_seats"><?php esc_html_e('Total Seats', 'mt-ticket-bus'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="total_seats" name="total_seats" value="<?php echo $edit_bus ? esc_attr($edit_bus->total_seats) : '0'; ?>" class="small-text" min="0" readonly />
                                <p class="description"><?php esc_html_e('Automatically calculated from seat layout.', 'mt-ticket-bus'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="features"><?php esc_html_e('Features', 'mt-ticket-bus'); ?></label>
                            </th>
                            <td>
                                <textarea id="features" name="features" rows="5" class="large-text"><?php echo $edit_bus ? esc_textarea($edit_bus->features) : ''; ?></textarea>
                                <p class="description"><?php esc_html_e('Bus features and amenities.', 'mt-ticket-bus'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="status"><?php esc_html_e('Status', 'mt-ticket-bus'); ?></label>
                            </th>
                            <td>
                                <select id="status" name="status">
                                    <option value="active" <?php selected($edit_bus ? $edit_bus->status : 'active', 'active'); ?>><?php esc_html_e('Active', 'mt-ticket-bus'); ?></option>
                                    <option value="inactive" <?php selected($edit_bus ? $edit_bus->status : '', 'inactive'); ?>><?php esc_html_e('Inactive', 'mt-ticket-bus'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button($edit_bus ? __('Update Bus', 'mt-ticket-bus') : __('Add Bus', 'mt-ticket-bus')); ?>
            </form>
        </div>

        <div class="mt-buses-list">
            <h2><?php esc_html_e('Buses List', 'mt-ticket-bus'); ?></h2>

            <?php if (empty($buses)) : ?>
                <p><?php esc_html_e('No buses found.', 'mt-ticket-bus'); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('ID', 'mt-ticket-bus'); ?></th>
                            <th class="mt-bus-name-col"><?php esc_html_e('Name', 'mt-ticket-bus'); ?></th>
                            <th><?php esc_html_e('Registration', 'mt-ticket-bus'); ?></th>
                            <th><?php esc_html_e('Total Seats', 'mt-ticket-bus'); ?></th>
                            <th class="mt-bus-actions"><?php esc_html_e('Actions', 'mt-ticket-bus'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($buses as $bus) : ?>
                            <tr class="<?php echo esc_attr($bus->status === 'inactive' ? 'mt-bus-inactive' : ''); ?>">
                                <td><?php echo esc_html($bus->id); ?></td>
                                <td class="mt-bus-name-col"><?php echo esc_html($bus->name); ?></td>
                                <td><?php echo esc_html($bus->registration_number); ?></td>
                                <td><?php echo esc_html($bus->total_seats); ?></td>
                                <td class="mt-bus-actions">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=mt-ticket-bus-buses&edit=' . $bus->id)); ?>"><?php esc_html_e('Edit', 'mt-ticket-bus'); ?></a> |
                                    <a href="#" class="mt-delete-bus" data-id="<?php echo esc_attr($bus->id); ?>"><?php esc_html_e('Delete', 'mt-ticket-bus'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>