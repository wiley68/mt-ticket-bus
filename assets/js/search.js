/**
 * Ticket Search Functionality
 *
 * @package MT_Ticket_Bus
 */

(function ($) {
  "use strict";

  var TicketSearch = {
    fromStations: [],
    endStations: [],
    selectedFrom: "",
    selectedTo: "",

    init: function () {
      this.loadStartStations();
      this.setupAutocomplete();
      this.setupDatePickers();
      this.setupFormSubmit();
      this.setupResultsPage();
    },

    loadStartStations: function () {
      var self = this;
      $.ajax({
        url: mtTicketSearch.ajaxUrl,
        type: "POST",
        data: {
          action: "mt_get_start_stations",
          nonce: mtTicketSearch.nonce,
        },
        success: function (response) {
          if (response.success && response.data.stations) {
            self.fromStations = response.data.stations;
            // Populate Select2 with stations
            self.populateSelect2("#mt-search-from", self.fromStations);
          }
        },
        error: function () {
          console.error("Failed to load start stations");
        },
      });
    },

    setupAutocomplete: function () {
      var self = this;

      // Initialize Select2 for "From" field
      $("#mt-search-from").select2({
        placeholder: mtTicketSearch.i18n.selectStation || "Select station",
        allowClear: false,
        minimumInputLength: 0,
        language: {
          noResults: function () {
            return mtTicketSearch.i18n.noResults || "No results found";
          },
          searching: function () {
            return mtTicketSearch.i18n.searching || "Searching...";
          },
        },
      });

      // Populate "From" field with stations
      if (self.fromStations.length > 0) {
        self.populateSelect2("#mt-search-from", self.fromStations);
      }

      // Handle "From" field change
      $("#mt-search-from").on("select2:select", function (e) {
        var station = e.params.data.id;
        self.selectedFrom = station;
        $("#mt-search-from-value").val(station);
        $("#mt-search-to").prop("disabled", false);
        self.loadEndStations(station);
      });

      // Handle "From" field clear
      $("#mt-search-from").on("select2:clear", function () {
        self.selectedFrom = "";
        $("#mt-search-from-value").val("");
        $("#mt-search-to").prop("disabled", true);
        $("#mt-search-to").val(null).trigger("change");
        $("#mt-search-to-value").val("");
        self.endStations = [];
      });

      // Initialize Select2 for "To" field
      $("#mt-search-to").select2({
        placeholder: mtTicketSearch.i18n.selectStation || "Select station",
        allowClear: false,
        minimumInputLength: 0,
        language: {
          noResults: function () {
            return mtTicketSearch.i18n.noResults || "No results found";
          },
          searching: function () {
            return mtTicketSearch.i18n.searching || "Searching...";
          },
        },
      });

      // Handle "To" field change
      $("#mt-search-to").on("select2:select", function (e) {
        var station = e.params.data.id;
        self.selectedTo = station;
        $("#mt-search-to-value").val(station);
      });

      // Handle "To" field clear
      $("#mt-search-to").on("select2:clear", function () {
        self.selectedTo = "";
        $("#mt-search-to-value").val("");
      });
    },

    populateSelect2: function (selector, stations) {
      var $select = $(selector);
      $select.empty();
      $select.append(
        '<option value="">' +
          (mtTicketSearch.i18n.selectStation || "Select station") +
          "</option>",
      );

      stations.forEach(function (station) {
        $select.append(
          '<option value="' + station + '">' + station + "</option>",
        );
      });

      $select.trigger("change");
    },

    loadEndStations: function (startStation) {
      var self = this;
      $.ajax({
        url: mtTicketSearch.ajaxUrl,
        type: "POST",
        data: {
          action: "mt_get_end_stations",
          nonce: mtTicketSearch.nonce,
          start_station: startStation,
        },
        success: function (response) {
          if (response.success && response.data.stations) {
            self.endStations = response.data.stations;
            // Populate Select2 with end stations
            self.populateSelect2("#mt-search-to", self.endStations);
            $("#mt-search-to").val(null).trigger("change");
            $("#mt-search-to-value").val("");
          }
        },
        error: function () {
          console.error("Failed to load end stations");
        },
      });
    },

    setupDatePickers: function () {
      // Set minimum date to today
      var today = new Date().toISOString().split("T")[0];
      $("#mt-search-date-from").attr("min", today);
      $("#mt-search-date-to").attr("min", today);

      // Auto-fill date_to when date_from changes
      $("#mt-search-date-from").on("change", function () {
        var dateFrom = $(this).val();
        if (dateFrom && !$("#mt-search-date-to").val()) {
          $("#mt-search-date-to").val(dateFrom);
        }
        // Update minimum for date_to
        $("#mt-search-date-to").attr("min", dateFrom);
      });
    },

    setupFormSubmit: function () {
      $("#mt-ticket-search-form").on("submit", function (e) {
        e.preventDefault();

        var from =
          $("#mt-search-from-value").val() || $("#mt-search-from").val();
        var to = $("#mt-search-to-value").val() || $("#mt-search-to").val();
        var dateFrom = $("#mt-search-date-from").val();
        var dateTo = $("#mt-search-date-to").val();

        if (!from || !to || !dateFrom || !dateTo) {
          alert("Please fill in all fields");
          return;
        }

        // Redirect to results page
        var resultsUrl =
          mtTicketSearch.resultsUrl +
          "?from=" +
          encodeURIComponent(from) +
          "&to=" +
          encodeURIComponent(to) +
          "&date_from=" +
          encodeURIComponent(dateFrom) +
          "&date_to=" +
          encodeURIComponent(dateTo);

        window.location.href = resultsUrl;
      });
    },

    setupResultsPage: function () {
      // Initialize seatmap toggles and interactions
      $(".mt-toggle-seatmap-button").on("click", function () {
        var $button = $(this);
        var $container = $button
          .closest(".mt-search-result-item")
          .find(".mt-result-seatmap-container");
        var isActive = $container.hasClass("active");

        if (isActive) {
          $container.removeClass("active").slideUp();
          $button.text("Show Seat Map");
        } else {
          $container.addClass("active").slideDown();
          $button.text("Hide Seat Map");

          // Initialize seatmap if not already initialized
          if (!$container.data("initialized")) {
            TicketSearch.initSeatmap($container);
            $container.data("initialized", true);
          }
        }
      });
    },

    initSeatmap: function ($container) {
      var $resultItem = $container.closest(".mt-search-result-item");
      var productId = $resultItem.data("product-id");
      var scheduleId = $resultItem.data("schedule-id");
      var busId = $resultItem.data("bus-id");
      var routeId = $resultItem.data("route-id");
      var departureDate = $resultItem.data("departure-date");
      var departureTime = $resultItem.data("departure-time");

      if (
        !productId ||
        !scheduleId ||
        !busId ||
        !departureDate ||
        !departureTime
      ) {
        $container.html(
          '<div class="mt-seat-layout-error">' +
            "Invalid seatmap parameters" +
            "</div>",
        );
        return;
      }

      // Show loading
      $container.html(
        '<div class="mt-seat-layout-loading">' +
          "Loading seat map..." +
          "</div>",
      );

      // Load seatmap data
      $.ajax({
        url: mtTicketSearch.ajaxUrl,
        type: "POST",
        data: {
          action: "mt_get_available_seats",
          nonce: mtTicketSearch.seatmapNonce || "",
          schedule_id: scheduleId,
          bus_id: busId,
          date: departureDate,
          departure_time: departureTime,
        },
        success: function (response) {
          if (response.success && response.data.seat_layout) {
            TicketSearch.renderSeatmap(
              $container,
              response.data.seat_layout,
              response.data.available_seats,
              productId,
              scheduleId,
              busId,
              routeId,
              departureDate,
              departureTime,
            );
          } else {
            $container.html(
              '<div class="mt-seat-layout-error">' +
                (response.data?.message || "Failed to load seat map") +
                "</div>",
            );
          }
        },
        error: function () {
          $container.html(
            '<div class="mt-seat-layout-error">' +
              "Error loading seat map" +
              "</div>",
          );
        },
      });
    },

    renderSeatmap: function (
      $container,
      layoutData,
      availableSeats,
      productId,
      scheduleId,
      busId,
      routeId,
      departureDate,
      departureTime,
    ) {
      $container.empty();

      if (!layoutData || !layoutData.config || !layoutData.seats) {
        $container.html(
          '<div class="mt-seat-layout-error">Invalid seat layout</div>',
        );
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
      html +=
        '<span class="mt-legend-item"><span class="mt-legend-seat mt-seat-available"></span> Available</span>';
      html +=
        '<span class="mt-legend-item"><span class="mt-legend-seat mt-seat-reserved"></span> Reserved</span>';
      html +=
        '<span class="mt-legend-item"><span class="mt-legend-seat mt-seat-selected"></span> Selected</span>';
      html +=
        '<span class="mt-legend-item"><span class="mt-legend-seat mt-seat-disabled"></span> Disabled</span>';
      html += "</div>";

      html += '<div class="mt-bus-seat-layout">';
      html += '<div class="mt-seat-map">';
      html += '<div class="mt-seat-map-aisle-left"></div>';

      // Render seats
      for (var row = 1; row <= rows; row++) {
        html += '<div class="mt-seat-row">';
        html += '<div class="mt-seat-row-number">' + row + "</div>";

        // Left seats
        for (var col = 1; col <= leftSeats; col++) {
          var seatId = String.fromCharCode(64 + col) + row;
          var seatClass = "mt-seat";
          if (seats[seatId] === false) {
            seatClass += " mt-seat-disabled";
          } else if (availableSeats.indexOf(seatId) === -1) {
            seatClass += " mt-seat-reserved";
          } else {
            seatClass += " mt-seat-available";
          }
          html +=
            '<button type="button" class="' +
            seatClass +
            '" data-seat-id="' +
            seatId +
            '">' +
            seatId +
            "</button>";
        }

        // Aisle
        html += '<div class="mt-seat-map-aisle"></div>';

        // Right seats
        for (var col = 1; col <= rightSeats; col++) {
          var seatId = String.fromCharCode(64 + leftSeats + col) + row;
          var seatClass = "mt-seat";
          if (seats[seatId] === false) {
            seatClass += " mt-seat-disabled";
          } else if (availableSeats.indexOf(seatId) === -1) {
            seatClass += " mt-seat-reserved";
          } else {
            seatClass += " mt-seat-available";
          }
          html +=
            '<button type="button" class="' +
            seatClass +
            '" data-seat-id="' +
            seatId +
            '">' +
            seatId +
            "</button>";
        }

        html += "</div>";
      }

      html += '<div class="mt-seat-map-aisle-right"></div>';
      html += "</div>";
      html += "</div>";
      html += "</div>";

      $container.html(html);

      // Setup seat selection
      TicketSearch.setupSeatSelection(
        $container,
        productId,
        scheduleId,
        busId,
        routeId,
        departureDate,
        departureTime,
      );
    },

    setupSeatSelection: function (
      $container,
      productId,
      scheduleId,
      busId,
      routeId,
      departureDate,
      departureTime,
    ) {
      var $resultItem = $container.closest(".mt-search-result-item");
      var selectedSeat = null;

      $container.on("click", ".mt-seat.mt-seat-available", function () {
        var $seat = $(this);
        var seatId = $seat.data("seat-id");

        // Deselect previous seat
        $container.find(".mt-seat-selected").removeClass("mt-seat-selected");

        // Select new seat
        $seat.addClass("mt-seat-selected");
        selectedSeat = seatId;

        // Store selected seat in result item
        $resultItem.data("selected-seat", seatId);
        $resultItem.data("selected-date", departureDate);
        $resultItem.data("selected-time", departureTime);

        // Enable buttons
        $resultItem
          .find(".mt-result-button-add-cart, .mt-result-button-buy-now")
          .prop("disabled", false);
      });

      // Setup button handlers
      $resultItem
        .find(".mt-result-button-add-cart")
        .off("click")
        .on("click", function () {
          TicketSearch.addToCart($resultItem, false);
        });

      $resultItem
        .find(".mt-result-button-buy-now")
        .off("click")
        .on("click", function () {
          TicketSearch.addToCart($resultItem, true);
        });
    },

    addToCart: function ($resultItem, buyNow) {
      var productId = $resultItem.data("product-id");
      var selectedSeat = $resultItem.data("selected-seat");
      var selectedDate = $resultItem.data("selected-date");
      var selectedTime = $resultItem.data("selected-time");

      if (!productId || !selectedSeat || !selectedDate || !selectedTime) {
        alert("Please select a seat first");
        return;
      }

      // Disable buttons during request
      $resultItem
        .find(".mt-result-button-add-cart, .mt-result-button-buy-now")
        .prop("disabled", true)
        .text("Processing...");

      // Use AJAX to add to cart
      // Note: jQuery will serialize the array properly
      $.ajax({
        url: mtTicketSearch.ajaxUrl,
        type: "POST",
        data: {
          action: "mt_add_tickets_to_cart",
          nonce: mtTicketSearch.seatmapNonce || mtTicketSearch.nonce,
          product_id: productId,
          "tickets[0][date]": selectedDate,
          "tickets[0][time]": selectedTime,
          "tickets[0][seat]": selectedSeat,
          buy_now: buyNow ? "true" : "false",
        },
        success: function (response) {
          if (response.success) {
            if (buyNow) {
              window.location.href =
                response.data.redirect ||
                response.data.checkout_url ||
                "/checkout/";
            } else {
              // Show success message
              alert(
                response.data.message || "Ticket added to cart successfully!",
              );

              // Update cart fragments if available
              if (
                response.data.fragments &&
                typeof wc_add_to_cart_params !== "undefined"
              ) {
                $(document.body).trigger("added_to_cart", [
                  response.data.fragments,
                  response.data.cart_hash,
                  $resultItem,
                ]);
              }

              // Re-enable buttons but keep seat selected
              $resultItem
                .find(".mt-result-button-add-cart, .mt-result-button-buy-now")
                .prop("disabled", false)
                .text(function () {
                  return $(this).hasClass("mt-result-button-add-cart")
                    ? "Add to Cart"
                    : "Buy Now";
                });
            }
          } else {
            alert(response.data?.message || "Error adding to cart");
            $resultItem
              .find(".mt-result-button-add-cart, .mt-result-button-buy-now")
              .prop("disabled", false)
              .text(function () {
                return $(this).hasClass("mt-result-button-add-cart")
                  ? "Add to Cart"
                  : "Buy Now";
              });
          }
        },
        error: function () {
          alert("Error adding to cart");
          $resultItem
            .find(".mt-result-button-add-cart, .mt-result-button-buy-now")
            .prop("disabled", false)
            .text(function () {
              return $(this).hasClass("mt-result-button-add-cart")
                ? "Add to Cart"
                : "Buy Now";
            });
        },
      });
    },
  };

  $(document).ready(function () {
    TicketSearch.init();
  });
})(jQuery);
