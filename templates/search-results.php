<?php

/**
 * Search Results Template
 *
 * This template displays the search results page for bus ticket searches.
 * It shows available tickets matching the search criteria (from/to stations,
 * date range) with details such as route, departure/arrival times, price,
 * available seats, and allows users to select seats and add tickets to cart.
 *
 * Expected GET parameters:
 * - from (string) Starting station name
 * - to (string) Destination station name
 * - date_from (string) Start date for search range (Y-m-d format)
 * - date_to (string) End date for search range (Y-m-d format)
 *
 * The template uses the search_tickets method from MT_Ticket_Bus_Shortcode_Search
 * class to retrieve matching tickets and displays them in a list format with
 * interactive seat selection and cart functionality.
 *
 * Each result item contains:
 * - Route information (name, stations)
 * - Departure and arrival times
 * - Price information
 * - Available seats count
 * - Seat map (loaded dynamically via AJAX)
 * - Action buttons (Add to Cart, Buy Now)
 *
 * @package MT_Ticket_Bus
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get search parameters
$from = isset($_GET['from']) ? sanitize_text_field($_GET['from']) : '';
$to = isset($_GET['to']) ? sanitize_text_field($_GET['to']) : '';
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';

if (empty($from) || empty($to) || empty($date_from) || empty($date_to)) {
    wp_die(esc_html__('Invalid search parameters.', 'mt-ticket-bus'));
}

// Perform search
$search_instance = MT_Ticket_Bus_Shortcode_Search::get_instance();
$reflection = new ReflectionClass($search_instance);
$search_method = $reflection->getMethod('search_tickets');
// Note: setAccessible() is deprecated in PHP 8.1+ as all methods are accessible by default
$results = $search_method->invoke($search_instance, $from, $to, $date_from, $date_to);

// Format dates for display
$date_from_formatted = '';
$date_to_formatted = '';
if ($date_from) {
    $date_from_ts = strtotime($date_from);
    if ($date_from_ts !== false) {
        $date_from_formatted = date_i18n(get_option('date_format'), $date_from_ts);
    }
}
if ($date_to) {
    $date_to_ts = strtotime($date_to);
    if ($date_to_ts !== false) {
        $date_to_formatted = date_i18n(get_option('date_format'), $date_to_ts);
    }
}

get_header();
?>

<div class="mt-search-results-container">
    <div class="mt-search-results-header">
        <h1><?php esc_html_e('Search Results', 'mt-ticket-bus'); ?></h1>
        <div class="mt-search-results-info">
            <?php
            echo esc_html(sprintf(
                /* translators: 1: departure station name, 2: arrival station name */
                __('From: %1$s to %2$s', 'mt-ticket-bus'),
                $from,
                $to
            ));
            ?>
            <?php if ($date_from_formatted && $date_to_formatted) : ?>
                <br>
                <?php
                if ($date_from_formatted === $date_to_formatted) {
                    /* translators: %s: formatted date */
                    echo esc_html(sprintf(__('Date: %s', 'mt-ticket-bus'), $date_from_formatted));
                } else {
                    echo esc_html(sprintf(
                        /* translators: 1: start date, 2: end date */
                        __('Date range: %1$s - %2$s', 'mt-ticket-bus'),
                        $date_from_formatted,
                        $date_to_formatted
                    ));
                }
                ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($results)) : ?>
        <div class="mt-no-results">
            <h2><?php esc_html_e('No tickets found', 'mt-ticket-bus'); ?></h2>
            <p><?php esc_html_e('Please try different search criteria.', 'mt-ticket-bus'); ?></p>
        </div>
    <?php else : ?>
        <div class="mt-search-results-list">
            <?php foreach ($results as $result) : ?>
                <?php
                // Get route info
                $routes = MT_Ticket_Bus_Routes::get_instance();
                $route = $routes->get_route($result['route_id']);
                $route_name = $route ? $route->name : '';

                // Full route stations (start → intermediates → end) for display and segment pricing
                $route_stations_parts = array();
                $route_stops          = array();
                $has_segment_pricing  = false;
                if ($route) {
                    $start_name = ! empty($route->start_station) ? $route->start_station : '';
                    if ($start_name !== '') {
                        $route_stations_parts[] = $start_name;
                    }
                    // Build route stops: start 0%, intermediates with price_percent, end 100%.
                    if ($start_name !== '') {
                        $route_stops[] = array(
                            'name'    => $start_name,
                            'percent' => 0,
                        );
                    }

                    if (! empty($route->intermediate_stations)) {
                        $decoded = json_decode($route->intermediate_stations, true);
                        if (is_array($decoded) && ! empty($decoded)) {
                            foreach ($decoded as $station) {
                                $name = is_array($station) && isset($station['name']) ? $station['name'] : (is_string($station) ? $station : '');
                                if ($name === '') {
                                    continue;
                                }
                                $route_stations_parts[] = $name;
                                $pct = isset($station['price_percent']) ? max(0, min(100, round((float) $station['price_percent'], 2))) : 0;
                                if ($pct > 0) {
                                    $has_segment_pricing = true;
                                }
                                $route_stops[] = array(
                                    'name'    => $name,
                                    'percent' => $pct,
                                );
                            }
                        } else {
                            // Legacy format: plain text, no pricing
                            $intermediate = array_filter(array_map('trim', explode("\n", $route->intermediate_stations)));
                            foreach ($intermediate as $station_name) {
                                if ($station_name === '') {
                                    continue;
                                }
                                $route_stations_parts[] = $station_name;
                                $route_stops[]          = array(
                                    'name'    => $station_name,
                                    'percent' => 0,
                                );
                            }
                        }
                    }

                    $end_name = ! empty($route->end_station) ? $route->end_station : '';
                    if ($end_name !== '') {
                        $route_stations_parts[] = $end_name;
                        $route_stops[]          = array(
                            'name'    => $end_name,
                            'percent' => 100,
                        );
                    }
                }
                $route_stations_display = ! empty($route_stations_parts) ? implode(' → ', $route_stations_parts) : $from . ' → ' . $to;

                // Valid segment indices (same logic as product page)
                $valid_start_indices = array();
                $valid_end_indices   = array();
                $last_stop_idx       = count($route_stops) > 0 ? count($route_stops) - 1 : 0;
                $n_stops             = count($route_stops);
                if ($n_stops > 0) {
                    for ($i = 0; $i < $n_stops; $i++) {
                        $pct = isset($route_stops[$i]['percent']) ? (float) $route_stops[$i]['percent'] : 0.0;
                        if ($i === 0 || ($i < $last_stop_idx && $pct > 0)) {
                            $valid_start_indices[] = $i;
                        }
                        if ($i === $last_stop_idx || ($i > 0 && $pct > 0)) {
                            $valid_end_indices[] = $i;
                        }
                    }
                }

                // Get bus info
                $buses = MT_Ticket_Bus_Buses::get_instance();
                $bus = $buses->get_bus($result['bus_id']);
                $bus_name = $bus ? $bus->name : '';
                $bus_registration = $bus ? $bus->registration_number : '';

                // Format departure date
                $departure_date_formatted = '';
                $departure_date_ts = strtotime($result['departure_date']);
                if ($departure_date_ts !== false) {
                    $departure_date_formatted = date_i18n(get_option('date_format'), $departure_date_ts);
                }

                // Get available seats count
                $reservations = MT_Ticket_Bus_Reservations::get_instance();
                $available_seats_list = $reservations->get_available_seats(
                    $result['schedule_id'],
                    $result['departure_date'],
                    $result['departure_time'],
                    $result['bus_id']
                );
                $available_seats = count($available_seats_list);

                // Get total seats from bus
                $total_seats = 0;
                if ($bus) {
                    $total_seats = (int) $bus->total_seats;
                    if ($total_seats === 0) {
                        // Fallback to counting from seat layout
                        $seat_layout = json_decode($bus->seat_layout, true);
                        if (is_array($seat_layout) && isset($seat_layout['seats'])) {
                            $total_seats = count($seat_layout['seats']);
                        }
                    }
                }

                $seats_status_class = 'available';
                if ($available_seats === 0) {
                    $seats_status_class = 'full';
                } elseif ($available_seats <= 5) {
                    $seats_status_class = 'low';
                }

                // Paid extras for this product (same logic as ticket renderer)
                $plugin_settings = get_option('mt_ticket_bus_settings', array());
                $show_pay_extras = isset($plugin_settings['show_pay_extras']) ? $plugin_settings['show_pay_extras'] : 'yes';
                $product_extras_ids = get_post_meta($result['product_id'], '_mt_ticket_extras_ids', true);
                if (! is_array($product_extras_ids)) {
                    $product_extras_ids = array();
                }
                $product_extras_ids = array_map('absint', array_filter($product_extras_ids));
                $product_extras_list = array();
                if (! empty($product_extras_ids)) {
                    $extras_manager = MT_Ticket_Bus_Extras::get_instance();
                    foreach ($product_extras_ids as $eid) {
                        $extra = $extras_manager->get_extra($eid);
                        if ($extra && $extra->status === 'active') {
                            $product_extras_list[] = array(
                                'id' => (int) $extra->id,
                                'name' => $extra->name,
                                'price' => (float) $extra->price,
                            );
                        }
                    }
                }
                $base_price = isset($result['price']) ? (float) $result['price'] : 0;
                $original_price_html = '';
                if ($result['product_id'] && function_exists('wc_get_product')) {
                    $product_obj = wc_get_product($result['product_id']);
                    if ($product_obj) {
                        $original_price_html = $product_obj->get_price_html();
                    }
                }
                $route_stops_json = ! empty($route_stops) ? wp_json_encode($route_stops) : '';
                ?>
                <div class="mt-search-result-item" data-product-id="<?php echo esc_attr($result['product_id']); ?>" data-schedule-id="<?php echo esc_attr($result['schedule_id']); ?>" data-bus-id="<?php echo esc_attr($result['bus_id']); ?>" data-route-id="<?php echo esc_attr($result['route_id']); ?>" data-departure-date="<?php echo esc_attr($result['departure_date']); ?>" data-departure-time="<?php echo esc_attr($result['departure_time']); ?>" data-base-price="<?php echo esc_attr($base_price); ?>" data-original-price-html="<?php echo esc_attr($original_price_html); ?>" data-route-stops="<?php echo esc_attr($route_stops_json); ?>" data-segment-pricing="<?php echo $has_segment_pricing ? '1' : '0'; ?>">
                    <div class="mt-result-header">
                        <div class="mt-result-route">
                            <div class="mt-result-route-name"><?php echo esc_html($route_name ?: $result['product_name']); ?></div>
                            <div class="mt-result-route-stations">
                                <?php echo esc_html($route_stations_display); ?>
                            </div>
                        </div>
                        <div class="mt-result-time-price">
                            <div class="mt-result-time">
                                <?php echo esc_html($result['departure_time']); ?> → <?php echo esc_html($result['arrival_time']); ?>
                            </div>
                            <div class="mt-result-price">
                                <?php
                                if ($result['price']) {
                                    $product = wc_get_product($result['product_id']);
                                    if ($product) {
                                        echo wp_kses_post($product->get_price_html());
                                    } else {
                                        if (function_exists('wc_price')) {
                                            echo esc_html(wc_price($result['price']));
                                        } else {
                                            echo esc_html(number_format($result['price'], 2) . ' ' . get_woocommerce_currency_symbol());
                                        }
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="mt-result-details">
                        <div class="mt-result-detail-item">
                            <div class="mt-result-detail-label"><?php esc_html_e('Date', 'mt-ticket-bus'); ?></div>
                            <div class="mt-result-detail-value"><?php echo esc_html($departure_date_formatted); ?></div>
                        </div>
                        <?php if ($bus_name) : ?>
                            <div class="mt-result-detail-item">
                                <div class="mt-result-detail-label"><?php esc_html_e('Bus', 'mt-ticket-bus'); ?></div>
                                <div class="mt-result-detail-value">
                                    <?php echo esc_html($bus_name); ?>
                                    <?php if ($bus_registration) : ?>
                                        (<?php echo esc_html($bus_registration); ?>)
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($route && isset($route->distance) && (float) $route->distance > 0) : ?>
                            <div class="mt-result-detail-item">
                                <div class="mt-result-detail-label"><?php esc_html_e('Distance', 'mt-ticket-bus'); ?></div>
                                <div class="mt-result-detail-value"><?php echo esc_html(number_format((float) $route->distance, 2, '.', '') . ' ' . __('km', 'mt-ticket-bus')); ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if ($route && isset($route->duration) && (int) $route->duration > 0) : ?>
                            <div class="mt-result-detail-item">
                                <div class="mt-result-detail-label"><?php esc_html_e('Duration', 'mt-ticket-bus'); ?></div>
                                <div class="mt-result-detail-value"><?php echo esc_html((int) $route->duration . ' ' . __('minutes', 'mt-ticket-bus')); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (! empty($route_stops)) : ?>
                        <?php
                        $segment_disabled   = ! $has_segment_pricing;
                        $default_start_idx  = ! empty($valid_start_indices) ? $valid_start_indices[0] : 0;
                        $default_end_idx    = ! empty($valid_end_indices) ? $valid_end_indices[count($valid_end_indices) - 1] : $last_stop_idx;
                        ?>
                        <div class="mt-result-segment-wrapper mt-route-segment-wrapper">
                            <div class="mt-route-segment">
                                <label class="mt-segment-label" for=""><?php esc_html_e('Starting bus stop', 'mt-ticket-bus'); ?></label>
                                <select class="mt-segment-select mt-search-segment-start" <?php echo $segment_disabled ? ' disabled="disabled"' : ''; ?>>
                                    <?php foreach ($valid_start_indices as $i) : ?>
                                        <?php
                                        $stop     = $route_stops[$i];
                                        $selected = ($i === $default_start_idx);
                                        ?>
                                        <option value="<?php echo esc_attr($i); ?>" data-percent="<?php echo esc_attr($stop['percent']); ?>" <?php echo $selected ? ' selected="selected"' : ''; ?>>
                                            <?php echo esc_html($stop['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <label class="mt-segment-label" for=""><?php esc_html_e('Final bus stop', 'mt-ticket-bus'); ?></label>
                                <select class="mt-segment-select mt-search-segment-end" <?php echo $segment_disabled ? ' disabled="disabled"' : ''; ?>>
                                    <?php foreach ($valid_end_indices as $i) : ?>
                                        <?php
                                        $stop     = $route_stops[$i];
                                        $selected = ($i === $default_end_idx);
                                        ?>
                                        <option value="<?php echo esc_attr($i); ?>" data-percent="<?php echo esc_attr($stop['percent']); ?>" <?php echo $selected ? ' selected="selected"' : ''; ?>>
                                            <?php echo esc_html($stop['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($show_pay_extras === 'yes' && ! empty($product_extras_list)) : ?>
                        <div class="mt-result-paid-extras-wrapper mt-result-row">
                            <div class="mt-ticket-paid-extras">
                                <h3 class="mt-paid-extras-title"><?php esc_html_e('Paid extras (optional)', 'mt-ticket-bus'); ?></h3>
                                <p class="mt-paid-extras-description"><?php esc_html_e('Select one or more extras to add to your ticket. Price will be added per seat.', 'mt-ticket-bus'); ?></p>
                                <div class="mt-paid-extras-options">
                                    <?php foreach ($product_extras_list as $ex) : ?>
                                        <?php
                                        $price_formatted = number_format($ex['price'], 2, '.', '');
                                        $label = sprintf(
                                            /* translators: 1: Extra name, 2: Extra price */
                                            __('%1$s (+%2$s)', 'mt-ticket-bus'),
                                            $ex['name'],
                                            $price_formatted
                                        );
                                        ?>
                                        <label class="mt-paid-extras-option">
                                            <input type="checkbox" class="mt-ticket-extras-option mt-result-extras-option" name="mt_ticket_extras[]" value="<?php echo esc_attr($ex['id']); ?>" data-extra-id="<?php echo esc_attr($ex['id']); ?>" data-extra-price="<?php echo esc_attr($ex['price']); ?>" disabled="disabled">
                                            <span class="mt-paid-extras-option-label"><?php echo esc_html($label); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mt-result-seats-info">
                        <div class="mt-result-seats-count <?php echo esc_attr($seats_status_class); ?>">
                            <?php
                            if ($available_seats === 0) {
                                esc_html_e('No seats available', 'mt-ticket-bus');
                            } else {
                                echo esc_html(sprintf(
                                    /* translators: %d: number of available seats */
                                    _n('%d seat available', '%d seats available', $available_seats, 'mt-ticket-bus'),
                                    $available_seats
                                ));
                            }
                            ?>
                        </div>
                        <button type="button" class="mt-toggle-seatmap-button">
                            <?php esc_html_e('Show Seat Map', 'mt-ticket-bus'); ?>
                        </button>
                    </div>

                    <div class="mt-result-seatmap-container" style="display: none;">
                        <div class="mt-seatmap-loading"><?php esc_html_e('Loading seat map...', 'mt-ticket-bus'); ?></div>
                    </div>

                    <div class="mt-result-actions">
                        <div class="mt-result-buttons">
                            <button type="button" class="mt-result-button mt-result-button-add-cart mt-button-disabled">
                                <?php esc_html_e('Add to Cart', 'mt-ticket-bus'); ?>
                            </button>
                            <button type="button" class="mt-result-button mt-result-button-buy-now mt-button-disabled">
                                <?php esc_html_e('Buy Now', 'mt-ticket-bus'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Load WordPress footer
if (function_exists('get_footer')) {
    get_footer();
} else {
    wp_footer();
?>
    </body>

    </html>
<?php
}
