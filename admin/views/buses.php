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
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

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
                                <label for="registration_number"><?php esc_html_e('Registration Number', 'mt-ticket-bus'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="registration_number" name="registration_number" value="<?php echo $edit_bus ? esc_attr($edit_bus->registration_number) : ''; ?>" class="regular-text" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="total_seats"><?php esc_html_e('Total Seats', 'mt-ticket-bus'); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <input type="number" id="total_seats" name="total_seats" value="<?php echo $edit_bus ? esc_attr($edit_bus->total_seats) : ''; ?>" class="small-text" min="1" required />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="seat_layout"><?php esc_html_e('Seat Layout', 'mt-ticket-bus'); ?></label>
                            </th>
                            <td>
                                <textarea id="seat_layout" name="seat_layout" rows="5" class="large-text"><?php echo $edit_bus ? esc_textarea($edit_bus->seat_layout) : ''; ?></textarea>
                                <p class="description"><?php esc_html_e('JSON or text description of seat layout.', 'mt-ticket-bus'); ?></p>
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
                            <th><?php esc_html_e('Name', 'mt-ticket-bus'); ?></th>
                            <th><?php esc_html_e('Registration', 'mt-ticket-bus'); ?></th>
                            <th><?php esc_html_e('Total Seats', 'mt-ticket-bus'); ?></th>
                            <th><?php esc_html_e('Status', 'mt-ticket-bus'); ?></th>
                            <th><?php esc_html_e('Actions', 'mt-ticket-bus'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($buses as $bus) : ?>
                            <tr>
                                <td><?php echo esc_html($bus->id); ?></td>
                                <td><?php echo esc_html($bus->name); ?></td>
                                <td><?php echo esc_html($bus->registration_number); ?></td>
                                <td><?php echo esc_html($bus->total_seats); ?></td>
                                <td><?php echo esc_html(ucfirst($bus->status)); ?></td>
                                <td>
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