<?php

/**
 * Schedules management page template
 *
 * @package MT_Ticket_Bus
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

$schedules = MT_Ticket_Bus_Schedules::get_instance()->get_all_schedules(array('status' => 'all'));
$edit_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
$edit_schedule = $edit_id ? MT_Ticket_Bus_Schedules::get_instance()->get_schedule($edit_id) : null;

// Get routes for dropdown
$routes = MT_Ticket_Bus_Routes::get_instance()->get_all_routes(array('status' => 'all'));

// Parse days of week if editing
$days_of_week = array();
if ($edit_schedule && !empty($edit_schedule->days_of_week)) {
    $days_of_week = MT_Ticket_Bus_Schedules::get_instance()->parse_days_of_week($edit_schedule->days_of_week);
}
?>

<div class="wrap mt-ticket-bus-schedules">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=mt-ticket-bus-schedules')); ?>" class="page-title-action"><?php esc_html_e('New Schedule', 'mt-ticket-bus'); ?></a>
    <hr class="wp-header-end">

    <?php
    // Show success message after save
    if (isset($_GET['saved']) && $_GET['saved'] == '1') {
        $edit_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
        $message = $edit_id > 0
            ? __('Schedule updated successfully.', 'mt-ticket-bus')
            : __('Schedule created successfully.', 'mt-ticket-bus');
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
    }
    ?>

    <div class="mt-schedules-container">
        <div class="mt-schedules-form">
            <h2><?php echo $edit_schedule ? esc_html__('Edit Schedule', 'mt-ticket-bus') : esc_html__('Add New Schedule', 'mt-ticket-bus'); ?></h2>

            <form id="mt-schedule-form" method="post">
                <?php wp_nonce_field('mt_ticket_bus_admin', 'nonce'); ?>
                <input type="hidden" name="action" value="mt_save_schedule" />
                <?php if ($edit_schedule) : ?>
                    <input type="hidden" name="id" value="<?php echo esc_attr($edit_id); ?>" />
                <?php endif; ?>

                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="schedule_name"><?php esc_html_e('Schedule Name', 'mt-ticket-bus'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="schedule_name" name="name" value="<?php echo $edit_schedule ? esc_attr($edit_schedule->name) : ''; ?>" class="regular-text" />
                                <p class="description"><?php esc_html_e('Optional: A descriptive name for this schedule.', 'mt-ticket-bus'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="route_id"><?php esc_html_e('Route', 'mt-ticket-bus'); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <select id="route_id" name="route_id" class="regular-text" required>
                                    <option value=""><?php esc_html_e('Select a route...', 'mt-ticket-bus'); ?></option>
                                    <?php foreach ($routes as $route) : ?>
                                        <?php
                                        $stations_display = '';
                                        
                                        // Only show stations in brackets if there are intermediate stations
                                        if (!empty($route->intermediate_stations)) {
                                            $stations = array();
                                            $intermediate = array_filter(array_map('trim', explode("\n", $route->intermediate_stations)));
                                            
                                            // Only proceed if we have intermediate stations
                                            if (!empty($intermediate)) {
                                                // Add start station
                                                if (!empty($route->start_station)) {
                                                    $stations[] = $route->start_station;
                                                }
                                                
                                                // Add intermediate stations
                                                $stations = array_merge($stations, $intermediate);
                                                
                                                // Add end station
                                                if (!empty($route->end_station)) {
                                                    $stations[] = $route->end_station;
                                                }
                                                
                                                if (!empty($stations)) {
                                                    $stations_display = ' (' . esc_html(implode(', ', $stations)) . ')';
                                                }
                                            }
                                        }
                                        ?>
                                        <option value="<?php echo esc_attr($route->id); ?>" <?php selected($edit_schedule ? $edit_schedule->route_id : '', $route->id); ?>>
                                            <?php echo esc_html($route->name) . $stations_display; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label><?php esc_html_e('Courses', 'mt-ticket-bus'); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <div class="mt-courses-container">
                                    <div class="mt-course-inputs">
                                        <label for="course_departure_time" style="display: inline-block; margin-right: 10px;">
                                            <?php esc_html_e('Departure Time:', 'mt-ticket-bus'); ?>
                                            <input type="time" id="course_departure_time" class="regular-text" style="width: auto; margin-left: 5px;" />
                                        </label>
                                        <label for="course_arrival_time" style="display: inline-block; margin-right: 10px;">
                                            <?php esc_html_e('Arrival Time:', 'mt-ticket-bus'); ?>
                                            <input type="time" id="course_arrival_time" class="regular-text" style="width: auto; margin-left: 5px;" />
                                        </label>
                                        <button type="button" id="add_course_btn" class="button"><?php esc_html_e('Add Course', 'mt-ticket-bus'); ?></button>
                                    </div>
                                    <div id="course_error" style="color: #dc3232; margin-top: 5px; display: none;"></div>
                                    <hr style="margin: 15px 0;" />
                                    <div id="courses_list" class="mt-courses-list">
                                        <?php
                                        // Load existing courses if editing
                                        $courses = array();
                                        if ($edit_schedule && !empty($edit_schedule->courses)) {
                                            $courses = json_decode($edit_schedule->courses, true);
                                            if (!is_array($courses)) {
                                                $courses = array();
                                            }
                                        }
                                        foreach ($courses as $course) {
                                            if (isset($course['departure_time']) && isset($course['arrival_time'])) {
                                                echo '<span class="mt-course-badge" data-departure="' . esc_attr($course['departure_time']) . '" data-arrival="' . esc_attr($course['arrival_time']) . '">';
                                                echo '<span class="mt-course-time">' . esc_html($course['departure_time']) . ' - ' . esc_html($course['arrival_time']) . '</span>';
                                                echo '<button type="button" class="mt-remove-course" aria-label="' . esc_attr__('Remove course', 'mt-ticket-bus') . '">×</button>';
                                                echo '</span>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <input type="hidden" id="courses_json" name="courses" value="<?php echo $edit_schedule && !empty($edit_schedule->courses) ? esc_attr($edit_schedule->courses) : '[]'; ?>" />
                                    <p class="description"><?php esc_html_e('Add one or more courses for this schedule. Each course must have a departure and arrival time.', 'mt-ticket-bus'); ?></p>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label><?php esc_html_e('Days of Week', 'mt-ticket-bus'); ?></label>
                            </th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="radio" name="days_of_week_type" value="all" <?php checked($days_of_week === 'all' || (is_string($days_of_week) && $days_of_week === 'all')); ?> />
                                        <?php esc_html_e('Every day', 'mt-ticket-bus'); ?>
                                    </label><br>
                                    <label>
                                        <input type="radio" name="days_of_week_type" value="weekdays" <?php checked($days_of_week === 'weekdays' || (is_string($days_of_week) && $days_of_week === 'weekdays')); ?> />
                                        <?php esc_html_e('Weekdays only (Monday-Friday)', 'mt-ticket-bus'); ?>
                                    </label><br>
                                    <label>
                                        <input type="radio" name="days_of_week_type" value="weekend" <?php checked($days_of_week === 'weekend' || (is_string($days_of_week) && $days_of_week === 'weekend')); ?> />
                                        <?php esc_html_e('Weekend only (Saturday-Sunday)', 'mt-ticket-bus'); ?>
                                    </label><br>
                                    <label>
                                        <input type="radio" name="days_of_week_type" value="custom" <?php checked(is_array($days_of_week) && !in_array($days_of_week, array('all', 'weekdays', 'weekend'))); ?> />
                                        <?php esc_html_e('Custom days:', 'mt-ticket-bus'); ?>
                                    </label><br>
                                    <div id="custom_days" style="margin-left: 20px; margin-top: 5px;">
                                        <?php
                                        $week_days = array(
                                            'monday' => __('Monday', 'mt-ticket-bus'),
                                            'tuesday' => __('Tuesday', 'mt-ticket-bus'),
                                            'wednesday' => __('Wednesday', 'mt-ticket-bus'),
                                            'thursday' => __('Thursday', 'mt-ticket-bus'),
                                            'friday' => __('Friday', 'mt-ticket-bus'),
                                            'saturday' => __('Saturday', 'mt-ticket-bus'),
                                            'sunday' => __('Sunday', 'mt-ticket-bus'),
                                        );
                                        $selected_days = is_array($days_of_week) ? $days_of_week : array();
                                        foreach ($week_days as $day_key => $day_label) :
                                        ?>
                                            <label style="display: inline-block; margin-right: 15px;">
                                                <input type="checkbox" name="days_of_week[]" value="<?php echo esc_attr($day_key); ?>" <?php checked(in_array($day_key, $selected_days)); ?> />
                                                <?php echo esc_html($day_label); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </fieldset>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="status"><?php esc_html_e('Status', 'mt-ticket-bus'); ?></label>
                            </th>
                            <td>
                                <select id="status" name="status">
                                    <option value="active" <?php selected($edit_schedule ? $edit_schedule->status : 'active', 'active'); ?>><?php esc_html_e('Active', 'mt-ticket-bus'); ?></option>
                                    <option value="inactive" <?php selected($edit_schedule ? $edit_schedule->status : '', 'inactive'); ?>><?php esc_html_e('Inactive', 'mt-ticket-bus'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button($edit_schedule ? __('Update Schedule', 'mt-ticket-bus') : __('Add Schedule', 'mt-ticket-bus')); ?>
            </form>
        </div>

        <div class="mt-schedules-list">
            <h2><?php esc_html_e('Schedules List', 'mt-ticket-bus'); ?></h2>

            <?php if (empty($schedules)) : ?>
                <p><?php esc_html_e('No schedules found.', 'mt-ticket-bus'); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('ID', 'mt-ticket-bus'); ?></th>
                            <th class="mt-schedule-name-col"><?php esc_html_e('Name', 'mt-ticket-bus'); ?></th>
                            <th class="mt-schedule-route-col"><?php esc_html_e('Route', 'mt-ticket-bus'); ?></th>
                            <th><?php esc_html_e('Actions', 'mt-ticket-bus'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $schedule) : ?>
                            <?php
                            $days_display = '—';
                            if (!empty($schedule->days_of_week)) {
                                $parsed_days = MT_Ticket_Bus_Schedules::get_instance()->parse_days_of_week($schedule->days_of_week);
                                if ($parsed_days === 'all') {
                                    $days_display = __('Every day', 'mt-ticket-bus');
                                } elseif ($parsed_days === 'weekdays') {
                                    $days_display = __('Weekdays', 'mt-ticket-bus');
                                } elseif ($parsed_days === 'weekend') {
                                    $days_display = __('Weekend', 'mt-ticket-bus');
                                } elseif (is_array($parsed_days)) {
                                    $days_display = implode(', ', array_map('ucfirst', $parsed_days));
                                }
                            }
                            
                            // Get route name
                            $route_name = '—';
                            $route_full_name = '—';
                            if (!empty($schedule->route_id)) {
                                $route = MT_Ticket_Bus_Routes::get_instance()->get_route($schedule->route_id);
                                if ($route) {
                                    $route_name = esc_html($route->name);
                                    $route_full_name = esc_html($route->name);
                                    
                                    // Add stations if intermediate stations exist
                                    if (!empty($route->intermediate_stations)) {
                                        $stations = array();
                                        $intermediate = array_filter(array_map('trim', explode("\n", $route->intermediate_stations)));
                                        
                                        if (!empty($intermediate)) {
                                            if (!empty($route->start_station)) {
                                                $stations[] = esc_html($route->start_station);
                                            }
                                            $stations = array_merge($stations, $intermediate);
                                            if (!empty($route->end_station)) {
                                                $stations[] = esc_html($route->end_station);
                                            }
                                            
                                            if (!empty($stations)) {
                                                $route_full_name .= ' (' . implode(', ', $stations) . ')';
                                            }
                                        }
                                    }
                                }
                            }
                            
                            // Parse courses for popup
                            $courses = array();
                            $courses_display = '—';
                            if (!empty($schedule->courses)) {
                                $courses = json_decode($schedule->courses, true);
                                if (is_array($courses) && !empty($courses)) {
                                    $course_times = array();
                                    foreach ($courses as $course) {
                                        if (isset($course['departure_time']) && isset($course['arrival_time'])) {
                                            $course_times[] = esc_html($course['departure_time']) . ' - ' . esc_html($course['arrival_time']);
                                        }
                                    }
                                    if (!empty($course_times)) {
                                        $courses_display = implode(', ', $course_times);
                                    }
                                }
                            }
                            
                            // Prepare data attributes for popup
                            $schedule_name = $schedule->name ? esc_attr($schedule->name) : '—';
                            $schedule_status = esc_attr(ucfirst($schedule->status));
                            ?>
                            <tr class="<?php echo $schedule->status === 'inactive' ? 'mt-schedule-inactive' : ''; ?>">
                                <td><?php echo esc_html($schedule->id); ?></td>
                                <td class="mt-schedule-name-col"><?php echo $schedule->name ? esc_html($schedule->name) : '—'; ?></td>
                                <td class="mt-schedule-route-col"><?php echo $route_name; ?></td>
                                <td class="mt-schedule-actions">
                                    <a href="#" class="mt-schedule-info" 
                                       data-name="<?php echo $schedule_name; ?>"
                                       data-route="<?php echo esc_attr($route_full_name); ?>"
                                       data-courses="<?php echo esc_attr($courses_display); ?>"
                                       data-frequency="<?php echo esc_attr($days_display); ?>"
                                       data-status="<?php echo $schedule_status; ?>"><?php esc_html_e('Info', 'mt-ticket-bus'); ?></a> |
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=mt-ticket-bus-schedules&edit=' . $schedule->id)); ?>"><?php esc_html_e('Edit', 'mt-ticket-bus'); ?></a> |
                                    <a href="#" class="mt-delete-schedule" data-id="<?php echo esc_attr($schedule->id); ?>"><?php esc_html_e('Delete', 'mt-ticket-bus'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>