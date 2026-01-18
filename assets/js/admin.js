/**
 * Admin JavaScript for MT Ticket Bus plugin
 *
 * @package MT_Ticket_Bus
 */

(function ($) {
    'use strict';

    $(document).ready(function () {

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
                alert('Registration number is required.');
                $('#registration_number').focus();
                return;
            }

            // Check if there's an error shown
            if ($('#registration_number_error').is(':visible')) {
                alert('Please fix the registration number error before submitting.');
                $('#registration_number').focus();
                return;
            }

            // Ensure seat layout is updated before submission
            updateSeatLayoutJson();

            var formData = $(this).serialize();
            formData += '&nonce=' + mtTicketBusAdmin.nonce;

            // Show loading indicator
            var submitButton = $(this).find('input[type="submit"]');
            var originalText = submitButton.val();
            submitButton.prop('disabled', true).val('Saving...');

            $.ajax({
                url: mtTicketBusAdmin.ajaxUrl,
                type: 'POST',
                data: formData + '&action=mt_save_bus',
                success: function (response) {
                    submitButton.prop('disabled', false).val(originalText);

                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        // Show error message prominently
                        var errorMsg = response.data && response.data.message ? response.data.message : 'An error occurred while saving the bus.';
                        alert(errorMsg);

                        // If it's a registration number error, highlight the field
                        if (errorMsg.indexOf('registration number') !== -1 || errorMsg.indexOf('Registration number') !== -1) {
                            $('#registration_number').addClass('error').focus();
                            $('#registration_number_error').text(errorMsg).show();
                        }
                    }
                },
                error: function (xhr, status, error) {
                    submitButton.prop('disabled', false).val(originalText);
                    var errorMsg = 'An error occurred. Please try again.';

                    // Try to parse error response
                    try {
                        var errorResponse = JSON.parse(xhr.responseText);
                        if (errorResponse.data && errorResponse.data.message) {
                            errorMsg = errorResponse.data.message;
                        }
                    } catch (e) {
                        // Use default error message
                    }

                    alert(errorMsg);

                    // If it's a registration number error, highlight the field
                    if (errorMsg.indexOf('registration number') !== -1 || errorMsg.indexOf('Registration number') !== -1) {
                        $('#registration_number').addClass('error').focus();
                        $('#registration_number_error').text(errorMsg).show();
                    }
                }
            });
        });

        // Handle route form submission
        $('#mt-route-form').on('submit', function (e) {
            e.preventDefault();

            var formData = $(this).serialize();
            formData += '&nonce=' + mtTicketBusAdmin.nonce;

            $.ajax({
                url: mtTicketBusAdmin.ajaxUrl,
                type: 'POST',
                data: formData + '&action=mt_save_route',
                success: function (response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function () {
                    alert('An error occurred. Please try again.');
                }
            });
        });

        // Handle bus deletion
        $('.mt-delete-bus').on('click', function (e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to delete this bus?')) {
                return;
            }

            var busId = $(this).data('id');

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
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function () {
                    alert('An error occurred. Please try again.');
                }
            });
        });

        // Handle route deletion
        $('.mt-delete-route').on('click', function (e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to delete this route?')) {
                return;
            }

            var routeId = $(this).data('id');

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
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function () {
                    alert('An error occurred. Please try again.');
                }
            });
        });

    });

})(jQuery);
