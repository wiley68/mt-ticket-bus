<?php

/**
 * Routes management page template
 *
 * @package MT_Ticket_Bus
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

$routes = MT_Ticket_Bus_Routes::get_instance()->get_all_routes();
$edit_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
$edit_route = $edit_id ? MT_Ticket_Bus_Routes::get_instance()->get_route($edit_id) : null;
?>

<div class="wrap mt-ticket-bus-routes">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

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
                                <label for="end_station"><?php esc_html_e('End Station', 'mt-ticket-bus'); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <input type="text" id="end_station" name="end_station" value="<?php echo $edit_route ? esc_attr($edit_route->end_station) : ''; ?>" class="regular-text" required />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="intermediate_stations"><?php esc_html_e('Intermediate Stations', 'mt-ticket-bus'); ?></label>
                            </th>
                            <td>
                                <textarea id="intermediate_stations" name="intermediate_stations" rows="5" class="large-text"><?php echo $edit_route ? esc_textarea($edit_route->intermediate_stations) : ''; ?></textarea>
                                <p class="description"><?php esc_html_e('List intermediate stations, one per line.', 'mt-ticket-bus'); ?></p>
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
                            <th><?php esc_html_e('Name', 'mt-ticket-bus'); ?></th>
                            <th><?php esc_html_e('Start Station', 'mt-ticket-bus'); ?></th>
                            <th><?php esc_html_e('End Station', 'mt-ticket-bus'); ?></th>
                            <th><?php esc_html_e('Distance', 'mt-ticket-bus'); ?></th>
                            <th><?php esc_html_e('Status', 'mt-ticket-bus'); ?></th>
                            <th><?php esc_html_e('Actions', 'mt-ticket-bus'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($routes as $route) : ?>
                            <tr>
                                <td><?php echo esc_html($route->id); ?></td>
                                <td><?php echo esc_html($route->name); ?></td>
                                <td><?php echo esc_html($route->start_station); ?></td>
                                <td><?php echo esc_html($route->end_station); ?></td>
                                <td><?php echo esc_html($route->distance); ?> km</td>
                                <td><?php echo esc_html(ucfirst($route->status)); ?></td>
                                <td>
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