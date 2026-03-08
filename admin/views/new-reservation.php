<?php

/**
 * New Reservation Admin Page Template
 *
 * Allows admin to create a reservation (WooCommerce order with ticket product) on behalf of
 * an existing customer or a guest. Includes product, date, course, seat selection and order status.
 *
 * @package MT_Ticket_Bus
 * @since 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

$ticket_products = get_posts(array(
    'post_type' => 'product',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC',
    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Ticket products by _mt_is_ticket_product; product count typically small.
    'meta_query' => array(
        array('key' => '_mt_is_ticket_product', 'value' => 'yes'),
    ),
));

$products_data = array();
foreach ($ticket_products as $p) {
    $products_data[$p->ID] = array(
        'name' => $p->post_title,
        'schedule_id' => (int) get_post_meta($p->ID, '_mt_bus_schedule_id', true),
        'bus_id' => (int) get_post_meta($p->ID, '_mt_bus_id', true),
        'route_id' => (int) get_post_meta($p->ID, '_mt_bus_route_id', true),
    );
}

$users = get_users(array(
    'orderby' => 'display_name',
    'role__in' => array('customer', 'subscriber', 'administrator'),
    'number' => 500,
));

$order_statuses = array(
    'pending' => __('Pending payment', 'mt-ticket-bus'),
    'processing' => __('Processing', 'mt-ticket-bus'),
    'on-hold' => __('On hold', 'mt-ticket-bus'),
    'completed' => __('Completed', 'mt-ticket-bus'),
);

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET param for error message display only (after redirect from form submit).
$error = isset($_GET['error']) ? sanitize_text_field(wp_unslash($_GET['error'])) : '';
?>

<div class="wrap mt-ticket-bus-new-reservation">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=mt-ticket-bus-reservations')); ?>" class="page-title-action"><?php esc_html_e('Reservations', 'mt-ticket-bus'); ?></a>
    <hr class="wp-header-end">

    <?php if ($error === 'missing') : ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('Please fill in product, date, course and at least one seat.', 'mt-ticket-bus'); ?></p>
        </div>
    <?php elseif ($error === 'guest') : ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('For guest orders please enter first name, last name and email.', 'mt-ticket-bus'); ?></p>
        </div>
    <?php elseif ($error === 'product' || $error === 'product_meta') : ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('Invalid or misconfigured ticket product.', 'mt-ticket-bus'); ?></p>
        </div>
    <?php elseif ($error === 'date_invalid') : ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('The selected date is not valid for the chosen schedule. Please select a date when the schedule runs.', 'mt-ticket-bus'); ?></p>
        </div>
    <?php elseif ($error === 'wc' || $error === 'create') : ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('Could not create order.', 'mt-ticket-bus'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="mt-new-reservation-form" style="max-width: 800px; margin-top: 20px;">
        <input type="hidden" name="action" value="mt_ticket_bus_create_reservation_order" />
        <?php wp_nonce_field('mt_ticket_bus_new_reservation', 'mt_new_reservation_nonce'); ?>

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="mt-customer-type"><?php esc_html_e('Customer', 'mt-ticket-bus'); ?></label></th>
                    <td>
                        <select id="mt-customer-type" name="customer_id" style="width: 100%; max-width: 320px;">
                            <option value="0"><?php esc_html_e('Guest', 'mt-ticket-bus'); ?></option>
                            <?php foreach ($users as $u) : ?>
                                <option value="<?php echo esc_attr($u->ID); ?>"><?php echo esc_html($u->display_name . ' (' . $u->user_email . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e('Select an existing customer or Guest to enter name and email.', 'mt-ticket-bus'); ?></p>
                    </td>
                </tr>
                <tr id="mt-guest-fields">
                    <th scope="row"><?php esc_html_e('Guest details', 'mt-ticket-bus'); ?></th>
                    <td>
                        <fieldset style="display: flex; flex-wrap: wrap; gap: 12px 16px;">
                            <label>
                                <span class="screen-reader-text"><?php esc_html_e('First name', 'mt-ticket-bus'); ?></span>
                                <input type="text" name="guest_first_name" id="mt-guest-first" placeholder="<?php esc_attr_e('First name', 'mt-ticket-bus'); ?>" style="width: 140px;" />
                            </label>
                            <label>
                                <span class="screen-reader-text"><?php esc_html_e('Last name', 'mt-ticket-bus'); ?></span>
                                <input type="text" name="guest_last_name" id="mt-guest-last" placeholder="<?php esc_attr_e('Last name', 'mt-ticket-bus'); ?>" style="width: 140px;" />
                            </label>
                            <label>
                                <span class="screen-reader-text"><?php esc_html_e('Email', 'mt-ticket-bus'); ?></span>
                                <input type="email" name="guest_email" id="mt-guest-email" placeholder="<?php esc_attr_e('Email', 'mt-ticket-bus'); ?>" style="width: 200px;" />
                            </label>
                            <label>
                                <span class="screen-reader-text"><?php esc_html_e('Phone', 'mt-ticket-bus'); ?></span>
                                <input type="text" name="guest_phone" id="mt-guest-phone" placeholder="<?php esc_attr_e('Phone', 'mt-ticket-bus'); ?>" style="width: 140px;" />
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="mt-product-id"><?php esc_html_e('Product / Ticket', 'mt-ticket-bus'); ?></label></th>
                    <td>
                        <select id="mt-product-id" name="product_id" required style="width: 100%; max-width: 320px;">
                            <option value=""><?php esc_html_e('— Select product —', 'mt-ticket-bus'); ?></option>
                            <?php foreach ($ticket_products as $p) : ?>
                                <option value="<?php echo esc_attr($p->ID); ?>"><?php echo esc_html($p->post_title); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="mt-departure-date"><?php esc_html_e('Departure date', 'mt-ticket-bus'); ?></label></th>
                    <td>
                        <input type="date" id="mt-departure-date" name="departure_date" required min="<?php echo esc_attr(gmdate('Y-m-d')); ?>" style="width: 160px;" disabled="disabled" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="mt-departure-time"><?php esc_html_e('Course / Time', 'mt-ticket-bus'); ?></label></th>
                    <td>
                        <select id="mt-departure-time" name="departure_time" required style="width: 100%; max-width: 200px;">
                            <option value=""><?php esc_html_e('— Select product and date first —', 'mt-ticket-bus'); ?></option>
                        </select>
                        <span id="mt-courses-loading" class="spinner is-active" style="float: none; margin-left: 8px; display: none;"></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label><?php esc_html_e('Seats', 'mt-ticket-bus'); ?></label></th>
                    <td>
                        <p class="description"><?php esc_html_e('Select product, date and course, then choose one or more seats below.', 'mt-ticket-bus'); ?></p>
                        <div id="mt-seatmap-container" style="margin-top: 12px; min-height: 120px;"></div>
                        <div id="mt-seats-inputs"></div>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="mt-order-status"><?php esc_html_e('Order status', 'mt-ticket-bus'); ?></label></th>
                    <td>
                        <select id="mt-order-status" name="order_status" style="width: 100%; max-width: 200px;">
                            <?php foreach ($order_statuses as $status => $label) : ?>
                                <option value="<?php echo esc_attr($status); ?>" <?php selected($status, 'pending'); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary" id="mt-submit-reservation"><?php esc_html_e('Create reservation', 'mt-ticket-bus'); ?></button>
        </p>
    </form>
</div>

<script>
    window.mtNewReservationData = {
        products: <?php echo wp_json_encode($products_data); ?>,
        ajaxUrl: <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>,
        nonce: <?php echo wp_json_encode(wp_create_nonce('mt_ticket_bus_admin')); ?>,
        i18n: {
            loadingDates: <?php echo wp_json_encode(__('Loading available dates…', 'mt-ticket-bus')); ?>,
            selectValidDate: <?php echo wp_json_encode(__('Select a date valid for this schedule.', 'mt-ticket-bus')); ?>,
            noDatesForSchedule: <?php echo wp_json_encode(__('No available dates for this schedule.', 'mt-ticket-bus')); ?>,
            dateNotAvailable: <?php echo wp_json_encode(__('This date is not available for the selected schedule.', 'mt-ticket-bus')); ?>
        }
    };
</script>