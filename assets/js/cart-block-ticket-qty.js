/**
 * WooCommerce Blocks cart: mark ticket product lines and hide quantity selector.
 *
 * Uses cartItemClass filter to add class mt-ticket-cart-item for ticket products,
 * so CSS can hide the quantity input (one seat = quantity always 1).
 *
 * @package MT_Ticket_Bus
 */
(function () {
    var ticketProductIds = (typeof mtTicketCartBlock !== 'undefined' && Array.isArray(mtTicketCartBlock.ticketProductIds))
        ? mtTicketCartBlock.ticketProductIds
        : [];

    function registerFilters() {
        if (typeof window.wc === 'undefined' || typeof window.wc.blocksCheckout === 'undefined') {
            return false;
        }
        var registerCheckoutFilters = window.wc.blocksCheckout.registerCheckoutFilters;
        if (typeof registerCheckoutFilters !== 'function') {
            return false;
        }
        registerCheckoutFilters('mt_ticket_bus', {
            cartItemClass: function (defaultValue, extensions, args) {
                var context = (args && args.context) ? args.context : '';
                if (context !== 'cart' && context !== 'summary') {
                    return defaultValue;
                }
                var cartItem = (args && args.cartItem) ? args.cartItem : null;
                if (!cartItem || typeof cartItem.id === 'undefined') {
                    return defaultValue;
                }
                var id = parseInt(cartItem.id, 10);
                if (ticketProductIds.indexOf(id) === -1) {
                    return defaultValue;
                }
                var base = defaultValue ? defaultValue + ' ' : '';
                return base + 'mt-ticket-cart-item';
            },
        });
        return true;
    }

    function init() {
        if (registerFilters()) {
            return;
        }
        var attempts = 0;
        var maxAttempts = 50;
        var t = setInterval(function () {
            attempts += 1;
            if (registerFilters() || attempts >= maxAttempts) {
                clearInterval(t);
            }
        }, 100);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
