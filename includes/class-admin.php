<?php

/**
 * Admin interface class
 *
 * Handles all admin menu pages and settings
 *
 * @package MT_Ticket_Bus
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Admin class
 */
class MT_Ticket_Bus_Admin
{

    /**
     * Plugin instance
     *
     * @var MT_Ticket_Bus_Admin
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return MT_Ticket_Bus_Admin
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        $menu_slug = 'mt-ticket-bus';

        // Main menu
        add_menu_page(
            __('MT Ticket Bus', 'mt-ticket-bus'),
            __('MT Ticket Bus', 'mt-ticket-bus'),
            'manage_options',
            $menu_slug,
            array($this, 'render_overview_page'),
            'dashicons-tickets-alt',
            56
        );

        // Overview submenu (duplicate of main menu)
        add_submenu_page(
            $menu_slug,
            __('Overview', 'mt-ticket-bus'),
            __('Overview', 'mt-ticket-bus'),
            'manage_options',
            $menu_slug,
            array($this, 'render_overview_page')
        );

        // Settings submenu
        add_submenu_page(
            $menu_slug,
            __('Settings', 'mt-ticket-bus'),
            __('Settings', 'mt-ticket-bus'),
            'manage_options',
            $menu_slug . '-settings',
            array($this, 'render_settings_page')
        );

        // Buses submenu
        add_submenu_page(
            $menu_slug,
            __('Buses', 'mt-ticket-bus'),
            __('Buses', 'mt-ticket-bus'),
            'manage_options',
            $menu_slug . '-buses',
            array($this, 'render_buses_page')
        );

        // Routes submenu
        add_submenu_page(
            $menu_slug,
            __('Routes', 'mt-ticket-bus'),
            __('Routes', 'mt-ticket-bus'),
            'manage_options',
            $menu_slug . '-routes',
            array($this, 'render_routes_page')
        );

        // Schedules submenu
        add_submenu_page(
            $menu_slug,
            __('Schedules', 'mt-ticket-bus'),
            __('Schedules', 'mt-ticket-bus'),
            'manage_options',
            $menu_slug . '-schedules',
            array($this, 'render_schedules_page')
        );

        // Reservations submenu
        add_submenu_page(
            $menu_slug,
            __('Reservations', 'mt-ticket-bus'),
            __('Reservations', 'mt-ticket-bus'),
            'manage_options',
            $menu_slug . '-reservations',
            array($this, 'render_reservations_page')
        );
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_scripts($hook)
    {
        // Only load on our plugin pages
        if (strpos($hook, 'mt-ticket-bus') === false) {
            return;
        }

        wp_enqueue_style(
            'mt-ticket-bus-admin',
            MT_TICKET_BUS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MT_TICKET_BUS_VERSION
        );

        // Enqueue SweetAlert2 CSS
        wp_enqueue_style(
            'sweetalert2',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
            array(),
            '11.0.0'
        );

        // Enqueue SweetAlert2 JS
        wp_enqueue_script(
            'sweetalert2',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js',
            array(),
            '11.0.0',
            true
        );

        wp_enqueue_script(
            'mt-ticket-bus-admin',
            MT_TICKET_BUS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'sweetalert2'),
            MT_TICKET_BUS_VERSION,
            true
        );

        wp_localize_script(
            'mt-ticket-bus-admin',
            'mtTicketBusAdmin',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('mt_ticket_bus_admin'),
                'i18n'    => array(
                    'registrationNumberRequired' => __('Registration number is required.', 'mt-ticket-bus'),
                    'fixRegistrationError' => __('Please fix the registration number error before submitting.', 'mt-ticket-bus'),
                    'configureSeatColumns' => __('Please configure at least one column with seats. Both columns cannot be 0.', 'mt-ticket-bus'),
                    'configureSeatColumnsFirst' => __('Please configure seat columns first.', 'mt-ticket-bus'),
                    'saving' => __('Saving...', 'mt-ticket-bus'),
                    'errorOccurred' => __('An error occurred. Please try again.', 'mt-ticket-bus'),
                    'errorOccurredSaving' => __('An error occurred while saving the bus.', 'mt-ticket-bus'),
                    'errorOccurredDeleting' => __('An error occurred while deleting the bus.', 'mt-ticket-bus'),
                    'errorOccurredSavingRoute' => __('An error occurred while saving the route.', 'mt-ticket-bus'),
                    'errorOccurredDeletingRoute' => __('An error occurred while deleting the route.', 'mt-ticket-bus'),
                    'confirmDeleteBus' => __('Are you sure you want to delete this bus?', 'mt-ticket-bus'),
                    'confirmDeleteRoute' => __('Are you sure you want to delete this route?', 'mt-ticket-bus'),
                    'confirmDeleteSchedule' => __('Are you sure you want to delete this schedule?', 'mt-ticket-bus'),
                    'errorOccurredSavingSchedule' => __('An error occurred while saving the schedule.', 'mt-ticket-bus'),
                    'scheduleInfo' => __('Schedule Information', 'mt-ticket-bus'),
                    'scheduleName' => __('Name', 'mt-ticket-bus'),
                    'scheduleRoute' => __('Route', 'mt-ticket-bus'),
                    'scheduleCourses' => __('Courses', 'mt-ticket-bus'),
                    'scheduleFrequency' => __('Frequency', 'mt-ticket-bus'),
                    'scheduleStatus' => __('Status', 'mt-ticket-bus'),
                    'loading' => __('Loading...', 'mt-ticket-bus'),
                    'ok' => __('OK', 'mt-ticket-bus'),
                    'yes' => __('Yes', 'mt-ticket-bus'),
                    'cancel' => __('Cancel', 'mt-ticket-bus'),
                    'selectRoute' => __('-- Select Route --', 'mt-ticket-bus'),
                    'selectSchedule' => __('-- Select Schedule --', 'mt-ticket-bus'),
                    'selectCourse' => __('-- Select Course --', 'mt-ticket-bus'),
                    'noSchedulesFound' => __('No schedules found.', 'mt-ticket-bus'),
                    'noCoursesFound' => __('No courses found.', 'mt-ticket-bus'),
                    'errorLoadingSchedules' => __('Error loading schedules.', 'mt-ticket-bus'),
                    'errorLoadingCourses' => __('Error loading courses.', 'mt-ticket-bus'),
                    'noSeatLayoutData' => __('No seat layout data available.', 'mt-ticket-bus'),
                    'invalidSeatLayout' => __('Invalid seat layout.', 'mt-ticket-bus'),
                    'orderId' => __('Order ID', 'mt-ticket-bus'),
                    'seatNumber' => __('Seat Number', 'mt-ticket-bus'),
                    'passengerName' => __('Passenger Name', 'mt-ticket-bus'),
                    'passengerEmail' => __('Passenger Email', 'mt-ticket-bus'),
                    'passengerPhone' => __('Passenger Phone', 'mt-ticket-bus'),
                    'departureDate' => __('Departure Date', 'mt-ticket-bus'),
                    'departureTime' => __('Departure Time', 'mt-ticket-bus'),
                    'status' => __('Status', 'mt-ticket-bus'),
                    'clickReservedSeat' => __('Click on a reserved seat to view reservation details.', 'mt-ticket-bus'),
                ),
                'adminUrl' => admin_url('post.php'),
            )
        );
    }

    /**
     * Render overview page
     */
    public function render_overview_page()
    {
        include MT_TICKET_BUS_PLUGIN_DIR . 'admin/views/overview.php';
    }

    /**
     * Render settings page
     */
    public function render_settings_page()
    {
        include MT_TICKET_BUS_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Render buses page
     */
    public function render_buses_page()
    {
        include MT_TICKET_BUS_PLUGIN_DIR . 'admin/views/buses.php';
    }

    /**
     * Render routes page
     */
    public function render_routes_page()
    {
        include MT_TICKET_BUS_PLUGIN_DIR . 'admin/views/routes.php';
    }

    /**
     * Render schedules page
     */
    public function render_schedules_page()
    {
        include MT_TICKET_BUS_PLUGIN_DIR . 'admin/views/schedules.php';
    }

    /**
     * Render reservations page
     */
    public function render_reservations_page()
    {
        include MT_TICKET_BUS_PLUGIN_DIR . 'admin/views/reservations.php';
    }
}
