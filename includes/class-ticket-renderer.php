<?php

/**
 * Ticket product renderer class
 *
 * Shared rendering logic for ticket products (used by both block themes and standard themes)
 *
 * @package MT_Ticket_Bus
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Ticket Renderer class
 */
class MT_Ticket_Bus_Renderer
{
    /**
     * Get product ID from various contexts
     *
     * @param mixed $block Block object (for block themes) or null
     * @return int|null Product ID or null if not found
     */
    public static function get_product_id($block = null)
    {
        // Try block context first (for block themes)
        if ($block && isset($block->context['postId'])) {
            return (int) $block->context['postId'];
        }

        // Try queried object
        if (function_exists('get_queried_object_id')) {
            $product_id = get_queried_object_id();
            if ($product_id) {
                return (int) $product_id;
            }
        }

        // Try global post
        if (is_singular('product')) {
            global $post;
            if ($post && isset($post->ID)) {
                return (int) $post->ID;
            }
        }

        return null;
    }

    /**
     * Check if product is a ticket product
     *
     * @param int $product_id Product ID
     * @return bool True if product is a ticket product
     */
    public static function is_ticket_product($product_id)
    {
        if (! $product_id) {
            return false;
        }

        return get_post_meta($product_id, '_mt_is_ticket_product', true) === 'yes';
    }

    /**
     * Get product ticket data (schedule, bus, route)
     *
     * @param int $product_id Product ID
     * @return array|null Product ticket data or null if not found
     */
    public static function get_product_ticket_data($product_id)
    {
        if (! $product_id) {
            return null;
        }

        $schedule_id = get_post_meta($product_id, '_mt_bus_schedule_id', true);
        $bus_id = get_post_meta($product_id, '_mt_bus_id', true);
        $route_id = get_post_meta($product_id, '_mt_bus_route_id', true);

        if (! $schedule_id || ! $bus_id || ! $route_id) {
            return null;
        }

        $schedule = MT_Ticket_Bus_Schedules::get_instance()->get_schedule($schedule_id);
        $bus = MT_Ticket_Bus_Buses::get_instance()->get_bus($bus_id);
        $route = MT_Ticket_Bus_Routes::get_instance()->get_route($route_id);

        if (! $schedule || ! $bus || ! $route) {
            return null;
        }

        return array(
            'product_id' => $product_id,
            'schedule' => $schedule,
            'bus' => $bus,
            'route' => $route,
        );
    }

    /**
     * Get available dates for a schedule (based on days_of_week)
     *
     * @param object $schedule Schedule object
     * @param int $month Month number (1-12)
     * @param int $year Year (e.g., 2026)
     * @return array Array of available dates with availability info
     */
    public static function get_available_dates($schedule, $month = null, $year = null)
    {
        if (! $schedule || empty($schedule->days_of_week)) {
            return array();
        }

        // Parse days_of_week
        $days_of_week = $schedule->days_of_week;
        if (is_string($days_of_week)) {
            $decoded = json_decode($days_of_week, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $days_of_week = $decoded;
            } elseif ($days_of_week === 'all') {
                $days_of_week = array(0, 1, 2, 3, 4, 5, 6); // All days
            } else {
                $days_of_week = array_map('intval', explode(',', $days_of_week));
            }
        }

        if (! is_array($days_of_week) || empty($days_of_week)) {
            return array();
        }

        // Use current month/year if not provided
        if (! $month) {
            $month = (int) date('n');
        }
        if (! $year) {
            $year = (int) date('Y');
        }

        $available_dates = array();
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $today = current_time('Y-m-d');

        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $day_of_week = (int) date('w', strtotime($date)); // 0 = Sunday, 6 = Saturday

            // Check if date is in the past
            if ($date < $today) {
                continue;
            }

            // Check if day of week matches schedule
            if (! in_array($day_of_week, $days_of_week, true)) {
                continue;
            }

            // Check availability (will be checked via AJAX for each date)
            $available_dates[] = array(
                'date' => $date,
                'day' => $day,
                'day_of_week' => $day_of_week,
                'available' => true, // Will be updated via AJAX
            );
        }

        return $available_dates;
    }

    /**
     * Get courses for a schedule
     *
     * @param object $schedule Schedule object
     * @return array Array of courses
     */
    public static function get_schedule_courses($schedule)
    {
        if (! $schedule || empty($schedule->courses)) {
            return array();
        }

        $courses = json_decode($schedule->courses, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($courses)) {
            return array();
        }

        return $courses;
    }

    /**
     * Check date availability (count available seats)
     *
     * @param int $schedule_id Schedule ID
     * @param int $bus_id Bus ID
     * @param string $date Date (Y-m-d)
     * @return array Availability info (available: bool, available_seats: int, total_seats: int)
     */
    public static function check_date_availability($schedule_id, $bus_id, $date)
    {
        $schedule = MT_Ticket_Bus_Schedules::get_instance()->get_schedule($schedule_id);
        if (! $schedule) {
            return array('available' => false, 'available_seats' => 0, 'total_seats' => 0);
        }

        $courses = self::get_schedule_courses($schedule);
        if (empty($courses)) {
            return array('available' => false, 'available_seats' => 0, 'total_seats' => 0);
        }

        $bus = MT_Ticket_Bus_Buses::get_instance()->get_bus($bus_id);
        if (! $bus) {
            return array('available' => false, 'available_seats' => 0, 'total_seats' => 0);
        }

        $total_seats = (int) $bus->total_seats;
        $min_available_seats = $total_seats;

        // Check availability for each course on this date
        foreach ($courses as $course) {
            $departure_time = $course['departure_time'];
            $available_seats = MT_Ticket_Bus_Reservations::get_instance()->get_available_seats(
                $schedule_id,
                $date,
                $departure_time,
                $bus_id
            );
            $available_count = count($available_seats);
            $min_available_seats = min($min_available_seats, $available_count);
        }

        return array(
            'available' => $min_available_seats > 0,
            'available_seats' => $min_available_seats,
            'total_seats' => $total_seats,
        );
    }

    /**
     * Render seatmap section (replaces gallery/images)
     *
     * @param mixed $block Block object (for block themes) or null
     * @return string HTML output
     */
    public static function render_seatmap($block = null)
    {
        $product_id = self::get_product_id($block);

        if (! $product_id) {
            return ''; // No product context
        }

        if (! self::is_ticket_product($product_id)) {
            return ''; // Not a ticket product
        }

        // Get product ticket data
        $ticket_data = self::get_product_ticket_data($product_id);
        if (! $ticket_data) {
            return '<div class="mt-ticket-block mt-ticket-seatmap-block"><div class="mt-ticket-block__inner"><p>' . esc_html__('Ticket data not configured. Please set Bus Route, Bus, and Schedule for this product.', 'mt-ticket-bus') . '</p></div></div>';
        }

        $schedule = $ticket_data['schedule'];
        $bus = $ticket_data['bus'];
        $route = $ticket_data['route'];

        // Get current month/year for calendar
        $current_month = (int) date('n');
        $current_year = (int) date('Y');

        // Get available dates for current month
        $available_dates = self::get_available_dates($schedule, $current_month, $current_year);

        // Get courses
        $courses = self::get_schedule_courses($schedule);

        // Parse bus seat layout
        $seat_layout = array();
        if (! empty($bus->seat_layout)) {
            $layout_data = json_decode($bus->seat_layout, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($layout_data['seats'])) {
                $seat_layout = $layout_data;
            }
        }

        // Build HTML output
        $output = '<div class="mt-ticket-block mt-ticket-seatmap-block" data-product-id="' . esc_attr($product_id) . '" data-schedule-id="' . esc_attr($schedule->id) . '" data-bus-id="' . esc_attr($bus->id) . '" data-route-id="' . esc_attr($route->id) . '">';
        $output .= '<div class="mt-ticket-block__inner">';

        // 1. Date picker
        $output .= '<div class="mt-seatmap-date-picker">';
        $output .= '<h3>' . esc_html__('Select date', 'mt-ticket-bus') . '</h3>';
        $output .= '<div class="mt-calendar-container" data-month="' . esc_attr($current_month) . '" data-year="' . esc_attr($current_year) . '">';
        $output .= '<div class="mt-calendar-header">';
        $output .= '<button type="button" class="mt-calendar-prev" aria-label="' . esc_attr__('Previous month', 'mt-ticket-bus') . '">‹</button>';
        $output .= '<div class="mt-calendar-month-year">' . esc_html(date_i18n('F Y', strtotime("$current_year-$current_month-01"))) . '</div>';
        $output .= '<button type="button" class="mt-calendar-next" aria-label="' . esc_attr__('Next month', 'mt-ticket-bus') . '">›</button>';
        $output .= '</div>';
        $output .= '<div class="mt-calendar-grid">';
        // Weekday headers
        $weekdays = array(
            __('Mon', 'mt-ticket-bus'),
            __('Tue', 'mt-ticket-bus'),
            __('Wed', 'mt-ticket-bus'),
            __('Thu', 'mt-ticket-bus'),
            __('Fri', 'mt-ticket-bus'),
            __('Sat', 'mt-ticket-bus'),
            __('Sun', 'mt-ticket-bus')
        );
        foreach ($weekdays as $weekday) {
            $output .= '<div class="mt-calendar-weekday">' . esc_html($weekday) . '</div>';
        }
        // Calendar days will be populated via JavaScript
        $output .= '</div>';
        $output .= '</div>';
        $output .= '<div class="mt-date-selected" style="display:none;">';
        $output .= '<span class="mt-selected-date-label">' . esc_html__('Selected date:', 'mt-ticket-bus') . '</span> ';
        $output .= '<span class="mt-selected-date-value"></span>';
        $output .= '</div>';
        $output .= '</div>';

        // 2. Time picker (hidden until date is selected)
        $output .= '<div class="mt-seatmap-time-picker" style="display:none;">';
        $output .= '<h3>' . esc_html__('Select time', 'mt-ticket-bus') . '</h3>';
        $output .= '<div class="mt-time-options">';
        foreach ($courses as $index => $course) {
            $departure_time = $course['departure_time'];
            $arrival_time = $course['arrival_time'];
            $output .= '<button type="button" class="mt-time-option" data-departure-time="' . esc_attr($departure_time) . '" data-arrival-time="' . esc_attr($arrival_time) . '">';
            $output .= '<span class="mt-time-departure">' . esc_html(date_i18n('H:i', strtotime($departure_time))) . '</span>';
            $output .= '<span class="mt-time-separator"> → </span>';
            $output .= '<span class="mt-time-arrival">' . esc_html(date_i18n('H:i', strtotime($arrival_time))) . '</span>';
            $output .= '</button>';
        }
        $output .= '</div>';
        $output .= '<div class="mt-time-selected" style="display:none;">';
        $output .= '<span class="mt-selected-time-label">' . esc_html__('Selected time:', 'mt-ticket-bus') . '</span> ';
        $output .= '<span class="mt-selected-time-value"></span>';
        $output .= '</div>';
        $output .= '</div>';

        // 3. Seat map (hidden until date and time are selected)
        $output .= '<div class="mt-seatmap-container" style="display:none;">';
        $output .= '<h3>' . esc_html__('Select seat', 'mt-ticket-bus') . '</h3>';
        $output .= '<div class="mt-bus-seat-layout" data-seat-layout="' . esc_attr(wp_json_encode($seat_layout)) . '">';
        $output .= '<div class="mt-seat-layout-loading">' . esc_html__('Loading layout...', 'mt-ticket-bus') . '</div>';
        $output .= '</div>';
        $output .= '</div>';

        $output .= '</div>'; // mt-ticket-block__inner
        $output .= '</div>'; // mt-ticket-block

        return $output;
    }

    /**
     * Render ticket summary section (replaces right summary/info/button)
     *
     * @param mixed $block Block object (for block themes) or null
     * @return string HTML output
     */
    public static function render_ticket_summary($block = null)
    {
        $product_id = self::get_product_id($block);

        if (! $product_id) {
            return ''; // No product context
        }

        if (! self::is_ticket_product($product_id)) {
            return ''; // Not a ticket product
        }

        // Get WooCommerce product
        $product = wc_get_product($product_id);
        if (! $product) {
            return '';
        }

        // Get product data
        $product_name = $product->get_name();
        $product_price = $product->get_price_html();
        $product_short_description = $product->get_short_description();
        $product_sku = $product->get_sku();

        // Get categories
        $categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'all'));
        $category_links = array();
        foreach ($categories as $category) {
            $category_links[] = '<a href="' . esc_url(get_term_link($category)) . '" class="mt-product-category-link">' . esc_html($category->name) . '</a>';
        }

        // Get tags
        $tags = wp_get_post_terms($product_id, 'product_tag', array('fields' => 'all'));
        $tag_links = array();
        foreach ($tags as $tag) {
            $tag_links[] = '<a href="' . esc_url(get_term_link($tag)) . '" class="mt-product-tag-link">' . esc_html($tag->name) . '</a>';
        }

        // Get product rating
        $rating_count = $product->get_rating_count();
        $average_rating = $product->get_average_rating();
        $reviews_url = get_permalink($product_id) . '#reviews';

        // Build HTML output
        $output = '<div class="mt-ticket-block mt-ticket-summary-block" data-product-id="' . esc_attr($product_id) . '">';
        $output .= '<div class="mt-ticket-block__inner">';

        // Row 1: Category | Rating & Reviews
        $output .= '<div class="mt-summary-row mt-summary-row-1">';
        $output .= '<div class="mt-summary-categories">';
        if (! empty($category_links)) {
            $output .= implode(', ', $category_links);
        }
        $output .= '</div>';
        $output .= '<div class="mt-summary-rating">';
        if ($rating_count > 0) {
            $output .= '<div class="mt-rating-stars">';
            for ($i = 1; $i <= 5; $i++) {
                $star_class = $i <= $average_rating ? 'mt-star-filled' : 'mt-star-empty';
                $output .= '<span class="mt-star ' . $star_class . '">★</span>';
            }
            $output .= '</div>';
            $output .= '<a href="' . esc_url($reviews_url) . '" class="mt-reviews-link">';
            $output .= sprintf(
                /* translators: %d: number of reviews */
                esc_html__('Reviews (%d)', 'mt-ticket-bus'),
                $rating_count
            );
            $output .= '</a>';
        } else {
            $output .= '<span class="mt-no-reviews">' . esc_html__('No reviews yet', 'mt-ticket-bus') . '</span>';
        }
        $output .= '</div>';
        $output .= '</div>'; // mt-summary-row-1

        // Row 2: Product Name
        $output .= '<div class="mt-summary-row mt-summary-row-2">';
        $output .= '<h1 class="mt-product-title">' . esc_html($product_name) . '</h1>';
        $output .= '</div>';

        // Row 3: Price
        $output .= '<div class="mt-summary-row mt-summary-row-3">';
        $output .= '<div class="mt-product-price">' . $product_price . '</div>';
        $output .= '</div>';

        // Row 4: Short Description
        if (! empty($product_short_description)) {
            $output .= '<div class="mt-summary-row mt-summary-row-4">';
            $output .= '<div class="mt-product-short-description">' . wp_kses_post($product_short_description) . '</div>';
            $output .= '</div>';
        }

        // Selected seats summary (hidden initially)
        $output .= '<div class="mt-selected-seats-summary" style="display:none;">';
        $output .= '<h3 class="mt-selected-seats-title">' . esc_html__('Selected seats:', 'mt-ticket-bus') . '</h3>';
        $output .= '<ul class="mt-selected-seats-list"></ul>';
        $output .= '</div>';

        // Row 5: Add to Cart & Buy Now buttons
        $output .= '<div class="mt-summary-row mt-summary-row-5">';
        $output .= '<div class="mt-product-actions">';

        // Add to Cart button
        $output .= '<button type="button" class="mt-btn mt-btn-add-to-cart button alt" data-product-id="' . esc_attr($product_id) . '">';
        $output .= esc_html__('Add to cart', 'mt-ticket-bus');
        $output .= '</button>';

        // Buy Now button
        $output .= '<button type="button" class="mt-btn mt-btn-buy-now button" data-product-id="' . esc_attr($product_id) . '">';
        $output .= esc_html__('Buy now', 'mt-ticket-bus');
        $output .= '</button>';

        $output .= '</div>';
        $output .= '</div>';

        // Row 6: SKU, Category, Tags
        $output .= '<div class="mt-summary-row mt-summary-row-6">';
        $output .= '<div class="mt-product-meta">';

        if ($product_sku) {
            $output .= '<div class="mt-meta-item">';
            $output .= '<span class="mt-meta-label">' . esc_html__('SKU:', 'mt-ticket-bus') . '</span> ';
            $output .= '<span class="mt-meta-value">' . esc_html($product_sku) . '</span>';
            $output .= '</div>';
        }

        if (! empty($categories)) {
            $output .= '<div class="mt-meta-item">';
            $output .= '<span class="mt-meta-label">' . esc_html__('Category:', 'mt-ticket-bus') . '</span> ';
            $output .= '<span class="mt-meta-value">' . implode(', ', $category_links) . '</span>';
            $output .= '</div>';
        }

        if (! empty($tags)) {
            $output .= '<div class="mt-meta-item">';
            $output .= '<span class="mt-meta-label">' . esc_html__('Tags:', 'mt-ticket-bus') . '</span> ';
            $output .= '<span class="mt-meta-value">' . implode(', ', $tag_links) . '</span>';
            $output .= '</div>';
        }

        $output .= '</div>';
        $output .= '</div>';

        $output .= '</div>'; // mt-ticket-block__inner
        $output .= '</div>'; // mt-ticket-block

        return $output;
    }
}
