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

        // Handle frequency type change for schedules
        $('#frequency_type').on('change', function () {
            if ($(this).val() === 'multiple') {
                $('#days_of_week_row').show();
            } else {
                $('#days_of_week_row').hide();
            }
        });

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
        if ($('#frequency_type').val() === 'multiple') {
            $('#days_of_week_row').show();
        }
        if ($('input[name="days_of_week_type"]:checked').val() === 'custom') {
            $('#custom_days').show();
        } else {
            $('#custom_days').hide();
        }

        // Handle schedule form submission
        $('#mt-schedule-form').on('submit', function (e) {
            e.preventDefault();

            var formData = $(this).serialize();
            formData += '&nonce=' + mtTicketBusAdmin.nonce;

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

            // Remove individual days_of_week[] from formData and add processed value
            formData = formData.replace(/&days_of_week\[\]=[^&]*/g, '');
            if (daysValue) {
                formData += '&days_of_week=' + encodeURIComponent(daysValue);
            }

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

        // Handle route form submission
        $('#mt-route-form').on('submit', function (e) {
            e.preventDefault();

            var formData = $(this).serialize();
            formData += '&nonce=' + mtTicketBusAdmin.nonce;

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
                data: formData + '&action=mt_save_route',
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

})(jQuery);
