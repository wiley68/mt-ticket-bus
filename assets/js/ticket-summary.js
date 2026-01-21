/**
 * Ticket summary functionality
 *
 * @package MT_Ticket_Bus
 */

(function ($) {
    'use strict';

    var TicketSummaryManager = {
        productId: null,
        selectedTickets: [], // Array of ticket objects: {date, time, seat}

        init: function () {
            var $summaryBlock = $('.mt-ticket-summary-block');
            if (!$summaryBlock.length) {
                return;
            }

            // Get product ID
            this.productId = $summaryBlock.data('product-id');
            if (!this.productId) {
                return;
            }

            // Listen for seat selection updates
            $(document).on('mt_seats_updated', this.handleSeatsUpdated.bind(this));

            // Handle Add to Cart button
            $summaryBlock.on('click', '.mt-btn-add-to-cart', this.handleAddToCart.bind(this));

            // Handle Buy Now button
            $summaryBlock.on('click', '.mt-btn-buy-now', this.handleBuyNow.bind(this));
        },

        handleSeatsUpdated: function (e, data) {
            if (!data.seats || data.seats.length === 0) {
                // Hide selected seats summary
                $('.mt-selected-seats-summary').hide();
                $('.mt-product-actions button').prop('disabled', true);
                this.selectedTickets = [];
                return;
            }

            // Build tickets array
            this.selectedTickets = [];
            var self = this;

            data.seats.forEach(function (seat) {
                self.selectedTickets.push({
                    date: data.date,
                    time: data.time,
                    seat: seat,
                });
            });

            // Update UI
            this.updateSelectedSeatsDisplay();
            $('.mt-product-actions button').prop('disabled', false);
        },

        updateSelectedSeatsDisplay: function () {
            var $summary = $('.mt-selected-seats-summary');
            var $list = $summary.find('.mt-selected-seats-list');

            if (this.selectedTickets.length === 0) {
                $summary.hide();
                return;
            }

            $list.empty();
            var self = this;

            this.selectedTickets.forEach(function (ticket) {
                var dateObj = new Date(ticket.date);
                var dateFormatted = dateObj.toLocaleDateString('bg-BG', {
                    weekday: 'short',
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                });
                var timeFormatted = ticket.time.substring(0, 5);

                var $item = $('<li class="mt-selected-seat-item"></li>');
                $item.html(
                    '<span class="mt-seat-info">' +
                    '<strong>' + ticket.seat + '</strong> - ' +
                    dateFormatted + ' ' + timeFormatted +
                    '</span>'
                );
                $list.append($item);
            });

            $summary.show();
        },

        handleAddToCart: function (e) {
            e.preventDefault();

            if (this.selectedTickets.length === 0) {
                alert(mtTicketBus.i18n.selectSeat || 'Please select at least one seat.');
                return;
            }

            this.addTicketsToCart(false);
        },

        handleBuyNow: function (e) {
            e.preventDefault();

            if (this.selectedTickets.length === 0) {
                alert(mtTicketBus.i18n.selectSeat || 'Please select at least one seat.');
                return;
            }

            this.addTicketsToCart(true);
        },

        addTicketsToCart: function (buyNow) {
            var self = this;
            var $button = buyNow ? $('.mt-btn-buy-now') : $('.mt-btn-add-to-cart');
            var originalText = $button.text();

            // Disable buttons and show loading
            $('.mt-product-actions button').prop('disabled', true);
            $button.text('Обработка...');

            $.ajax({
                url: mtTicketBus.ajaxurl || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'mt_add_tickets_to_cart',
                    nonce: mtTicketBus.nonce,
                    product_id: this.productId,
                    tickets: this.selectedTickets,
                    buy_now: buyNow ? 'true' : 'false',
                },
                success: function (response) {
                    if (response.success) {
                        // Update cart fragments if provided
                        if (response.data.fragments) {
                            $.each(response.data.fragments, function (key, value) {
                                $(key).replaceWith(value);
                            });
                        }

                        // Update cart hash
                        if (response.data.cart_hash) {
                            $('body').trigger('wc_fragment_refresh');
                        }

                        // Show success message
                        if (typeof wc_add_to_cart_params !== 'undefined') {
                            // Use WooCommerce's notice system if available
                            $(document.body).trigger('added_to_cart', [
                                response.data.fragments,
                                response.data.cart_hash,
                                $button,
                            ]);
                        } else {
                            // Fallback: simple alert
                            alert(response.data.message);
                        }

                        // Redirect if Buy Now
                        if (buyNow && response.data.redirect) {
                            window.location.href = response.data.redirect;
                        } else if (response.data.cart_url && !buyNow) {
                            // Optionally redirect to cart after adding
                            // window.location.href = response.data.cart_url;
                        }

                        // Reset selected tickets
                        self.selectedTickets = [];
                        $('.mt-selected-seats-summary').hide();
                        } else {
                            alert(response.data.message || (mtTicketBus.i18n.addToCartError || 'Error adding to cart.'));
                            $('.mt-product-actions button').prop('disabled', false);
                            $button.text(originalText);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX error:', error);
                        alert(mtTicketBus.i18n.addToCartErrorRetry || 'Error adding to cart. Please try again.');
                        $('.mt-product-actions button').prop('disabled', false);
                        $button.text(originalText);
                    },
            });
        },
    };

    // Initialize on document ready
    $(document).ready(function () {
        TicketSummaryManager.init();
    });

})(jQuery);
