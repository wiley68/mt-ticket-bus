<?php

/**
 * Search Results Template
 *
 * @package MT_Ticket_Bus
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
    wp_die(__('Invalid search parameters.', 'mt-ticket-bus'));
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
                __('From: %s to %s', 'mt-ticket-bus'),
                $from,
                $to
            ));
            ?>
            <?php if ($date_from_formatted && $date_to_formatted) : ?>
                <br>
                <?php
                if ($date_from_formatted === $date_to_formatted) {
                    echo esc_html(sprintf(__('Date: %s', 'mt-ticket-bus'), $date_from_formatted));
                } else {
                    echo esc_html(sprintf(
                        __('Date range: %s - %s', 'mt-ticket-bus'),
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
                ?>
                <div class="mt-search-result-item" data-product-id="<?php echo esc_attr($result['product_id']); ?>" data-schedule-id="<?php echo esc_attr($result['schedule_id']); ?>" data-bus-id="<?php echo esc_attr($result['bus_id']); ?>" data-route-id="<?php echo esc_attr($result['route_id']); ?>" data-departure-date="<?php echo esc_attr($result['departure_date']); ?>" data-departure-time="<?php echo esc_attr($result['departure_time']); ?>">
                    <div class="mt-result-header">
                        <div class="mt-result-route">
                            <div class="mt-result-route-name"><?php echo esc_html($route_name ?: $result['product_name']); ?></div>
                            <div class="mt-result-route-stations">
                                <?php echo esc_html($from); ?> → <?php echo esc_html($to); ?>
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
                    </div>

                    <div class="mt-result-seats-info">
                        <div class="mt-result-seats-count <?php echo esc_attr($seats_status_class); ?>">
                            <?php
                            if ($available_seats === 0) {
                                esc_html_e('No seats available', 'mt-ticket-bus');
                            } else {
                                echo esc_html(sprintf(
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
