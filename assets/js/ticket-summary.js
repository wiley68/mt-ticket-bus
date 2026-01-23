/**
 * Ticket summary functionality
 *
 * @package MT_Ticket_Bus
 */

(function ($) {
  "use strict";

  var TicketSummaryManager = {
    productId: null,
    selectedTickets: [], // Array of ticket objects: {date, time, seat}
    $summaryBlock: null,
    // Sticky (JS-managed) state
    stickyOffset: 20,
    isSticky: false,
    originalTop: 0,
    $stickyPlaceholder: null,
    _stickyTicking: false,
    _resizeObserver: null,

    init: function () {
      this.$summaryBlock = $(".mt-ticket-summary-block");
      if (!this.$summaryBlock.length) {
        return;
      }

      // Get product ID
      this.productId = this.$summaryBlock.data("product-id");
      if (!this.productId) {
        return;
      }

      // Make ticket summary follow viewport on scroll (JS sticky)
      this.initSticky();

      // Listen for seat selection updates
      $(document).on("mt_seats_updated", this.handleSeatsUpdated.bind(this));

      // Handle Add to Cart button
      this.$summaryBlock.on(
        "click",
        ".mt-btn-add-to-cart",
        this.handleAddToCart.bind(this),
      );

      // Handle Buy Now button
      this.$summaryBlock.on(
        "click",
        ".mt-btn-buy-now",
        this.handleBuyNow.bind(this),
      );

      // Handle seat removal button
      this.$summaryBlock.on(
        "click",
        ".mt-remove-seat-btn",
        this.handleRemoveSeat.bind(this),
      );
    },

    initSticky: function () {
      if (!this.$summaryBlock || !this.$summaryBlock.length) {
        return;
      }

      // Create placeholder to preserve layout when block becomes fixed
      if (!this.$stickyPlaceholder || !this.$stickyPlaceholder.length) {
        this.$stickyPlaceholder = $(
          '<div class="mt-ticket-summary-sticky-placeholder" style="display:none;"></div>',
        );
        this.$summaryBlock.before(this.$stickyPlaceholder);
      }

      // Initial top position (in document flow)
      this.originalTop = this.$summaryBlock.offset().top;

      // Bind handlers
      var self = this;
      $(window).on("scroll", function () {
        self.requestStickySync();
      });
      $(window).on("resize", function () {
        self.requestStickySync(true);
      });

      // Seatmap changes the page height/scrollbar/layout — resync sticky geometry
      $(document).on(
        "mt_seatmap_container_shown mt_seatmap_rendered",
        function () {
          self.requestStickySync(true);
        },
      );

      // ResizeObserver for robust geometry updates when layout changes (e.g. scrollbar appears)
      if (typeof ResizeObserver !== "undefined") {
        this._resizeObserver = new ResizeObserver(function () {
          self.requestStickySync(true);
        });

        // Observe WooCommerce product wrapper if present
        var productEl = document.querySelector(".woocommerce div.product");
        if (productEl) {
          this._resizeObserver.observe(productEl);
        }

        // Also observe the summary column parent if present
        var summaryCol = this.$summaryBlock.closest(
          ".summary.entry-summary, .summary",
        )[0];
        if (summaryCol) {
          this._resizeObserver.observe(summaryCol);
        }
      }

      // First sync
      this.requestStickySync(true);
    },

    requestStickySync: function (force) {
      var self = this;
      if (this._stickyTicking && !force) {
        return;
      }
      this._stickyTicking = true;
      window.requestAnimationFrame(function () {
        self._stickyTicking = false;
        self.syncSticky(!!force);
      });
    },

    syncSticky: function (force) {
      if (!this.$summaryBlock || !this.$summaryBlock.length) {
        return;
      }

      // Disable sticky on narrow screens (avoid covering content on mobile)
      if (
        window.matchMedia &&
        window.matchMedia("(max-width: 991px)").matches
      ) {
        if (this.isSticky) {
          this.removeSticky();
        }
        // Keep originalTop updated for when switching back to desktop size
        this.originalTop = this.$summaryBlock.offset().top;
        return;
      }

      // If not sticky, keep originalTop updated (layout can shift as seatmap expands)
      if (!this.isSticky) {
        this.originalTop = this.$summaryBlock.offset().top;
      }

      var scrollTop = $(window).scrollTop();
      var shouldStick = scrollTop + this.stickyOffset >= this.originalTop;

      if (shouldStick) {
        if (!this.isSticky) {
          this.applySticky();
        } else {
          // Geometry can change after seatmap expands / scrollbar appears
          this.updateStickyGeometry();
        }
      } else if (this.isSticky) {
        this.removeSticky();
      }

      // If forced, also update geometry even if we just applied sticky
      if (force && this.isSticky) {
        this.updateStickyGeometry();
      }
    },

    applySticky: function () {
      if (!this.$stickyPlaceholder || !this.$stickyPlaceholder.length) {
        return;
      }

      // Preserve layout space
      var height = this.$summaryBlock.outerHeight(true);
      this.$stickyPlaceholder.height(height).show();

      this.isSticky = true;
      this.updateStickyGeometry();
    },

    updateStickyGeometry: function () {
      if (
        !this.isSticky ||
        !this.$stickyPlaceholder ||
        !this.$stickyPlaceholder.length
      ) {
        return;
      }

      var placeholderEl = this.$stickyPlaceholder[0];
      var rect = placeholderEl.getBoundingClientRect(); // viewport coords

      // Constrain height within viewport (keep content scrollable inside summary)
      var maxH = "calc(100vh - " + this.stickyOffset * 2 + "px)";

      this.$summaryBlock.css({
        position: "fixed",
        top: this.stickyOffset + "px",
        left: rect.left + "px",
        width: rect.width + "px",
        zIndex: 50,
        maxHeight: maxH,
        overflowY: "auto",
      });
    },

    removeSticky: function () {
      this.isSticky = false;

      if (this.$stickyPlaceholder && this.$stickyPlaceholder.length) {
        this.$stickyPlaceholder.hide().height(0);
      }

      this.$summaryBlock.css({
        position: "",
        top: "",
        left: "",
        width: "",
        zIndex: "",
        maxHeight: "",
        overflowY: "",
      });

      // Recalculate original position after returning to flow
      this.originalTop = this.$summaryBlock.offset().top;
    },

    handleSeatsUpdated: function (e, data) {
      if (!data.seats || data.seats.length === 0) {
        // Hide selected seats summary
        var $summary = $(".mt-selected-seats-summary");
        var $list = $summary.find(".mt-selected-seats-list");
        $list.empty(); // Clear the list
        $summary.hide();
        $(".mt-product-actions button").prop("disabled", true);
        this.selectedTickets = [];
        this.updatePrice(); // Reset price to base price
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
      this.updatePrice();
      $(".mt-product-actions button").prop("disabled", false);
    },

    updateSelectedSeatsDisplay: function () {
      var $summary = $(".mt-selected-seats-summary");
      var $list = $summary.find(".mt-selected-seats-list");

      if (this.selectedTickets.length === 0) {
        $summary.hide();
        return;
      }

      $list.empty();
      var self = this;

      // Get unit price from the price element
      var $priceElement = $(".mt-product-price");
      var basePrice = parseFloat($priceElement.data("base-price") || 0);
      var originalPriceHtml = $priceElement.data("original-price-html");
      var unitPriceFormatted = "";

      if (originalPriceHtml) {
        // Use the original WooCommerce HTML for unit price display
        unitPriceFormatted = originalPriceHtml;
      } else if (basePrice > 0) {
        // Fallback: format price using formatPrice function
        unitPriceFormatted = this.formatPrice(basePrice);
      }

      this.selectedTickets.forEach(function (ticket, index) {
        var dateObj = new Date(ticket.date);
        var dateFormatted = dateObj.toLocaleDateString("bg-BG", {
          weekday: "short",
          year: "numeric",
          month: "short",
          day: "numeric",
        });
        var timeFormatted = ticket.time.substring(0, 5);

        var $item = $('<li class="mt-selected-seat-item"></li>');
        var removeSeatText = mtTicketBus.i18n.removeSeat || "Remove seat";
        $item.html(
          '<span class="mt-seat-info">' +
            "<strong>" +
            ticket.seat +
            "</strong> - " +
            dateFormatted +
            " " +
            timeFormatted +
            (unitPriceFormatted ? " - " + unitPriceFormatted : "") +
            "</span>" +
            '<button type="button" class="mt-remove-seat-btn" ' +
            'data-seat="' +
            ticket.seat +
            '" ' +
            'data-date="' +
            ticket.date +
            '" ' +
            'data-time="' +
            ticket.time +
            '" ' +
            'data-index="' +
            index +
            '" ' +
            'title="' +
            removeSeatText +
            '" ' +
            'aria-label="' +
            removeSeatText +
            '">' +
            "×" +
            "</button>",
        );
        $list.append($item);
      });

      $summary.show();
    },

    formatPriceNumber: function (price) {
      // Format only the numeric part of price (without currency symbol)
      // This is used to replace the number in existing WooCommerce HTML
      var priceFormat =
        typeof mtTicketBus !== "undefined" && mtTicketBus.priceFormat
          ? mtTicketBus.priceFormat
          : {
              decimalSeparator: ".",
              thousandSeparator: "",
              decimals: 2,
            };

      // Format number with decimals
      var formatted = parseFloat(price).toFixed(priceFormat.decimals);

      // Add thousand separators only if thousandSeparator is not empty
      // Empty string means no thousand separator (WooCommerce setting)
      if (
        priceFormat.thousandSeparator &&
        priceFormat.thousandSeparator.length > 0
      ) {
        var parts = formatted.split(".");
        parts[0] = parts[0].replace(
          /\B(?=(\d{3})+(?!\d))/g,
          priceFormat.thousandSeparator,
        );
        formatted = parts.join(priceFormat.decimalSeparator);
      } else {
        // No thousand separator - just replace decimal separator
        formatted = formatted.replace(".", priceFormat.decimalSeparator);
      }

      return formatted;
    },

    formatPrice: function (price) {
      // Format price according to WooCommerce settings (with currency symbol)
      // This is a fallback if original HTML is not available
      var priceFormat =
        typeof mtTicketBus !== "undefined" && mtTicketBus.priceFormat
          ? mtTicketBus.priceFormat
          : {
              currencySymbol: "",
              currencyPosition: "left",
              decimalSeparator: ".",
              thousandSeparator: ",",
              decimals: 2,
            };

      // Format number with decimals
      var formatted = this.formatPriceNumber(price);

      // Add currency symbol based on position
      var currencySymbol = priceFormat.currencySymbol || "";
      var position = priceFormat.currencyPosition || "left";

      switch (position) {
        case "left":
          return currencySymbol + formatted;
        case "right":
          return formatted + currencySymbol;
        case "left_space":
          return currencySymbol + " " + formatted;
        case "right_space":
          return formatted + " " + currencySymbol;
        default:
          return currencySymbol + formatted;
      }
    },

    updatePrice: function () {
      var $priceElement = $(".mt-product-price");
      if (!$priceElement.length) {
        return;
      }

      // Get base price from data attribute
      var basePrice = parseFloat($priceElement.data("base-price") || 0);
      if (isNaN(basePrice) || basePrice <= 0) {
        return;
      }

      // Calculate total price based on number of selected tickets
      var ticketCount = this.selectedTickets.length;

      // If no tickets selected, show original price HTML exactly as WooCommerce rendered it
      var originalPriceHtml = $priceElement.data("original-price-html");
      if (ticketCount === 0) {
        if (originalPriceHtml) {
          $priceElement.html(originalPriceHtml);
        }
        return;
      }

      // Calculate total numeric price
      var totalPrice = basePrice * ticketCount;

      // Format only the numeric part (without currency symbol)
      // We'll replace it in the original HTML to preserve WooCommerce structure
      var formattedNumber = this.formatPriceNumber(totalPrice);

      // If we have original WooCommerce HTML, replace only the numeric part
      if (originalPriceHtml) {
        // Replace first occurrence of a numeric pattern (digits, dots, commas)
        // This preserves the WooCommerce HTML structure (spans, bdi, etc.)
        var newHtml = String(originalPriceHtml).replace(
          /[0-9.,]+/,
          formattedNumber,
        );
        $priceElement.html(newHtml);
      } else {
        // Fallback: format with currency symbol
        var formattedPrice = this.formatPrice(totalPrice);
        $priceElement.html(formattedPrice);
      }
    },

    handleAddToCart: function (e) {
      e.preventDefault();

      if (this.selectedTickets.length === 0) {
        Swal.fire({
          icon: "info",
          title:
            mtTicketBus.i18n.selectSeat || "Please select at least one seat.",
          confirmButtonText: mtTicketBus.i18n.ok || "OK",
        });
        return;
      }

      this.addTicketsToCart(false);
    },

    handleBuyNow: function (e) {
      e.preventDefault();

      if (this.selectedTickets.length === 0) {
        Swal.fire({
          icon: "info",
          title:
            mtTicketBus.i18n.selectSeat || "Please select at least one seat.",
          confirmButtonText: mtTicketBus.i18n.ok || "OK",
        });
        return;
      }

      this.addTicketsToCart(true);
    },

    handleRemoveSeat: function (e) {
      e.preventDefault();
      e.stopPropagation();

      var $button = $(e.currentTarget);
      var seatId = $button.data("seat");
      var date = $button.data("date");
      var time = $button.data("time");
      var index = parseInt($button.data("index"), 10);

      if (!seatId || index < 0) {
        return;
      }

      // Remove ticket from selectedTickets array
      if (index < this.selectedTickets.length) {
        this.selectedTickets.splice(index, 1);
      } else {
        // Fallback: find and remove by seat, date, and time
        var self = this;
        this.selectedTickets = this.selectedTickets.filter(function (ticket) {
          return !(
            ticket.seat === seatId &&
            ticket.date === date &&
            ticket.time === time
          );
        });
      }

      // Deselect seat in seatmap if it matches current date/time
      // Trigger event to deselect seat in seatmap
      $(document).trigger("mt_seat_deselect", {
        seat: seatId,
        date: date,
        time: time,
      });

      // Update display
      this.updateSelectedSeatsDisplay();
      this.updatePrice();

      // If no more tickets, disable buttons
      if (this.selectedTickets.length === 0) {
        $(".mt-product-actions button").prop("disabled", true);
        $(".mt-selected-seats-summary").hide();
      } else {
        // Get schedule/bus/route IDs from seatmap block
        var $seatmapBlock = $(".mt-ticket-seatmap-block");
        var scheduleId = $seatmapBlock.data("schedule-id");
        var busId = $seatmapBlock.data("bus-id");
        var routeId = $seatmapBlock.data("route-id");

        // Trigger seats updated event with remaining seats
        // Group remaining seats by date and time
        var seatsByDateTime = {};
        var self = this;
        this.selectedTickets.forEach(function (ticket) {
          var key = ticket.date + "_" + ticket.time;
          if (!seatsByDateTime[key]) {
            seatsByDateTime[key] = {
              date: ticket.date,
              time: ticket.time,
              seats: [],
            };
          }
          seatsByDateTime[key].seats.push(ticket.seat);
        });

        // Trigger event for each date/time combination
        Object.keys(seatsByDateTime).forEach(function (key) {
          var group = seatsByDateTime[key];
          $(document).trigger("mt_seats_updated", {
            productId: self.productId,
            scheduleId: scheduleId,
            busId: busId,
            routeId: routeId,
            date: group.date,
            time: group.time,
            seats: group.seats,
            seatCount: group.seats.length,
          });
        });
      }
    },

    addTicketsToCart: function (buyNow) {
      var self = this;
      var $button = buyNow ? $(".mt-btn-buy-now") : $(".mt-btn-add-to-cart");
      var originalText = $button.text();

      // Disable buttons and show loading
      $(".mt-product-actions button").prop("disabled", true);
      $button.text("Processing...");

      $.ajax({
        url: mtTicketBus.ajaxurl || "/wp-admin/admin-ajax.php",
        type: "POST",
        data: {
          action: "mt_add_tickets_to_cart",
          nonce: mtTicketBus.nonce,
          product_id: this.productId,
          tickets: this.selectedTickets,
          buy_now: buyNow ? "true" : "false",
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
              $("body").trigger("wc_fragment_refresh");
            }

            // Show success message
            if (typeof wc_add_to_cart_params !== "undefined") {
              // Use WooCommerce's notice system if available
              $(document.body).trigger("added_to_cart", [
                response.data.fragments,
                response.data.cart_hash,
                $button,
              ]);
            } else {
              // Fallback: SweetAlert2
              Swal.fire({
                icon: "success",
                title:
                  response.data.message ||
                  mtTicketBus.i18n.addedToCart ||
                  "Added to cart",
                confirmButtonText: mtTicketBus.i18n.ok || "OK",
              });
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
            $(".mt-selected-seats-summary").hide();
          } else {
            Swal.fire({
              icon: "error",
              title:
                response.data.message ||
                mtTicketBus.i18n.addToCartError ||
                "Error adding to cart.",
              confirmButtonText: mtTicketBus.i18n.ok || "OK",
            });
            $(".mt-product-actions button").prop("disabled", false);
            $button.text(originalText);
          }
        },
        error: function (xhr, status, error) {
          console.error("AJAX error:", error);
          Swal.fire({
            icon: "error",
            title:
              mtTicketBus.i18n.addToCartErrorRetry ||
              "Error adding to cart. Please try again.",
            confirmButtonText: mtTicketBus.i18n.ok || "OK",
          });
          $(".mt-product-actions button").prop("disabled", false);
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
