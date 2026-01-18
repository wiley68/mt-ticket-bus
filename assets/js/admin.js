/**
 * Admin JavaScript for MT Ticket Bus plugin
 *
 * @package MT_Ticket_Bus
 */

(function ($) {
    'use strict';

    $(document).ready(function () {

        // Handle bus form submission
        $('#mt-bus-form').on('submit', function (e) {
            e.preventDefault();

            var formData = $(this).serialize();
            formData += '&nonce=' + mtTicketBusAdmin.nonce;

            $.ajax({
                url: mtTicketBusAdmin.ajaxUrl,
                type: 'POST',
                data: formData + '&action=mt_save_bus',
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
