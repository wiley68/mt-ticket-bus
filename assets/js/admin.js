/**
 * Admin JavaScript for MT Ticket Bus plugin
 *
 * @package MT_Ticket_Bus
 */

(function ($) {
    'use strict';

    $(document).ready(function () {

        // Show loading overlay on page container
        function showPageLoading() {
            // Try buses container first, then routes container, then schedules container
            var container = $('.mt-buses-container, .mt-routes-container, .mt-schedules-container');
            if (container.length === 0) {
                return;
            }

            // Check if overlay already exists
            if (container.find('.mt-page-loading-overlay').length > 0) {
                return;
            }

            // Create overlay
            var overlay = $('<div class="mt-page-loading-overlay"><div class="mt-form-loading-spinner"></div><p>' + mtTicketBusAdmin.i18n.loading + '</p></div>');
            container.css('position', 'relative').append(overlay);
        }

        // Show loading overlay if page is reloading after save
        if (window.location.href.indexOf('saved=1') !== -1) {
            // Remove the saved parameter from URL without reload
            var currentUrl = window.location.href;
            var newUrl = currentUrl.split('&saved=1')[0].split('?saved=1')[0].split('&saved=1&')[0].split('?saved=1&')[0];

            // Clean up any double separators
            newUrl = newUrl.replace(/[?&]{2,}/g, function (match) {
                return match.indexOf('?') !== -1 ? '?' : '&';
            });

            if (newUrl !== currentUrl) {
                window.history.replaceState({}, document.title, newUrl);
            }
        }

        // Handle Edit link clicks for buses
        $(document).on('click', '.mt-buses-list a[href*="edit="]', function (e) {
            // Show loading overlay immediately
            showPageLoading();
            // Allow navigation to proceed
        });

        // Handle Edit link clicks for routes
        $(document).on('click', '.mt-routes-list a[href*="edit="]', function (e) {
            // Show loading overlay immediately
            showPageLoading();
            // Allow navigation to proceed
        });

        // Handle New Bus button click
        $(document).on('click', '.mt-ticket-bus-buses .page-title-action', function (e) {
            // Check if link doesn't contain edit parameter (it's a "New" button)
            if ($(this).attr('href').indexOf('edit=') === -1) {
                // Show loading overlay immediately
                showPageLoading();
                // Allow navigation to proceed
            }
        });

        // Handle New Route button click
        $(document).on('click', '.mt-ticket-bus-routes .page-title-action', function (e) {
            // Check if link doesn't contain edit parameter (it's a "New" button)
            if ($(this).attr('href').indexOf('edit=') === -1) {
                // Show loading overlay immediately
                showPageLoading();
                // Allow navigation to proceed
            }
        });

        // Handle Edit link clicks for schedules
        $(document).on('click', '.mt-schedules-list a[href*="edit="]', function (e) {
            // Show loading overlay immediately
            showPageLoading();
            // Allow navigation to proceed
        });

        // Handle New Schedule button click
        $(document).on('click', '.mt-ticket-bus-schedules .page-title-action', function (e) {
            // Check if link doesn't contain edit parameter (it's a "New" button)
            if ($(this).attr('href').indexOf('edit=') === -1) {
                // Show loading overlay immediately
                showPageLoading();
                // Allow navigation to proceed
            }
        });

        // Courses management for schedules
        var coursesData = [];

        // Initialize courses from hidden field and existing badges
        function initializeCourses() {
            var coursesJson = $('#courses_json').val();
            if (coursesJson && coursesJson !== '[]') {
                try {
                    coursesData = JSON.parse(coursesJson);
                    if (!Array.isArray(coursesData)) {
                        coursesData = [];
                    }
                } catch (e) {
                    coursesData = [];
                }
            } else {
                // If no JSON, try to read from existing badges
                coursesData = [];
                $('#courses_list .mt-course-badge').each(function () {
                    var badge = $(this);
                    var departure = badge.data('departure');
                    var arrival = badge.data('arrival');
                    if (departure && arrival) {
                        coursesData.push({
                            departure_time: departure,
                            arrival_time: arrival
                        });
                    }
                });
                // Update hidden field with data from badges
                if (coursesData.length > 0) {
                    updateCoursesJson();
                }
            }
            renderCourses();
        }

        // Convert time string (HH:MM) to minutes for comparison
        function timeToMinutes(timeStr) {
            if (!timeStr) return 0;
            var parts = timeStr.split(':');
            return parseInt(parts[0]) * 60 + parseInt(parts[1]);
        }

        // Check if two time ranges overlap
        function coursesOverlap(course1, course2) {
            var dep1 = timeToMinutes(course1.departure_time);
            var arr1 = timeToMinutes(course1.arrival_time);
            var dep2 = timeToMinutes(course2.departure_time);
            var arr2 = timeToMinutes(course2.arrival_time);

            // Check if ranges overlap
            return (dep1 < arr2 && dep2 < arr1);
        }

        // Validate course before adding
        function validateCourse(departureTime, arrivalTime) {
            if (!departureTime || !arrivalTime) {
                return { valid: false, message: 'Both departure and arrival times are required.' };
            }

            var dep = timeToMinutes(departureTime);
            var arr = timeToMinutes(arrivalTime);

            if (dep >= arr) {
                return { valid: false, message: 'Arrival time must be after departure time.' };
            }

            var newCourse = {
                departure_time: departureTime,
                arrival_time: arrivalTime
            };

            // Check for overlaps with existing courses
            for (var i = 0; i < coursesData.length; i++) {
                if (coursesOverlap(newCourse, coursesData[i])) {
                    return {
                        valid: false,
                        message: 'This course overlaps with an existing course (' +
                            coursesData[i].departure_time + ' - ' + coursesData[i].arrival_time + ').'
                    };
                }
            }

            return { valid: true };
        }

        // Add course
        $('#add_course_btn').on('click', function () {
            var departureTime = $('#course_departure_time').val();
            var arrivalTime = $('#course_arrival_time').val();

            var validation = validateCourse(departureTime, arrivalTime);

            if (!validation.valid) {
                $('#course_error').text(validation.message).show();
                return;
            }

            $('#course_error').hide();

            coursesData.push({
                departure_time: departureTime,
                arrival_time: arrivalTime
            });

            // Sort courses chronologically
            coursesData.sort(function (a, b) {
                return timeToMinutes(a.departure_time) - timeToMinutes(b.departure_time);
            });

            renderCourses();
            updateCoursesJson();

            // Clear input fields
            $('#course_departure_time').val('');
            $('#course_arrival_time').val('');
        });

        // Remove course
        $(document).on('click', '.mt-remove-course', function () {
            var badge = $(this).closest('.mt-course-badge');
            var departureTime = badge.data('departure');
            var arrivalTime = badge.data('arrival');

            coursesData = coursesData.filter(function (course) {
                return course.departure_time !== departureTime || course.arrival_time !== arrivalTime;
            });

            renderCourses();
            updateCoursesJson();
        });

        // Render courses as badges
        function renderCourses() {
            var container = $('#courses_list');
            container.empty();

            if (coursesData.length === 0) {
                container.html('<span class="mt-course-badge empty-state">' +
                    'No courses added yet. Add courses using the form above.</span>');
                return;
            }

            coursesData.forEach(function (course) {
                var badge = $('<span class="mt-course-badge" data-departure="' +
                    course.departure_time + '" data-arrival="' + course.arrival_time + '">');
                badge.append('<span class="mt-course-time">' + course.departure_time + ' - ' +
                    course.arrival_time + '</span>');
                badge.append('<button type="button" class="mt-remove-course" aria-label="Remove course">×</button>');
                container.append(badge);
            });
        }

        // Update hidden JSON field
        function updateCoursesJson() {
            $('#courses_json').val(JSON.stringify(coursesData));
        }

        // Initialize on page load
        initializeCourses();

        // Handle days of week type change
        $('input[name="days_of_week_type"]').on('change', function () {
            if ($(this).val() === 'custom') {
                $('#custom_days').show();
            } else {
                $('#custom_days').hide();
                $('#custom_days input[type="checkbox"]').prop('checked', false);
            }
        });

        // Initialize days of week visibility
        if ($('input[name="days_of_week_type"]:checked').val() === 'custom') {
            $('#custom_days').show();
        } else {
            $('#custom_days').hide();
        }

        // Handle schedule form submission
        $('#mt-schedule-form').on('submit', function (e) {
            e.preventDefault();

            // Validate that at least one course is added
            if (coursesData.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'At least one course is required.',
                    confirmButtonText: mtTicketBusAdmin.i18n.ok
                });
                return;
            }

            // Ensure courses JSON is included - update before serialization
            updateCoursesJson();

            // Double check that the hidden field has data
            var coursesValue = $('#courses_json').val();
            if (!coursesValue || coursesValue === '[]') {
                Swal.fire({
                    icon: 'warning',
                    title: 'At least one course is required.',
                    confirmButtonText: mtTicketBusAdmin.i18n.ok
                });
                return;
            }

            // Build form data object instead of string to ensure courses are included
            var formDataObj = {};
            $(this).find('input, select, textarea').each(function () {
                var $field = $(this);
                var name = $field.attr('name');
                var type = $field.attr('type');

                // Skip courses field - we'll add it manually
                if (name === 'courses') {
                    return;
                }

                if (name && name !== 'nonce') {
                    if (type === 'checkbox' || type === 'radio') {
                        if ($field.is(':checked')) {
                            if (formDataObj[name]) {
                                if (!Array.isArray(formDataObj[name])) {
                                    formDataObj[name] = [formDataObj[name]];
                                }
                                formDataObj[name].push($field.val());
                            } else {
                                formDataObj[name] = $field.val();
                            }
                        }
                    } else {
                        formDataObj[name] = $field.val();
                    }
                }
            });

            // Process days_of_week based on selection
            var daysType = $('input[name="days_of_week_type"]:checked').val();
            var daysValue = '';

            if (daysType === 'all' || daysType === 'weekdays' || daysType === 'weekend') {
                daysValue = daysType;
            } else if (daysType === 'custom') {
                var selectedDays = [];
                $('#custom_days input[type="checkbox"]:checked').each(function () {
                    selectedDays.push($(this).val());
                });
                if (selectedDays.length > 0) {
                    daysValue = JSON.stringify(selectedDays);
                }
            }

            // Remove individual days_of_week[] from formDataObj and add processed value
            delete formDataObj['days_of_week[]'];
            if (daysValue) {
                formDataObj['days_of_week'] = daysValue;
            }

            // Convert to URL-encoded string (excluding courses)
            var formData = $.param(formDataObj);

            // Add courses separately without double encoding
            formData += '&courses=' + encodeURIComponent(coursesValue);
            formData += '&nonce=' + mtTicketBusAdmin.nonce;

            // Get form container
            var formContainer = $('.mt-schedules-form');

            // Show loading overlay
            showFormLoading(formContainer);

            // Show loading indicator on button
            var submitButton = $(this).find('input[type="submit"]');
            var originalText = submitButton.val();
            submitButton.prop('disabled', true).val(mtTicketBusAdmin.i18n.saving);

            $.ajax({
                url: mtTicketBusAdmin.ajaxUrl,
                type: 'POST',
                data: formData + '&action=mt_save_schedule',
                success: function (response) {
                    if (response.success) {
                        // Switch to page-level loading overlay (covers both blocks)
                        hideFormLoading(formContainer);
                        showPageLoading();

                        // Add success parameter to URL for showing message after reload
                        var currentUrl = window.location.href;
                        var separator = currentUrl.indexOf('?') !== -1 ? '&' : '?';
                        var newUrl = currentUrl.split('&saved=')[0].split('?saved=')[0] + separator + 'saved=1';

                        // Preserve edit parameter if present
                        if (currentUrl.indexOf('edit=') !== -1) {
                            var editMatch = currentUrl.match(/[?&]edit=(\d+)/);
                            if (editMatch) {
                                var editParam = editMatch[0].substring(0, 1) === '&' ? editMatch[0] : '&' + editMatch[0];
                                if (newUrl.indexOf('edit=') === -1) {
                                    newUrl += editParam;
                                }
                            }
                        }

                        // Reload page - overlay will remain visible during reload
                        window.location.href = newUrl;
                    } else {
                        // Hide loading overlay only on error
                        hideFormLoading(formContainer);
                        submitButton.prop('disabled', false).val(originalText);

                        // Show error message prominently
                        var errorMsg = response.data && response.data.message ? response.data.message : mtTicketBusAdmin.i18n.errorOccurredSavingSchedule;


                        Swal.fire({
                            icon: 'error',
                            title: errorMsg,
                            confirmButtonText: mtTicketBusAdmin.i18n.ok
                        });
                    }
                },
                error: function () {
                    // Hide loading overlay on error
                    hideFormLoading(formContainer);
                    submitButton.prop('disabled', false).val(originalText);
                    Swal.fire({
                        icon: 'error',
                        title: mtTicketBusAdmin.i18n.errorOccurred,
                        confirmButtonText: mtTicketBusAdmin.i18n.ok
                    });
                }
            });
        });

        // Handle schedule info popup
        $(document).on('click', '.mt-schedule-info', function (e) {
            e.preventDefault();

            var $infoLink = $(this);
            var name = $infoLink.data('name') || '—';
            var route = $infoLink.data('route') || '—';
            var courses = $infoLink.data('courses') || '—';
            var frequency = $infoLink.data('frequency') || '—';
            var status = $infoLink.data('status') || '—';

            var htmlContent = '<div style="text-align: left; line-height: 1.8;">';
            htmlContent += '<p><strong>' + mtTicketBusAdmin.i18n.scheduleName + ':</strong> ' + name + '</p>';
            htmlContent += '<p><strong>' + mtTicketBusAdmin.i18n.scheduleRoute + ':</strong> ' + route + '</p>';
            htmlContent += '<p><strong>' + mtTicketBusAdmin.i18n.scheduleCourses + ':</strong> ' + courses + '</p>';
            htmlContent += '<p><strong>' + mtTicketBusAdmin.i18n.scheduleFrequency + ':</strong> ' + frequency + '</p>';
            htmlContent += '<p><strong>' + mtTicketBusAdmin.i18n.scheduleStatus + ':</strong> ' + status + '</p>';
            htmlContent += '</div>';

            Swal.fire({
                icon: 'info',
                title: mtTicketBusAdmin.i18n.scheduleInfo,
                html: htmlContent,
                confirmButtonText: mtTicketBusAdmin.i18n.ok,
                width: '600px'
            });
        });

        // Handle schedule deletion
        $('.mt-delete-schedule').on('click', function (e) {
            e.preventDefault();

            var $deleteLink = $(this);
            var scheduleId = $deleteLink.data('id');

            Swal.fire({
                icon: 'warning',
                title: mtTicketBusAdmin.i18n.confirmDeleteSchedule,
                showCancelButton: true,
                confirmButtonText: mtTicketBusAdmin.i18n.yes,
                cancelButtonText: mtTicketBusAdmin.i18n.cancel,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6'
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                // Show loading overlay before AJAX request
                showPageLoading();

                $.ajax({
                    url: mtTicketBusAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'mt_delete_schedule',
                        id: scheduleId,
                        nonce: mtTicketBusAdmin.nonce
                    },
                    success: function (response) {
                        if (response.success) {
                            // Overlay will remain visible during reload
                            location.reload();
                        } else {
                            // Hide overlay on error
                            $('.mt-page-loading-overlay').remove();
                            Swal.fire({
                                icon: 'error',
                                title: response.data.message,
                                confirmButtonText: mtTicketBusAdmin.i18n.ok
                            });
                        }
                    },
                    error: function () {
                        // Hide overlay on error
                        $('.mt-page-loading-overlay').remove();
                        Swal.fire({
                            icon: 'error',
                            title: mtTicketBusAdmin.i18n.errorOccurred,
                            confirmButtonText: mtTicketBusAdmin.i18n.ok
                        });
                    }
                });
            });
        });

        // Seat layout management
        var seatLayoutData = {};

        // Initialize seat layout if editing
        function initializeSeatLayout() {
            var seatLayoutJson = $('#seat_layout').val();
            seatLayoutData = {}; // Reset first

            if (seatLayoutJson) {
                try {
                    var parsed = JSON.parse(seatLayoutJson);
                    if (parsed && parsed.seats && typeof parsed.seats === 'object') {
                        // Copy all seat data, preserving true/false values
                        for (var seatId in parsed.seats) {
                            if (parsed.seats.hasOwnProperty(seatId)) {
                                seatLayoutData[seatId] = parsed.seats[seatId] === true || parsed.seats[seatId] === 'true' || parsed.seats[seatId] === 1;
                            }
                        }
                    }
                } catch (e) {
                    console.error('Error parsing seat layout JSON:', e);
                    seatLayoutData = {};
                }
            }
            generateSeatLayout();
        }

        // Generate seat layout based on configuration
        function generateSeatLayout() {
            var leftSeats = parseInt($('#left_column_seats').val()) || 0;
            var rightSeats = parseInt($('#right_column_seats').val()) || 0;
            var rows = parseInt($('#number_of_rows').val()) || 10;

            if (leftSeats === 0 && rightSeats === 0) {
                $('#mt-seat-layout-container').html('<p class="description">Please configure seat columns first.</p>');
                $('#seat_layout').val('');
                $('#total_seats').val(0);
                return;
            }

            var container = $('#mt-seat-layout-container');
            container.empty();

            // Create layout structure
            var layoutTable = $('<table class="mt-bus-layout-table"></table>');
            var headerRow = $('<tr></tr>');

            // Left columns header
            for (var l = 0; l < leftSeats; l++) {
                var colLetter = String.fromCharCode(65 + l); // A, B, C...
                headerRow.append($('<th>' + colLetter + '</th>'));
            }

            // Aisle header
            if (leftSeats > 0 && rightSeats > 0) {
                headerRow.append($('<th class="mt-aisle-header">Aisle</th>'));
            }

            // Right columns header
            for (var r = 0; r < rightSeats; r++) {
                var colLetter = String.fromCharCode(65 + leftSeats + r);
                headerRow.append($('<th>' + colLetter + '</th>'));
            }

            layoutTable.append(headerRow);

            // Generate rows and initialize seatLayoutData
            var totalAvailableSeats = 0;
            for (var row = 1; row <= rows; row++) {
                var rowElement = $('<tr></tr>');

                // Left columns
                for (var l = 0; l < leftSeats; l++) {
                    var colLetter = String.fromCharCode(65 + l);
                    var seatId = colLetter + row;
                    // Initialize seat in seatLayoutData if not exists
                    if (seatLayoutData[seatId] === undefined) {
                        seatLayoutData[seatId] = true;
                    }
                    var isAvailable = seatLayoutData[seatId] === true;
                    if (isAvailable) totalAvailableSeats++;

                    var seatCell = $('<td></td>');
                    var seatDiv = $('<div class="mt-seat ' + (isAvailable ? 'available' : 'disabled') + '" data-seat="' + seatId + '">' + seatId + '</div>');
                    seatCell.append(seatDiv);
                    rowElement.append(seatCell);
                }

                // Aisle
                if (leftSeats > 0 && rightSeats > 0) {
                    rowElement.append($('<td class="mt-aisle-cell"></td>'));
                }

                // Right columns
                for (var r = 0; r < rightSeats; r++) {
                    var colLetter = String.fromCharCode(65 + leftSeats + r);
                    var seatId = colLetter + row;
                    // Initialize seat in seatLayoutData if not exists
                    if (seatLayoutData[seatId] === undefined) {
                        seatLayoutData[seatId] = true;
                    }
                    var isAvailable = seatLayoutData[seatId] === true;
                    if (isAvailable) totalAvailableSeats++;

                    var seatCell = $('<td></td>');
                    var seatDiv = $('<div class="mt-seat ' + (isAvailable ? 'available' : 'disabled') + '" data-seat="' + seatId + '">' + seatId + '</div>');
                    seatCell.append(seatDiv);
                    rowElement.append(seatCell);
                }

                layoutTable.append(rowElement);
            }

            container.append(layoutTable);

            // Update total seats
            $('#total_seats').val(totalAvailableSeats);

            // Update hidden JSON field
            updateSeatLayoutJson();

            // Add click handlers
            $('.mt-seat').on('click', function () {
                var seatId = $(this).data('seat');
                var isAvailable = $(this).hasClass('available');

                if (isAvailable) {
                    $(this).removeClass('available').addClass('disabled');
                    seatLayoutData[seatId] = false;
                } else {
                    $(this).removeClass('disabled').addClass('available');
                    seatLayoutData[seatId] = true;
                }

                updateTotalSeats();
                updateSeatLayoutJson();
            });
        }

        // Update total seats count
        function updateTotalSeats() {
            var total = 0;
            $('.mt-seat.available').each(function () {
                total++;
            });
            $('#total_seats').val(total);
        }

        // Update hidden JSON field
        function updateSeatLayoutJson() {
            var leftSeats = parseInt($('#left_column_seats').val()) || 0;
            var rightSeats = parseInt($('#right_column_seats').val()) || 0;
            var rows = parseInt($('#number_of_rows').val()) || 10;

            // Ensure all seats are in seatLayoutData with current state
            var currentSeatData = {};
            for (var row = 1; row <= rows; row++) {
                // Left columns
                for (var l = 0; l < leftSeats; l++) {
                    var colLetter = String.fromCharCode(65 + l);
                    var seatId = colLetter + row;
                    // Get current state from DOM or seatLayoutData
                    var seatElement = $('.mt-seat[data-seat="' + seatId + '"]');
                    if (seatElement.length) {
                        currentSeatData[seatId] = seatElement.hasClass('available');
                    } else {
                        currentSeatData[seatId] = seatLayoutData[seatId] !== undefined ? seatLayoutData[seatId] : true;
                    }
                }
                // Right columns
                for (var r = 0; r < rightSeats; r++) {
                    var colLetter = String.fromCharCode(65 + leftSeats + r);
                    var seatId = colLetter + row;
                    // Get current state from DOM or seatLayoutData
                    var seatElement = $('.mt-seat[data-seat="' + seatId + '"]');
                    if (seatElement.length) {
                        currentSeatData[seatId] = seatElement.hasClass('available');
                    } else {
                        currentSeatData[seatId] = seatLayoutData[seatId] !== undefined ? seatLayoutData[seatId] : true;
                    }
                }
            }

            // Update seatLayoutData with current state
            seatLayoutData = currentSeatData;

            var layoutJson = {
                config: {
                    left: leftSeats,
                    right: rightSeats,
                    rows: rows
                },
                seats: seatLayoutData
            };

            $('#seat_layout').val(JSON.stringify(layoutJson));
        }

        // Initialize on page load
        initializeSeatLayout();

        // Regenerate layout when configuration changes
        $('#left_column_seats, #right_column_seats, #number_of_rows').on('change', function () {
            // Keep existing seat data, but remove seats that are no longer in the layout
            var leftSeats = parseInt($('#left_column_seats').val()) || 0;
            var rightSeats = parseInt($('#right_column_seats').val()) || 0;
            var rows = parseInt($('#number_of_rows').val()) || 10;

            // Create new seatLayoutData with only valid seats
            var newSeatLayoutData = {};
            for (var row = 1; row <= rows; row++) {
                // Left columns
                for (var l = 0; l < leftSeats; l++) {
                    var colLetter = String.fromCharCode(65 + l);
                    var seatId = colLetter + row;
                    // Keep existing value or default to true
                    newSeatLayoutData[seatId] = seatLayoutData[seatId] !== undefined ? seatLayoutData[seatId] : true;
                }
                // Right columns
                for (var r = 0; r < rightSeats; r++) {
                    var colLetter = String.fromCharCode(65 + leftSeats + r);
                    var seatId = colLetter + row;
                    // Keep existing value or default to true
                    newSeatLayoutData[seatId] = seatLayoutData[seatId] !== undefined ? seatLayoutData[seatId] : true;
                }
            }
            seatLayoutData = newSeatLayoutData;
            generateSeatLayout();
        });

        // Check registration number uniqueness on blur
        $('#registration_number').on('blur', function () {
            var registrationNumber = $(this).val();
            var busId = $('input[name="id"]').val() || 0;
            var errorElement = $('#registration_number_error');

            if (!registrationNumber) {
                errorElement.hide();
                return;
            }

            $.ajax({
                url: mtTicketBusAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mt_check_registration_number',
                    registration_number: registrationNumber,
                    exclude_id: busId,
                    nonce: mtTicketBusAdmin.nonce
                },
                success: function (response) {
                    if (response.success) {
                        errorElement.hide();
                        $('#registration_number').removeClass('error');
                    } else {
                        errorElement.text(response.data.message).show();
                        $('#registration_number').addClass('error');
                    }
                },
                error: function () {
                    errorElement.hide();
                }
            });
        });

        // Handle bus form submission
        $('#mt-bus-form').on('submit', function (e) {
            e.preventDefault();

            // Basic validation
            var registrationNumber = $('#registration_number').val();
            if (!registrationNumber || registrationNumber.trim() === '') {
                Swal.fire({
                    icon: 'warning',
                    title: mtTicketBusAdmin.i18n.registrationNumberRequired,
                    confirmButtonText: mtTicketBusAdmin.i18n.ok
                }).then(function () {
                    $('#registration_number').focus();
                });
                return;
            }

            // Check if there's an error shown
            if ($('#registration_number_error').is(':visible')) {
                Swal.fire({
                    icon: 'warning',
                    title: mtTicketBusAdmin.i18n.fixRegistrationError,
                    confirmButtonText: mtTicketBusAdmin.i18n.ok
                }).then(function () {
                    $('#registration_number').focus();
                });
                return;
            }

            // Validate seat configuration
            var leftSeats = parseInt($('#left_column_seats').val()) || 0;
            var rightSeats = parseInt($('#right_column_seats').val()) || 0;
            if (leftSeats === 0 && rightSeats === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: mtTicketBusAdmin.i18n.configureSeatColumns,
                    confirmButtonText: mtTicketBusAdmin.i18n.ok
                }).then(function () {
                    $('#left_column_seats').focus();
                });
                return;
            }

            // Ensure seat layout is updated before submission
            updateSeatLayoutJson();

            var formData = $(this).serialize();
            formData += '&nonce=' + mtTicketBusAdmin.nonce;

            // Get form container
            var formContainer = $('.mt-buses-form');

            // Show loading overlay
            showFormLoading(formContainer);

            // Show loading indicator on button
            var submitButton = $(this).find('input[type="submit"]');
            var originalText = submitButton.val();
            submitButton.prop('disabled', true).val(mtTicketBusAdmin.i18n.saving);

            $.ajax({
                url: mtTicketBusAdmin.ajaxUrl,
                type: 'POST',
                data: formData + '&action=mt_save_bus',
                success: function (response) {
                    if (response.success) {
                        // Switch to page-level loading overlay (covers both blocks)
                        hideFormLoading(formContainer);
                        showPageLoading();

                        // Add success parameter to URL for showing message after reload
                        var currentUrl = window.location.href;
                        var separator = currentUrl.indexOf('?') !== -1 ? '&' : '?';
                        var newUrl = currentUrl.split('&saved=')[0].split('?saved=')[0] + separator + 'saved=1';

                        // Reload page - overlay will remain visible during reload
                        window.location.href = newUrl;
                    } else {
                        // Hide loading overlay only on error
                        hideFormLoading(formContainer);
                        submitButton.prop('disabled', false).val(originalText);

                        // Show error message prominently
                        var errorMsg = response.data && response.data.message ? response.data.message : mtTicketBusAdmin.i18n.errorOccurredSaving;
                        Swal.fire({
                            icon: 'error',
                            title: errorMsg,
                            confirmButtonText: mtTicketBusAdmin.i18n.ok
                        });

                        // If it's a registration number error, highlight the field
                        if (errorMsg.indexOf('registration number') !== -1 || errorMsg.indexOf('Registration number') !== -1) {
                            $('#registration_number').addClass('error').focus();
                            $('#registration_number_error').text(errorMsg).show();
                        }
                    }
                },
                error: function (xhr, status, error) {
                    hideFormLoading(formContainer);
                    submitButton.prop('disabled', false).val(originalText);
                    var errorMsg = mtTicketBusAdmin.i18n.errorOccurred;

                    // Try to parse error response
                    try {
                        var errorResponse = JSON.parse(xhr.responseText);
                        if (errorResponse.data && errorResponse.data.message) {
                            errorMsg = errorResponse.data.message;
                        }
                    } catch (e) {
                        // Use default error message
                    }

                    Swal.fire({
                        icon: 'error',
                        title: errorMsg,
                        confirmButtonText: mtTicketBusAdmin.i18n.ok
                    });

                    // If it's a registration number error, highlight the field
                    if (errorMsg.indexOf('registration number') !== -1 || errorMsg.indexOf('Registration number') !== -1) {
                        $('#registration_number').addClass('error').focus();
                        $('#registration_number_error').text(errorMsg).show();
                    }
                }
            });
        });

        // Show loading overlay on form
        function showFormLoading(container) {
            // Check if overlay already exists
            if (container.find('.mt-form-loading-overlay').length > 0) {
                return;
            }

            // Create overlay
            var overlay = $('<div class="mt-form-loading-overlay"><div class="mt-form-loading-spinner"></div><p>' + mtTicketBusAdmin.i18n.saving + '</p></div>');
            container.css('position', 'relative').append(overlay);

            // Disable all form inputs
            container.find('input, select, textarea, button').prop('disabled', true);
        }

        // Hide loading overlay from form
        function hideFormLoading(container) {
            container.find('.mt-form-loading-overlay').remove();

            // Re-enable all form inputs
            container.find('input, select, textarea, button').prop('disabled', false);
        }

        // Intermediate stations management for routes
        var stationsData = [];

        // Initialize stations from hidden field and existing badges
        function initializeStations() {
            var stationsJson = $('#intermediate_stations_json').val();
            stationsData = [];

            // Check if we have valid JSON data
            if (stationsJson && stationsJson.trim() !== '' && stationsJson !== '[]') {
                try {
                    var parsed = JSON.parse(stationsJson);
                    if (Array.isArray(parsed) && parsed.length > 0) {
                        // Validate each station has a name
                        parsed.forEach(function (station) {
                            if (station && station.name && station.name.trim() !== '') {
                                stationsData.push({
                                    name: station.name.trim(),
                                    duration: parseInt(station.duration, 10) || 0
                                });
                            }
                        });
                    }
                } catch (e) {
                    console.error('Error parsing stations JSON:', e);
                    stationsData = [];
                }
            }

            // If no data from JSON, try to read from existing badges (for backward compatibility)
            if (stationsData.length === 0) {
                $('#stations_list .mt-station-badge:not(.empty-state)').each(function () {
                    var badge = $(this);
                    var name = badge.data('name');
                    var duration = parseInt(badge.data('duration'), 10) || 0;
                    if (name && name.trim() !== '') {
                        stationsData.push({
                            name: name.trim(),
                            duration: duration
                        });
                    }
                });
            }

            // Sort by duration
            stationsData.sort(function (a, b) {
                return a.duration - b.duration;
            });

            // Update hidden field with clean data
            updateStationsJson();
            renderStations();
        }

        // Validate station before adding
        function validateStation(stationName, stationDuration, totalDuration) {
            if (!stationName || stationName.trim() === '') {
                return { valid: false, message: 'Station name is required.' };
            }

            var duration = parseInt(stationDuration, 10);
            if (isNaN(duration) || duration < 0) {
                return { valid: false, message: 'Duration must be a valid positive number.' };
            }

            // Check if station name already exists
            for (var i = 0; i < stationsData.length; i++) {
                if (stationsData[i].name.toLowerCase() === stationName.toLowerCase()) {
                    return { valid: false, message: 'A station with this name already exists.' };
                }
            }

            // Check if duration is greater than previous station
            if (stationsData.length > 0) {
                var lastDuration = stationsData[stationsData.length - 1].duration;
                if (duration <= lastDuration) {
                    return {
                        valid: false,
                        message: 'Duration must be greater than the previous station (' + lastDuration + ' minutes).'
                    };
                }
            }

            // Check if duration is less than total route duration
            var totalDur = parseInt(totalDuration, 10);
            if (!isNaN(totalDur) && totalDur > 0 && duration >= totalDur) {
                return {
                    valid: false,
                    message: 'Station duration must be less than the total route duration (' + totalDur + ' minutes).'
                };
            }

            return { valid: true };
        }

        // Add station
        $('#add_station_btn').on('click', function () {
            var stationName = $('#station_name').val().trim();
            var stationDuration = $('#station_duration').val();
            var totalDuration = $('#duration').val();

            var validation = validateStation(stationName, stationDuration, totalDuration);

            if (!validation.valid) {
                $('#station_error').text(validation.message).show();
                return;
            }

            $('#station_error').hide();

            stationsData.push({
                name: stationName,
                duration: parseInt(stationDuration, 10)
            });

            // Sort stations by duration
            stationsData.sort(function (a, b) {
                return a.duration - b.duration;
            });

            renderStations();
            updateStationsJson();

            // Clear input fields
            $('#station_name').val('');
            $('#station_duration').val('');
        });

        // Remove station
        $(document).on('click', '.mt-remove-station', function () {
            var badge = $(this).closest('.mt-station-badge');
            var stationName = badge.data('name');
            var stationDuration = parseInt(badge.data('duration'), 10);

            stationsData = stationsData.filter(function (station) {
                return station.name !== stationName || station.duration !== stationDuration;
            });

            renderStations();
            updateStationsJson();
        });

        // Render stations as badges
        function renderStations() {
            var container = $('#stations_list');
            container.empty();

            if (stationsData.length === 0) {
                container.html('<span class="mt-station-badge empty-state">' +
                    'No intermediate stations added yet. Add stations using the form above.</span>');
                return;
            }

            stationsData.forEach(function (station) {
                var badge = $('<span class="mt-station-badge" data-name="' +
                    station.name + '" data-duration="' + station.duration + '">');
                badge.append('<span class="mt-station-info">' + station.name + ' (' +
                    station.duration + ' min)</span>');
                badge.append('<button type="button" class="mt-remove-station" aria-label="Remove station">×</button>');
                container.append(badge);
            });
        }

        // Update hidden JSON field
        function updateStationsJson() {
            // If no stations, save empty string instead of empty array
            if (stationsData.length === 0) {
                $('#intermediate_stations_json').val('');
            } else {
                // Ensure we have clean data before stringifying
                var cleanData = stationsData.map(function (station) {
                    return {
                        name: String(station.name || '').trim(),
                        duration: parseInt(station.duration, 10) || 0
                    };
                }).filter(function (station) {
                    return station.name !== '';
                });

                if (cleanData.length > 0) {
                    $('#intermediate_stations_json').val(JSON.stringify(cleanData));
                } else {
                    $('#intermediate_stations_json').val('');
                }
            }
        }

        // Validate route form before submission
        function validateRouteForm() {
            var totalDuration = parseInt($('#duration').val(), 10);

            if (isNaN(totalDuration) || totalDuration <= 0) {
                return { valid: true }; // Total duration is optional, skip validation if not set
            }

            // Check if total duration is greater than last intermediate station
            if (stationsData.length > 0) {
                var lastStationDuration = stationsData[stationsData.length - 1].duration;
                if (totalDuration <= lastStationDuration) {
                    return {
                        valid: false,
                        message: 'Total route duration (' + totalDuration + ' minutes) must be greater than the last intermediate station duration (' + lastStationDuration + ' minutes).'
                    };
                }
            }

            return { valid: true };
        }

        // Initialize on page load (only if route form exists)
        if ($('#mt-route-form').length > 0) {
            initializeStations();

            // Re-validate when total duration changes
            $('#duration').on('change blur', function () {
                var totalDuration = parseInt($(this).val(), 10);

                // Validate all existing stations against new total duration
                if (!isNaN(totalDuration) && totalDuration > 0) {
                    var hasInvalid = false;
                    var invalidMessage = '';

                    for (var i = 0; i < stationsData.length; i++) {
                        if (stationsData[i].duration >= totalDuration) {
                            hasInvalid = true;
                            invalidMessage = 'Station "' + stationsData[i].name + '" has duration (' +
                                stationsData[i].duration + ' min) greater than or equal to total route duration (' +
                                totalDuration + ' min).';
                            break;
                        }
                    }

                    if (hasInvalid) {
                        $('#station_error').text(invalidMessage).show();
                    } else {
                        $('#station_error').hide();
                    }
                } else {
                    $('#station_error').hide();
                }
            });
        }

        // Handle route form submission
        $('#mt-route-form').on('submit', function (e) {
            e.preventDefault();

            // Validate intermediate stations vs total duration
            var routeValidation = validateRouteForm();
            if (!routeValidation.valid) {
                Swal.fire({
                    icon: 'warning',
                    title: routeValidation.message,
                    confirmButtonText: mtTicketBusAdmin.i18n.ok
                });
                return;
            }

            // Ensure stations JSON is included and up-to-date
            updateStationsJson();

            // Build form data manually to avoid double encoding of JSON
            var formDataObj = {};
            var stationsJsonValue = '';

            $(this).find('input, select, textarea').each(function () {
                var $field = $(this);
                var name = $field.attr('name');
                if (name && name !== 'nonce' && name !== 'intermediate_stations') {
                    if ($field.attr('type') === 'checkbox' || $field.attr('type') === 'radio') {
                        if ($field.is(':checked')) {
                            if (formDataObj[name]) {
                                if (!Array.isArray(formDataObj[name])) {
                                    formDataObj[name] = [formDataObj[name]];
                                }
                                formDataObj[name].push($field.val());
                            } else {
                                formDataObj[name] = $field.val();
                            }
                        }
                    } else {
                        formDataObj[name] = $field.val();
                    }
                } else if (name === 'intermediate_stations') {
                    // Get stations JSON value separately
                    stationsJsonValue = $field.val();
                }
            });

            // Add intermediate_stations to formDataObj
            formDataObj['intermediate_stations'] = stationsJsonValue || '';
            formDataObj['action'] = 'mt_save_route';
            formDataObj['nonce'] = mtTicketBusAdmin.nonce;

            // Get form container
            var formContainer = $('.mt-routes-form');

            // Show loading overlay
            showFormLoading(formContainer);

            // Show loading indicator on button
            var submitButton = $(this).find('input[type="submit"]');
            var originalText = submitButton.val();
            submitButton.prop('disabled', true).val(mtTicketBusAdmin.i18n.saving);

            $.ajax({
                url: mtTicketBusAdmin.ajaxUrl,
                type: 'POST',
                data: formDataObj,
                success: function (response) {
                    if (response.success) {
                        // Switch to page-level loading overlay (covers both blocks)
                        hideFormLoading(formContainer);
                        showPageLoading();

                        // Add success parameter to URL for showing message after reload
                        var currentUrl = window.location.href;
                        var separator = currentUrl.indexOf('?') !== -1 ? '&' : '?';
                        var newUrl = currentUrl.split('&saved=')[0].split('?saved=')[0] + separator + 'saved=1';

                        // Preserve edit parameter if present
                        if (currentUrl.indexOf('edit=') !== -1) {
                            var editMatch = currentUrl.match(/[?&]edit=(\d+)/);
                            if (editMatch) {
                                var editParam = editMatch[0].substring(0, 1) === '&' ? editMatch[0] : '&' + editMatch[0];
                                if (newUrl.indexOf('edit=') === -1) {
                                    newUrl += editParam;
                                }
                            }
                        }

                        // Reload page - overlay will remain visible during reload
                        window.location.href = newUrl;
                    } else {
                        // Hide loading overlay only on error
                        hideFormLoading(formContainer);
                        submitButton.prop('disabled', false).val(originalText);

                        // Show error message prominently
                        var errorMsg = response.data && response.data.message ? response.data.message : mtTicketBusAdmin.i18n.errorOccurredSavingRoute;
                        Swal.fire({
                            icon: 'error',
                            title: errorMsg,
                            confirmButtonText: mtTicketBusAdmin.i18n.ok
                        });
                    }
                },
                error: function () {
                    // Hide loading overlay on error
                    hideFormLoading(formContainer);
                    submitButton.prop('disabled', false).val(originalText);
                    Swal.fire({
                        icon: 'error',
                        title: mtTicketBusAdmin.i18n.errorOccurred,
                        confirmButtonText: mtTicketBusAdmin.i18n.ok
                    });
                }
            });
        });

        // Handle bus deletion
        $('.mt-delete-bus').on('click', function (e) {
            e.preventDefault();

            var $deleteLink = $(this);
            var busId = $deleteLink.data('id');

            Swal.fire({
                icon: 'warning',
                title: mtTicketBusAdmin.i18n.confirmDeleteBus,
                showCancelButton: true,
                confirmButtonText: mtTicketBusAdmin.i18n.yes,
                cancelButtonText: mtTicketBusAdmin.i18n.cancel,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6'
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                // Show loading overlay before AJAX request
                showPageLoading();

                $.ajax({
                    url: mtTicketBusAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'mt_delete_bus',
                        id: busId,
                        nonce: mtTicketBusAdmin.nonce
                    },
                    success: function (response) {
                        if (response.success) {
                            // Overlay will remain visible during reload
                            location.reload();
                        } else {
                            // Hide overlay on error
                            $('.mt-page-loading-overlay').remove();
                            Swal.fire({
                                icon: 'error',
                                title: response.data.message,
                                confirmButtonText: mtTicketBusAdmin.i18n.ok
                            });
                        }
                    },
                    error: function () {
                        // Hide overlay on error
                        $('.mt-page-loading-overlay').remove();
                        Swal.fire({
                            icon: 'error',
                            title: mtTicketBusAdmin.i18n.errorOccurred,
                            confirmButtonText: mtTicketBusAdmin.i18n.ok
                        });
                    }
                });
            });
        });

        // Handle route deletion
        $('.mt-delete-route').on('click', function (e) {
            e.preventDefault();

            var $deleteLink = $(this);
            var routeId = $deleteLink.data('id');

            Swal.fire({
                icon: 'warning',
                title: mtTicketBusAdmin.i18n.confirmDeleteRoute,
                showCancelButton: true,
                confirmButtonText: mtTicketBusAdmin.i18n.yes,
                cancelButtonText: mtTicketBusAdmin.i18n.cancel,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6'
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                // Show loading overlay before AJAX request
                showPageLoading();

                $.ajax({
                    url: mtTicketBusAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'mt_delete_route',
                        id: routeId,
                        nonce: mtTicketBusAdmin.nonce
                    },
                    success: function (response) {
                        if (response.success) {
                            // Overlay will remain visible during reload
                            location.reload();
                        } else {
                            // Hide overlay on error
                            $('.mt-page-loading-overlay').remove();
                            Swal.fire({
                                icon: 'error',
                                title: response.data.message,
                                confirmButtonText: mtTicketBusAdmin.i18n.ok
                            });
                        }
                    },
                    error: function () {
                        // Hide overlay on error
                        $('.mt-page-loading-overlay').remove();
                        Swal.fire({
                            icon: 'error',
                            title: mtTicketBusAdmin.i18n.errorOccurred,
                            confirmButtonText: mtTicketBusAdmin.i18n.ok
                        });
                    }
                });
            });
        });
    });

    // Reservations page functionality
    if ($('.mt-ticket-bus-reservations').length) {
        var $routeSelect = $('#route_id');
        var $scheduleSelect = $('#schedule_id');
        var $courseSelect = $('#departure_time');

        // Load schedules when route changes
        $routeSelect.on('change', function () {
            var routeId = $(this).val();
            $scheduleSelect.prop('disabled', true).html('<option value="">' + (mtTicketBusAdmin.i18n.loading || 'Loading...') + '</option>');
            $courseSelect.prop('disabled', true).html('<option value="">' + (mtTicketBusAdmin.i18n.selectCourse || '-- Select Course --') + '</option>');

            if (!routeId) {
                $scheduleSelect.prop('disabled', true).html('<option value="">' + (mtTicketBusAdmin.i18n.selectSchedule || '-- Select Schedule --') + '</option>');
                return;
            }

            $.ajax({
                url: mtTicketBusAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mt_get_schedules_by_route',
                    route_id: routeId,
                    nonce: mtTicketBusAdmin.nonce
                },
                success: function (response) {
                    if (response.success && response.data && response.data.schedules) {
                        var options = '<option value="">' + (mtTicketBusAdmin.i18n.selectSchedule || '-- Select Schedule --') + '</option>';
                        $.each(response.data.schedules, function (id, label) {
                            options += '<option value="' + id + '">' + label + '</option>';
                        });
                        $scheduleSelect.html(options).prop('disabled', false);
                    } else {
                        $scheduleSelect.html('<option value="">' + (mtTicketBusAdmin.i18n.noSchedulesFound || 'No schedules found.') + '</option>').prop('disabled', true);
                    }
                },
                error: function () {
                    $scheduleSelect.html('<option value="">' + (mtTicketBusAdmin.i18n.errorLoadingSchedules || 'Error loading schedules.') + '</option>').prop('disabled', true);
                }
            });
        });

        // Load courses when schedule changes
        $scheduleSelect.on('change', function () {
            var scheduleId = $(this).val();
            $courseSelect.prop('disabled', true).html('<option value="">' + (mtTicketBusAdmin.i18n.loading || 'Loading...') + '</option>');

            if (!scheduleId) {
                $courseSelect.prop('disabled', true).html('<option value="">' + (mtTicketBusAdmin.i18n.selectCourse || '-- Select Course --') + '</option>');
                return;
            }

            $.ajax({
                url: mtTicketBusAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mt_get_courses_by_schedule',
                    schedule_id: scheduleId,
                    nonce: mtTicketBusAdmin.nonce
                },
                success: function (response) {
                    if (response.success && response.data && response.data.courses) {
                        var options = '<option value="">' + (mtTicketBusAdmin.i18n.selectCourse || '-- Select Course --') + '</option>';
                        $.each(response.data.courses, function (index, course) {
                            options += '<option value="' + course.value + '">' + course.label + '</option>';
                        });
                        $courseSelect.html(options).prop('disabled', false);
                    } else {
                        $courseSelect.html('<option value="">' + (mtTicketBusAdmin.i18n.noCoursesFound || 'No courses found.') + '</option>').prop('disabled', true);
                    }
                },
                error: function () {
                    $courseSelect.html('<option value="">' + (mtTicketBusAdmin.i18n.errorLoadingCourses || 'Error loading courses.') + '</option>').prop('disabled', true);
                }
            });
        });

        // Render seat map for reservations page
        function renderReservationsSeatMap() {
            var $layoutContainer = $('#mt-reservations-seat-layout');
            if (!$layoutContainer.length) {
                return;
            }

            var seatLayoutJson = $layoutContainer.data('seat-layout');
            var reservedSeatsJson = $layoutContainer.data('reserved-seats');

            if (!seatLayoutJson) {
                $layoutContainer.html('<div class="mt-seat-layout-error">' + (mtTicketBusAdmin.i18n.noSeatLayoutData || 'No seat layout data available.') + '</div>');
                return;
            }

            var layoutData = typeof seatLayoutJson === 'string' ? JSON.parse(seatLayoutJson) : seatLayoutJson;
            var reservedSeats = typeof reservedSeatsJson === 'string' ? JSON.parse(reservedSeatsJson) : (reservedSeatsJson || []);

            if (!layoutData || !layoutData.config || !layoutData.seats) {
                $layoutContainer.html('<div class="mt-seat-layout-error">' + (mtTicketBusAdmin.i18n.invalidSeatLayout || 'Invalid seat layout.') + '</div>');
                return;
            }

            var config = layoutData.config;
            var seats = layoutData.seats;
            var leftSeats = config.left || 0;
            var rightSeats = config.right || 0;
            var rows = config.rows || 10;

            // Create seat map HTML
            var html = '<div class="mt-seat-map-wrapper">';
            html += '<div class="mt-seat-map-legend">';
            html += '<span class="mt-legend-item"><span class="mt-legend-seat mt-seat-available"></span> Available</span>';
            html += '<span class="mt-legend-item"><span class="mt-legend-seat mt-seat-reserved"></span> Reserved</span>';
            html += '<span class="mt-legend-item"><span class="mt-legend-seat mt-seat-disabled"></span> Disabled</span>';
            html += '</div>';

            html += '<div class="mt-seat-map">';
            html += '<div class="mt-seat-map-aisle-left"></div>';

            // Render seats
            for (var row = 1; row <= rows; row++) {
                html += '<div class="mt-seat-row">';
                html += '<span class="mt-seat-row-number">' + row + '</span>';

                // Left column seats
                for (var col = 0; col < leftSeats; col++) {
                    var colLetter = String.fromCharCode(65 + col);
                    var seatId = colLetter + row;
                    var isSeatEnabled = seats[seatId] === true || seats[seatId] === 1 || seats[seatId] === '1';
                    var isReserved = reservedSeats.indexOf(seatId) !== -1;
                    var classes = 'mt-seat';

                    if (!isSeatEnabled) {
                        classes += ' mt-seat-disabled';
                    } else if (isReserved) {
                        classes += ' mt-seat-reserved';
                    } else {
                        classes += ' mt-seat-available';
                    }

                    html += '<div class="' + classes + '" data-seat="' + seatId + '">' + seatId + '</div>';
                }

                // Aisle
                html += '<div class="mt-seat-aisle"></div>';

                // Right column seats
                for (var col = 0; col < rightSeats; col++) {
                    var colLetter = String.fromCharCode(65 + leftSeats + col);
                    var seatId = colLetter + row;
                    var isSeatEnabled = seats[seatId] === true || seats[seatId] === 1 || seats[seatId] === '1';
                    var isReserved = reservedSeats.indexOf(seatId) !== -1;
                    var classes = 'mt-seat';

                    if (!isSeatEnabled) {
                        classes += ' mt-seat-disabled';
                    } else if (isReserved) {
                        classes += ' mt-seat-reserved';
                    } else {
                        classes += ' mt-seat-available';
                    }

                    html += '<div class="' + classes + '" data-seat="' + seatId + '">' + seatId + '</div>';
                }

                html += '</div>';
            }

            html += '<div class="mt-seat-map-aisle-right"></div>';
            html += '</div>';
            html += '</div>';

            $layoutContainer.html(html);

            // Add click handlers for reserved seats
            var reservationsData = $layoutContainer.data('reservations') || {};
            $layoutContainer.find('.mt-seat-reserved').on('click', function () {
                var seatId = $(this).data('seat');
                if (reservationsData[seatId]) {
                    showReservationDetails(reservationsData[seatId]);
                }
            });

            // Add cursor pointer for reserved seats
            $layoutContainer.find('.mt-seat-reserved').css('cursor', 'pointer');
        }

        // Show reservation details in the right panel
        function showReservationDetails(reservation) {
            var $detailsContainer = $('#mt-reservation-details');
            var html = '<table class="form-table">';

            // Helper function to escape HTML
            function escapeHtml(text) {
                if (!text) return '';
                var map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return String(text).replace(/[&<>"']/g, function (m) { return map[m]; });
            }

            // Order ID
            if (reservation.order_id) {
                var orderId = escapeHtml(reservation.order_id);
                var orderEditUrl = (mtTicketBusAdmin.adminUrl || '') + '?post=' + orderId + '&action=edit';
                html += '<tr>';
                html += '<th scope="row">' + (mtTicketBusAdmin.i18n.orderId || 'Order ID') + '</th>';
                html += '<td><strong><a href="' + escapeHtml(orderEditUrl) + '" target="_blank">#' + orderId + '</a></strong></td>';
                html += '</tr>';
            }

            // Seat Number
            if (reservation.seat_number) {
                html += '<tr>';
                html += '<th scope="row">' + (mtTicketBusAdmin.i18n.seatNumber || 'Seat Number') + '</th>';
                html += '<td><strong>' + escapeHtml(reservation.seat_number) + '</strong></td>';
                html += '</tr>';
            }

            // Passenger Name
            if (reservation.passenger_name) {
                html += '<tr>';
                html += '<th scope="row">' + (mtTicketBusAdmin.i18n.passengerName || 'Passenger Name') + '</th>';
                html += '<td>' + escapeHtml(reservation.passenger_name) + '</td>';
                html += '</tr>';
            }

            // Passenger Email
            if (reservation.passenger_email) {
                var email = escapeHtml(reservation.passenger_email);
                html += '<tr>';
                html += '<th scope="row">' + (mtTicketBusAdmin.i18n.passengerEmail || 'Passenger Email') + '</th>';
                html += '<td><a href="mailto:' + email + '">' + email + '</a></td>';
                html += '</tr>';
            }

            // Passenger Phone
            if (reservation.passenger_phone) {
                var phone = escapeHtml(reservation.passenger_phone);
                html += '<tr>';
                html += '<th scope="row">' + (mtTicketBusAdmin.i18n.passengerPhone || 'Passenger Phone') + '</th>';
                html += '<td><a href="tel:' + phone + '">' + phone + '</a></td>';
                html += '</tr>';
            }

            // Departure Date
            if (reservation.departure_date) {
                html += '<tr>';
                html += '<th scope="row">' + (mtTicketBusAdmin.i18n.departureDate || 'Departure Date') + '</th>';
                html += '<td>' + escapeHtml(reservation.departure_date) + '</td>';
                html += '</tr>';
            }

            // Departure Time
            if (reservation.departure_time) {
                var timeDisplay = String(reservation.departure_time);
                if (timeDisplay.length > 5) {
                    timeDisplay = timeDisplay.substring(0, 5);
                }
                html += '<tr>';
                html += '<th scope="row">' + (mtTicketBusAdmin.i18n.departureTime || 'Departure Time') + '</th>';
                html += '<td>' + escapeHtml(timeDisplay) + '</td>';
                html += '</tr>';
            }

            // Status
            if (reservation.status) {
                var statusClass = 'reserved';
                var statusLabel = String(reservation.status).charAt(0).toUpperCase() + String(reservation.status).slice(1);
                if (reservation.status === 'confirmed') {
                    statusClass = 'confirmed';
                } else if (reservation.status === 'cancelled') {
                    statusClass = 'cancelled';
                }
                html += '<tr>';
                html += '<th scope="row">' + (mtTicketBusAdmin.i18n.status || 'Status') + '</th>';
                html += '<td><span class="mt-reservation-status mt-status-' + escapeHtml(statusClass) + '">' + escapeHtml(statusLabel) + '</span></td>';
                html += '</tr>';
            }

            html += '</table>';

            $detailsContainer.html(html);
        }

        // Render seat map on page load if data is available
        if ($('#mt-reservations-seat-layout').data('seat-layout')) {
            renderReservationsSeatMap();
        }
    }

})(jQuery);
