<?php

/**
 * WooCommerce integration class
 *
 * Handles integration with WooCommerce for bus ticket products
 *
 * @package MT_Ticket_Bus
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * WooCommerce Integration class
 */
class MT_Ticket_Bus_WooCommerce_Integration
{

    /**
     * Plugin instance
     *
     * @var MT_Ticket_Bus_WooCommerce_Integration
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return MT_Ticket_Bus_WooCommerce_Integration
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        // Add product meta fields
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_product_meta_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_meta_fields'));

        // Make product virtual by default
        add_action('woocommerce_product_options_general_product_data', array($this, 'set_virtual_default'));

        // Customize product page
        //add_action('wp', array($this, 'maybe_customize_single_product'), 99);
        add_action('woocommerce_before_single_product', array($this, 'maybe_customize_single_product'), 1);
        echo '<pre style="background:#111;color:#0f0;padding:10px;overflow:auto;">';
        echo "has images: ";
        var_export(has_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images'));
        echo "\n";
        echo "has title: ";
        var_export(has_action('woocommerce_single_product_summary', 'woocommerce_template_single_title'));
        echo "\n";
        echo "has atc: ";
        var_export(has_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart'));
        echo "\n";
        echo '</pre>';
        // AJAX handler for getting schedules by route
        add_action('wp_ajax_mt_get_schedules_by_route', array($this, 'ajax_get_schedules_by_route'));
        add_action('wp_ajax_nopriv_mt_get_schedules_by_route', array($this, 'ajax_get_schedules_by_route'));

        // Enqueue admin scripts for WooCommerce product edit page
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function maybe_customize_single_product()
    {
        if (!function_exists('is_product') || !is_product()) return;

        $product_id = get_queried_object_id();
        if (!$product_id) return;

        $product = wc_get_product($product_id);
        if (!$product) return;

        $is_ticket_product = get_post_meta($product_id, '_mt_is_ticket_product', true);
        if ($is_ticket_product !== 'yes') return;

        echo '<pre style="background:#111;color:#0f0;padding:10px;overflow:auto;">';
        echo "has images: ";
        var_export(has_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images'));
        echo "\n";
        echo "has title: ";
        var_export(has_action('woocommerce_single_product_summary', 'woocommerce_template_single_title'));
        echo "\n";
        echo "has atc: ";
        var_export(has_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart'));
        echo "\n";
        echo '</pre>';

        remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_title', 5);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
    }

    /**
     * Add product meta fields for bus tickets
     */
    public function add_product_meta_fields()
    {
        global $post;

        $route_id = get_post_meta($post->ID, '_mt_bus_route_id', true);
        $bus_id = get_post_meta($post->ID, '_mt_bus_id', true);
        $schedule_id = get_post_meta($post->ID, '_mt_bus_schedule_id', true);

        echo '<div class="options_group mt-bus-ticket-options">';

        // Checkbox to mark product as bus ticket
        $is_ticket_product = get_post_meta($post->ID, '_mt_is_ticket_product', true);
        woocommerce_wp_checkbox(array(
            'id'          => '_mt_is_ticket_product',
            'label'       => __('Is Bus Ticket Product', 'mt-ticket-bus'),
            'description' => __('Check this box if this is a bus ticket product.', 'mt-ticket-bus'),
            'value'       => $is_ticket_product ? 'yes' : 'no',
        ));

        // Route selection
        woocommerce_wp_select(array(
            'id'          => '_mt_bus_route_id',
            'label'       => __('Bus Route', 'mt-ticket-bus'),
            'description' => __('Select the bus route for this ticket product.', 'mt-ticket-bus'),
            'options'     => $this->get_routes_options(),
            'value'       => $route_id,
            'custom_attributes' => array(
                'disabled' => $is_ticket_product ? '' : 'disabled',
            ),
        ));

        // Bus selection
        woocommerce_wp_select(array(
            'id'          => '_mt_bus_id',
            'label'       => __('Bus', 'mt-ticket-bus'),
            'description' => __('Select the bus for this ticket product.', 'mt-ticket-bus'),
            'options'     => $this->get_buses_options(),
            'value'       => $bus_id,
            'custom_attributes' => array(
                'disabled' => $is_ticket_product ? '' : 'disabled',
            ),
        ));

        // Schedule selection
        woocommerce_wp_select(array(
            'id'          => '_mt_bus_schedule_id',
            'label'       => __('Schedule', 'mt-ticket-bus'),
            'description' => __('Select the schedule for this ticket product. Schedules will be filtered based on the selected route.', 'mt-ticket-bus'),
            'options'     => $this->get_schedules_options($route_id),
            'value'       => $schedule_id,
            'custom_attributes' => array(
                'disabled' => $is_ticket_product ? '' : 'disabled',
            ),
        ));

        echo '</div>';

        // Add JavaScript for dynamic schedule loading
?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                var ticketCheckbox = $('#_mt_is_ticket_product');
                var routeSelect = $('#_mt_bus_route_id');
                var busSelect = $('#_mt_bus_id');
                var scheduleSelect = $('#_mt_bus_schedule_id');
                var savedScheduleId = scheduleSelect.val(); // Save the current schedule ID

                // Function to toggle fields based on checkbox state
                function toggleTicketFields() {
                    var isChecked = ticketCheckbox.is(':checked');
                    routeSelect.prop('disabled', !isChecked);
                    busSelect.prop('disabled', !isChecked);
                    scheduleSelect.prop('disabled', !isChecked);

                    // Clear values if checkbox is unchecked
                    if (!isChecked) {
                        routeSelect.val('').trigger('change');
                        busSelect.val('');
                        scheduleSelect.val('');
                    }
                }

                // Toggle fields when checkbox changes
                ticketCheckbox.on('change', function() {
                    toggleTicketFields();
                });

                // Initialize on page load
                toggleTicketFields();

                // Function to load schedules by route
                function loadSchedulesByRoute(routeId, preserveScheduleId) {
                    if (!routeId || routeId === '') {
                        scheduleSelect.html('<option value=""><?php echo esc_js(__('Select a schedule...', 'mt-ticket-bus')); ?></option>');
                        return;
                    }

                    scheduleSelect.prop('disabled', true);

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'mt_get_schedules_by_route',
                            route_id: routeId,
                            nonce: '<?php echo wp_create_nonce('mt_ticket_bus_admin'); ?>'
                        },
                        success: function(response) {
                            var isTicketChecked = ticketCheckbox.is(':checked');
                            scheduleSelect.prop('disabled', !isTicketChecked);

                            if (response.success && response.data && response.data.schedules) {
                                var options = '<option value=""><?php echo esc_js(__('Select a schedule...', 'mt-ticket-bus')); ?></option>';

                                $.each(response.data.schedules, function(id, label) {
                                    options += '<option value="' + id + '">' + label + '</option>';
                                });

                                scheduleSelect.html(options);

                                // Restore saved schedule ID if it exists in the new options
                                if (preserveScheduleId && savedScheduleId) {
                                    if (scheduleSelect.find('option[value="' + savedScheduleId + '"]').length > 0) {
                                        scheduleSelect.val(savedScheduleId);
                                    }
                                }
                            } else {
                                scheduleSelect.html('<option value=""><?php echo esc_js(__('No schedules found for this route.', 'mt-ticket-bus')); ?></option>');
                            }
                        },
                        error: function() {
                            var isTicketChecked = ticketCheckbox.is(':checked');
                            scheduleSelect.prop('disabled', !isTicketChecked);
                            scheduleSelect.html('<option value=""><?php echo esc_js(__('Error loading schedules.', 'mt-ticket-bus')); ?></option>');
                        }
                    });
                }

                // Load schedules when route changes
                routeSelect.on('change', function() {
                    var routeId = $(this).val();
                    savedScheduleId = ''; // Clear saved schedule when route changes
                    loadSchedulesByRoute(routeId, false);
                });

                // Load schedules on page load if route is already selected
                if (routeSelect.val() && ticketCheckbox.is(':checked')) {
                    loadSchedulesByRoute(routeSelect.val(), true);
                }
            });
        </script>
        <?php
    }

    /**
     * Save product meta fields
     *
     * @param int $post_id Post ID
     */
    public function save_product_meta_fields($post_id)
    {
        // Save ticket product flag
        $is_ticket_product = isset($_POST['_mt_is_ticket_product']) ? 'yes' : 'no';
        update_post_meta($post_id, '_mt_is_ticket_product', $is_ticket_product);

        // Only save ticket-related fields if product is marked as ticket
        if ($is_ticket_product === 'yes') {
            if (isset($_POST['_mt_bus_route_id'])) {
                update_post_meta($post_id, '_mt_bus_route_id', sanitize_text_field($_POST['_mt_bus_route_id']));
            }

            if (isset($_POST['_mt_bus_id'])) {
                update_post_meta($post_id, '_mt_bus_id', sanitize_text_field($_POST['_mt_bus_id']));
            }

            if (isset($_POST['_mt_bus_schedule_id'])) {
                update_post_meta($post_id, '_mt_bus_schedule_id', sanitize_text_field($_POST['_mt_bus_schedule_id']));
            }
        } else {
            // Clear ticket-related fields if product is not a ticket
            delete_post_meta($post_id, '_mt_bus_route_id');
            delete_post_meta($post_id, '_mt_bus_id');
            delete_post_meta($post_id, '_mt_bus_schedule_id');
        }
    }

    /**
     * Set virtual product by default
     */
    public function set_virtual_default()
    {
        global $post;

        if (! $post->ID) {
            return;
        }

        $is_virtual = get_post_meta($post->ID, '_virtual', true);

        if (empty($is_virtual)) {
        ?>
            <script>
                jQuery(document).ready(function($) {
                    $('#_virtual').prop('checked', true).trigger('change');
                });
            </script>
<?php
        }
    }

    /**
     * Get routes options for select field
     *
     * @return array
     */
    private function get_routes_options()
    {
        $routes = MT_Ticket_Bus_Routes::get_instance()->get_all_routes();
        $options = array('' => __('Select a route...', 'mt-ticket-bus'));

        foreach ($routes as $route) {
            $options[$route->id] = $route->name . ' (' . $route->start_station . ' - ' . $route->end_station . ')';
        }

        return $options;
    }

    /**
     * Get buses options for select field
     *
     * @return array
     */
    private function get_buses_options()
    {
        $buses = MT_Ticket_Bus_Buses::get_instance()->get_all_buses();
        $options = array('' => __('Select a bus...', 'mt-ticket-bus'));

        foreach ($buses as $bus) {
            $options[$bus->id] = $bus->name . ' (' . $bus->registration_number . ')';
        }

        return $options;
    }

    /**
     * Get schedules options for select field
     *
     * @param int $route_id Optional route ID to filter schedules
     * @return array
     */
    private function get_schedules_options($route_id = 0)
    {
        $args = array('status' => 'all');
        if (!empty($route_id)) {
            $args['route_id'] = absint($route_id);
        }

        $schedules = MT_Ticket_Bus_Schedules::get_instance()->get_all_schedules($args);
        $options = array('' => __('Select a schedule...', 'mt-ticket-bus'));

        foreach ($schedules as $schedule) {
            $label_parts = array();

            // Add name if available
            if (!empty($schedule->name)) {
                $label_parts[] = $schedule->name;
            }

            // Parse and add courses
            $courses_display = '';
            if (!empty($schedule->courses)) {
                $courses = json_decode($schedule->courses, true);
                if (is_array($courses) && !empty($courses)) {
                    $course_times = array();
                    foreach ($courses as $course) {
                        if (isset($course['departure_time']) && isset($course['arrival_time'])) {
                            $course_times[] = $course['departure_time'] . ' - ' . $course['arrival_time'];
                        }
                    }
                    if (!empty($course_times)) {
                        $courses_display = implode(', ', $course_times);
                    }
                }
            }

            if ($courses_display) {
                $label_parts[] = $courses_display;
            }

            // Add days of week
            $days_info = '';
            if (!empty($schedule->days_of_week)) {
                $parsed_days = MT_Ticket_Bus_Schedules::get_instance()->parse_days_of_week($schedule->days_of_week);
                if ($parsed_days === 'all') {
                    $days_info = ' (' . __('Every day', 'mt-ticket-bus') . ')';
                } elseif ($parsed_days === 'weekdays') {
                    $days_info = ' (' . __('Weekdays', 'mt-ticket-bus') . ')';
                } elseif ($parsed_days === 'weekend') {
                    $days_info = ' (' . __('Weekend', 'mt-ticket-bus') . ')';
                } elseif (is_array($parsed_days)) {
                    $days_info = ' (' . implode(', ', array_map('ucfirst', $parsed_days)) . ')';
                }
            }

            $label = implode(' - ', $label_parts) . $days_info;
            $options[$schedule->id] = $label;
        }

        return $options;
    }

    /**
     * AJAX handler for getting schedules by route
     */
    public function ajax_get_schedules_by_route()
    {
        check_ajax_referer('mt_ticket_bus_admin', 'nonce');

        $route_id = isset($_POST['route_id']) ? absint($_POST['route_id']) : 0;

        if (empty($route_id)) {
            wp_send_json_error(array('message' => __('Route ID is required.', 'mt-ticket-bus')));
        }

        $schedules = MT_Ticket_Bus_Schedules::get_instance()->get_schedules_by_route($route_id, array('status' => 'all'));
        $options = array();

        foreach ($schedules as $schedule) {
            $label_parts = array();

            // Add name if available
            if (!empty($schedule->name)) {
                $label_parts[] = $schedule->name;
            }

            // Parse and add courses
            $courses_display = '';
            if (!empty($schedule->courses)) {
                $courses = json_decode($schedule->courses, true);
                if (is_array($courses) && !empty($courses)) {
                    $course_times = array();
                    foreach ($courses as $course) {
                        if (isset($course['departure_time']) && isset($course['arrival_time'])) {
                            $course_times[] = $course['departure_time'] . ' - ' . $course['arrival_time'];
                        }
                    }
                    if (!empty($course_times)) {
                        $courses_display = implode(', ', $course_times);
                    }
                }
            }

            if ($courses_display) {
                $label_parts[] = $courses_display;
            }

            // Add days of week
            $days_info = '';
            if (!empty($schedule->days_of_week)) {
                $parsed_days = MT_Ticket_Bus_Schedules::get_instance()->parse_days_of_week($schedule->days_of_week);
                if ($parsed_days === 'all') {
                    $days_info = ' (' . __('Every day', 'mt-ticket-bus') . ')';
                } elseif ($parsed_days === 'weekdays') {
                    $days_info = ' (' . __('Weekdays', 'mt-ticket-bus') . ')';
                } elseif ($parsed_days === 'weekend') {
                    $days_info = ' (' . __('Weekend', 'mt-ticket-bus') . ')';
                } elseif (is_array($parsed_days)) {
                    $days_info = ' (' . implode(', ', array_map('ucfirst', $parsed_days)) . ')';
                }
            }

            $label = implode(' - ', $label_parts) . $days_info;
            $options[$schedule->id] = $label;
        }

        wp_send_json_success(array('schedules' => $options));
    }

    /**
     * Enqueue admin scripts for WooCommerce product edit page
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_scripts($hook)
    {
        // Only load on product edit page
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }

        global $post;
        if (!$post || $post->post_type !== 'product') {
            return;
        }

        // Make sure ajaxurl is available
        wp_add_inline_script('jquery', 'var ajaxurl = "' . admin_url('admin-ajax.php') . '";', 'before');
    }

    private function mt_debug($label, $value = null)
    {
        if (!current_user_can('manage_options')) return;

        echo '<pre style="background:#111;color:#0f0;padding:10px;margin:10px 0;overflow:auto;">';
        echo esc_html($label);
        if ($value !== null) {
            echo "\n";
            echo esc_html(print_r($value, true));
        }
        echo '</pre>';
    }
}
