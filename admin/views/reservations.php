<?php

/**
 * Reservations management page template
 *
 * @package MT_Ticket_Bus
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

$routes = MT_Ticket_Bus_Routes::get_instance()->get_all_routes(array('status' => 'all'));
$selected_date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : date('Y-m-d');
$selected_route_id = isset($_GET['route_id']) ? absint($_GET['route_id']) : 0;
$selected_schedule_id = isset($_GET['schedule_id']) ? absint($_GET['schedule_id']) : 0;
$selected_departure_time = isset($_GET['departure_time']) ? sanitize_text_field($_GET['departure_time']) : '';

// Get schedules for selected route
$schedules = array();
if ($selected_route_id > 0) {
    $schedules = MT_Ticket_Bus_Schedules::get_instance()->get_schedules_by_route($selected_route_id, array('status' => 'all'));
}

// Get selected schedule and courses
$selected_schedule = null;
$courses = array();
if ($selected_schedule_id > 0) {
    $selected_schedule = MT_Ticket_Bus_Schedules::get_instance()->get_schedule($selected_schedule_id);
    if ($selected_schedule && !empty($selected_schedule->courses)) {
        $courses = json_decode($selected_schedule->courses, true);
        if (!is_array($courses)) {
            $courses = array();
        }
    }
}

// Get bus and reservations data if all filters are selected
$bus = null;
$reservations = array();
$reserved_seats = array();
if ($selected_date && $selected_route_id > 0 && $selected_schedule_id > 0 && $selected_departure_time) {
    // Get reservations for this date/schedule/time
    $reservations = MT_Ticket_Bus_Reservations::get_instance()->get_all_reservations(array(
        'schedule_id' => $selected_schedule_id,
        'departure_date' => $selected_date,
        'status' => ''
    ));

    // Filter reservations by departure time and get bus_id from first reservation
    // Also create a map of seat_number => reservation for quick lookup
    $reserved_seats = array();
    $reservations_by_seat = array();
    $bus_id = null;
    foreach ($reservations as $reservation) {
        $reservation_time = date('H:i', strtotime($reservation->departure_time));
        if ($reservation_time === $selected_departure_time) {
            if (in_array($reservation->status, array('reserved', 'confirmed'))) {
                $reserved_seats[] = $reservation->seat_number;
                // Add order date to reservation object
                $reservation_data = (array) $reservation;
                // Initialize order_notes as empty string
                $reservation_data['order_notes'] = '';

                if (!empty($reservation->order_id)) {
                    $order = wc_get_order($reservation->order_id);
                    if ($order) {
                        // Get order date - try get_date_created first, fallback to post date
                        $order_date = null;
                        // Use reflection or direct call with error suppression for linter compatibility
                        $order_date_obj = @$order->get_date_created();
                        if ($order_date_obj && method_exists($order_date_obj, 'date')) {
                            $order_date = $order_date_obj->date('Y-m-d H:i:s');
                        }
                        // Fallback to post date if get_date_created is not available
                        if (!$order_date) {
                            $order_post = get_post($reservation->order_id);
                            if ($order_post && isset($order_post->post_date)) {
                                $order_date = $order_post->post_date;
                            }
                        }
                        if ($order_date) {
                            $reservation_data['order_date'] = $order_date;
                        }
                        // Get order status with translation
                        $order_status = $order->get_status();
                        if ($order_status) {
                            $reservation_data['order_status'] = $order_status;
                            // Get translated status name
                            $order_status_name = wc_get_order_status_name($order_status);
                            if ($order_status_name) {
                                $reservation_data['order_status_name'] = $order_status_name;
                            }
                        }
                        // Get payment method
                        $payment_method_title = $order->get_payment_method_title();
                        if ($payment_method_title) {
                            $reservation_data['payment_method'] = $payment_method_title;
                        }
                        // Get order notes
                        $notes_text = array();

                        // Get customer note if exists
                        $customer_note = $order->get_customer_note();
                        if (!empty($customer_note)) {
                            $notes_text[] = trim($customer_note);
                        }

                        // Get order notes (system notes) - try wc_get_order_notes first
                        $order_notes = array();
                        if (function_exists('wc_get_order_notes')) {
                            $order_notes = @wc_get_order_notes(array(
                                'order_id' => $reservation->order_id,
                                'limit' => 50
                            ));
                        }

                        // Fallback to get_comments if wc_get_order_notes doesn't work
                        if (empty($order_notes) || !is_array($order_notes)) {
                            $order_notes = get_comments(array(
                                'post_id' => $reservation->order_id,
                                'status' => 'approve',
                                'type' => 'order_note',
                                'number' => 50,
                                'orderby' => 'comment_date',
                                'order' => 'DESC'
                            ));
                        }

                        // Process order notes
                        if (!empty($order_notes) && is_array($order_notes)) {
                            foreach ($order_notes as $note) {
                                if (!is_object($note)) {
                                    continue;
                                }

                                // Try different ways to get note content
                                $note_content = '';

                                // Method 1: comment_content (WP_Comment)
                                if (isset($note->comment_content) && !empty(trim($note->comment_content))) {
                                    $note_content = trim($note->comment_content);
                                }
                                // Method 2: content property
                                elseif (isset($note->content) && !empty(trim($note->content))) {
                                    $note_content = trim($note->content);
                                }
                                // Method 3: get_content() method
                                elseif (method_exists($note, 'get_content')) {
                                    $note_content = trim($note->get_content());
                                }
                                // Method 4: comment_text property
                                elseif (isset($note->comment_text) && !empty(trim($note->comment_text))) {
                                    $note_content = trim($note->comment_text);
                                }

                                if (!empty($note_content)) {
                                    $notes_text[] = $note_content;
                                }
                            }
                        }

                        // Store all notes (update order_notes if we have any)
                        if (!empty($notes_text)) {
                            $reservation_data['order_notes'] = implode("\n", $notes_text);
                        }
                    }
                }
                $reservations_by_seat[$reservation->seat_number] = (object) $reservation_data;
            }
            // Get bus_id from first matching reservation
            if ($bus_id === null && !empty($reservation->bus_id)) {
                $bus_id = $reservation->bus_id;
            }
        }
    }

    // If no reservations found, try to get bus from products using this schedule
    if ($bus_id === null) {
        // Get products that use this schedule
        $products = get_posts(array(
            'post_type' => 'product',
            'meta_key' => '_mt_bus_schedule_id',
            'meta_value' => $selected_schedule_id,
            'posts_per_page' => 1
        ));
        if (!empty($products)) {
            $bus_id = get_post_meta($products[0]->ID, '_mt_bus_id', true);
        }
    }

    // Get bus if we have bus_id
    if ($bus_id) {
        $bus = MT_Ticket_Bus_Buses::get_instance()->get_bus($bus_id);
    }
}
?>

<div class="wrap mt-ticket-bus-reservations" style="padding-bottom: 80px;">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    <hr class="wp-header-end">

    <!-- Filters -->
    <div class="mt-reservations-filters" style="background: #fff; padding: 15px 20px; margin: 20px 0; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0, 0, 0, .04);">
        <form method="get" action="" id="mt-reservations-filter-form" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
            <input type="hidden" name="page" value="mt-ticket-bus-reservations" />

            <input type="date" id="date" name="date" value="<?php echo esc_attr($selected_date); ?>" class="regular-text" required style="flex: 0 0 auto;" />

            <select id="route_id" name="route_id" class="regular-text" required style="flex: 0 0 auto; min-width: 200px;">
                <option value=""><?php esc_html_e('-- Select Route --', 'mt-ticket-bus'); ?></option>
                <?php foreach ($routes as $route) : ?>
                    <option value="<?php echo esc_attr($route->id); ?>" <?php selected($selected_route_id, $route->id); ?>>
                        <?php echo esc_html($route->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select id="schedule_id" name="schedule_id" class="regular-text" <?php echo $selected_route_id > 0 ? '' : 'disabled'; ?> required style="flex: 0 0 auto; min-width: 200px;">
                <option value=""><?php esc_html_e('-- Select Schedule --', 'mt-ticket-bus'); ?></option>
                <?php if ($selected_route_id > 0) : ?>
                    <?php foreach ($schedules as $schedule) : ?>
                        <option value="<?php echo esc_attr($schedule->id); ?>" <?php selected($selected_schedule_id, $schedule->id); ?>>
                            <?php echo esc_html($schedule->name ?: __('Schedule', 'mt-ticket-bus') . ' #' . $schedule->id); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>

            <select id="departure_time" name="departure_time" class="regular-text" <?php echo $selected_schedule_id > 0 ? '' : 'disabled'; ?> required style="flex: 0 0 auto; min-width: 200px;">
                <option value=""><?php esc_html_e('-- Select Course --', 'mt-ticket-bus'); ?></option>
                <?php if ($selected_schedule_id > 0 && !empty($courses)) : ?>
                    <?php foreach ($courses as $course) : ?>
                        <?php
                        $departure_time = isset($course['departure_time']) ? $course['departure_time'] : '';
                        $arrival_time = isset($course['arrival_time']) ? $course['arrival_time'] : '';
                        $time_display = $departure_time . ($arrival_time ? ' → ' . $arrival_time : '');
                        $time_value = date('H:i', strtotime($departure_time));
                        ?>
                        <option value="<?php echo esc_attr($time_value); ?>" <?php selected($selected_departure_time, $time_value); ?>>
                            <?php echo esc_html($time_display); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>

            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e('Show Reservations', 'mt-ticket-bus'); ?>" style="flex: 0 0 auto;" />
        </form>
    </div>

    <!-- Bus Information and Seat Map -->
    <?php if ($bus && $selected_date && $selected_route_id > 0 && $selected_schedule_id > 0 && $selected_departure_time) : ?>
        <div class="mt-reservations-display" style="display: flex; gap: 20px; margin: 20px 0;">
            <!-- Left: Bus Information and Seat Map -->
            <div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0, 0, 0, .04);">
                <h2><?php esc_html_e('Bus Information', 'mt-ticket-bus'); ?></h2>

                <div style="line-height: 1.5;">
                    <div style="margin-bottom: 8px;">
                        <strong><?php esc_html_e('Bus Name', 'mt-ticket-bus'); ?>:</strong> <?php echo esc_html($bus->name); ?>
                    </div>
                    <div style="margin-bottom: 8px;">
                        <strong><?php esc_html_e('Registration Number', 'mt-ticket-bus'); ?>:</strong> <?php echo esc_html($bus->registration_number); ?>
                    </div>
                    <div style="margin-bottom: 8px;">
                        <strong><?php esc_html_e('Total Seats', 'mt-ticket-bus'); ?>:</strong> <?php echo esc_html($bus->total_seats); ?>
                    </div>
                </div>

                <h2 style="margin-top: 20px;"><?php esc_html_e('Seat Map', 'mt-ticket-bus'); ?></h2>

                <div class="mt-seat-map-container">
                    <div id="mt-reservations-seat-layout"
                        data-seat-layout="<?php echo esc_attr($bus->seat_layout); ?>"
                        data-reserved-seats="<?php echo esc_attr(json_encode($reserved_seats)); ?>"
                        data-reservations="<?php echo esc_attr(json_encode($reservations_by_seat)); ?>"
                        data-date="<?php echo esc_attr($selected_date); ?>"
                        data-schedule-id="<?php echo esc_attr($selected_schedule_id); ?>"
                        data-departure-time="<?php echo esc_attr($selected_departure_time); ?>">
                        <div class="mt-seat-layout-loading"><?php esc_html_e('Loading layout...', 'mt-ticket-bus'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Right: Reservation Information -->
            <div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0, 0, 0, .04);">
                <h2><?php esc_html_e('Reservation Information', 'mt-ticket-bus'); ?></h2>
                <div id="mt-reservation-details">
                    <p class="description"><?php esc_html_e('Click on a reserved seat to view reservation details.', 'mt-ticket-bus'); ?></p>
                </div>
            </div>
        </div>
</div>
<?php elseif ($selected_date || $selected_route_id > 0 || $selected_schedule_id > 0 || $selected_departure_time) : ?>
    <div class="notice notice-info">
        <p><?php esc_html_e('Please select all filters (Date, Route, Schedule, and Course) to view reservations.', 'mt-ticket-bus'); ?></p>
    </div>
<?php endif; ?>
</div>