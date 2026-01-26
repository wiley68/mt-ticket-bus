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
      this.setupGlobalButtonHandlers();
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
          Swal.fire({
            icon: "warning",
            title:
              mtTicketSearch.i18n.fillAllFields || "Please fill in all fields",
            confirmButtonText: mtTicketSearch.i18n.ok || "OK",
          });
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

    setupGlobalButtonHandlers: function () {
      // Setup global button handlers that work even before seatmap is initialized
      $(document).on("click", ".mt-result-button-add-cart", function (e) {
        var $button = $(this);
        var $resultItem = $button.closest(".mt-search-result-item");
        var selectedSeats = $resultItem.data("selected-seats") || [];

        // Check if no seats selected
        if (!selectedSeats || selectedSeats.length === 0) {
          e.preventDefault();
          e.stopPropagation();
          Swal.fire({
            icon: "info",
            title:
              mtTicketSearch.i18n.selectSeatFirst ||
              "Please select a seat from the seat map first.",
            confirmButtonText: mtTicketSearch.i18n.ok || "OK",
          });
          return false;
        }
        TicketSearch.addToCart($resultItem, false);
      });

      $(document).on("click", ".mt-result-button-buy-now", function (e) {
        var $button = $(this);
        var $resultItem = $button.closest(".mt-search-result-item");
        var selectedSeats = $resultItem.data("selected-seats") || [];

        // Check if no seats selected
        if (!selectedSeats || selectedSeats.length === 0) {
          e.preventDefault();
          e.stopPropagation();
          Swal.fire({
            icon: "info",
            title:
              mtTicketSearch.i18n.selectSeatFirst ||
              "Please select a seat from the seat map first.",
            confirmButtonText: mtTicketSearch.i18n.ok || "OK",
          });
          return false;
        }
        TicketSearch.addToCart($resultItem, true);
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
          $button.text(mtTicketSearch.i18n.showSeatMap || "Show Seat Map");
        } else {
          $container.addClass("active").slideDown();
          $button.text(mtTicketSearch.i18n.hideSeatMap || "Hide Seat Map");

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
      var selectedSeats = []; // Array to store multiple selected seats

      // Initialize selected seats array from data attribute if exists
      if ($resultItem.data("selected-seats")) {
        selectedSeats = $resultItem.data("selected-seats").slice();
      }

      // Update visual state of seats
      var updateSeatVisualState = function () {
        $container.find(".mt-seat").each(function () {
          var $seat = $(this);
          var seatId = $seat.data("seat-id");
          if (selectedSeats.indexOf(seatId) > -1) {
            $seat.removeClass("mt-seat-available").addClass("mt-seat-selected");
          } else if ($seat.hasClass("mt-seat-selected")) {
            $seat.removeClass("mt-seat-selected").addClass("mt-seat-available");
          }
        });
      };

      // Initialize visual state
      updateSeatVisualState();

      // Handle seat click (toggle selection)
      $container.on(
        "click",
        ".mt-seat.mt-seat-available, .mt-seat.mt-seat-selected",
        function () {
          var $seat = $(this);
          var seatId = $seat.data("seat-id");

          // Toggle seat selection
          var seatIndex = selectedSeats.indexOf(seatId);
          if (seatIndex > -1) {
            // Deselect seat
            selectedSeats.splice(seatIndex, 1);
            $seat.removeClass("mt-seat-selected").addClass("mt-seat-available");
          } else {
            // Select seat
            selectedSeats.push(seatId);
            $seat.removeClass("mt-seat-available").addClass("mt-seat-selected");
          }

          // Store selected seats array in result item
          $resultItem.data("selected-seats", selectedSeats);
          $resultItem.data("selected-date", departureDate);
          $resultItem.data("selected-time", departureTime);

          // Enable/disable buttons based on selection
          var $buttons = $resultItem.find(
            ".mt-result-button-add-cart, .mt-result-button-buy-now",
          );
          if (selectedSeats.length > 0) {
            $buttons.prop("disabled", false).removeClass("mt-button-disabled");
          } else {
            $buttons.prop("disabled", false).addClass("mt-button-disabled");
          }
        },
      );

      // Button handlers are set up globally in setupGlobalButtonHandlers()
      // No need to set them up here as they work for all buttons
    },

    addToCart: function ($resultItem, buyNow) {
      var productId = $resultItem.data("product-id");
      var selectedSeats = $resultItem.data("selected-seats") || [];
      var selectedDate = $resultItem.data("selected-date");
      var selectedTime = $resultItem.data("selected-time");

      if (
        !productId ||
        !selectedSeats ||
        selectedSeats.length === 0 ||
        !selectedDate ||
        !selectedTime
      ) {
        Swal.fire({
          icon: "info",
          title:
            mtTicketSearch.i18n.selectSeatFirst ||
            "Please select a seat from the seat map first.",
          confirmButtonText: mtTicketSearch.i18n.ok || "OK",
        });
        return;
      }

      // Disable buttons during request
      $resultItem
        .find(".mt-result-button-add-cart, .mt-result-button-buy-now")
        .addClass("mt-button-disabled")
        .text("Processing...");

      // Build tickets array
      var tickets = [];
      for (var i = 0; i < selectedSeats.length; i++) {
        tickets.push({
          date: selectedDate,
          time: selectedTime,
          seat: selectedSeats[i],
        });
      }

      // Use AJAX to add to cart
      $.ajax({
        url: mtTicketSearch.ajaxUrl,
        type: "POST",
        data: {
          action: "mt_add_tickets_to_cart",
          nonce: mtTicketSearch.seatmapNonce || mtTicketSearch.nonce,
          product_id: productId,
          tickets: tickets,
          buy_now: buyNow ? "true" : "false",
        },
        success: function (response) {
          if (response.success) {
            // Update cart fragments if available
            if (
              response.data.fragments &&
              typeof wc_add_to_cart_params !== "undefined"
            ) {
              $.each(response.data.fragments, function (key, value) {
                $(key).replaceWith(value);
              });
            }

            // Update cart hash
            if (response.data.cart_hash) {
              $("body").trigger("wc_fragment_refresh");
            }

            if (buyNow) {
              // Redirect to checkout
              window.location.href =
                response.data.redirect ||
                response.data.checkout_url ||
                "/checkout/";
            } else {
              // Redirect to cart after adding
              if (response.data.cart_url) {
                window.location.href = response.data.cart_url;
              } else {
                // Fallback: Show success message and redirect to cart
                Swal.fire({
                  icon: "success",
                  title:
                    response.data.message ||
                    mtTicketSearch.i18n.ticketAdded ||
                    "Ticket added to cart successfully!",
                  confirmButtonText: mtTicketSearch.i18n.ok || "OK",
                }).then(function () {
                  // Redirect to cart page
                  if (
                    typeof wc_add_to_cart_params !== "undefined" &&
                    wc_add_to_cart_params.cart_url
                  ) {
                    window.location.href = wc_add_to_cart_params.cart_url;
                  } else if (response.data.cart_url) {
                    window.location.href = response.data.cart_url;
                  }
                });
              }
            }

            // Clear selected seats after successful add
            $resultItem.data("selected-seats", []);
            var $container = $resultItem.find(".mt-result-seatmap-container");
            $container
              .find(".mt-seat-selected")
              .removeClass("mt-seat-selected")
              .addClass("mt-seat-available");
            var $buttons = $resultItem.find(
              ".mt-result-button-add-cart, .mt-result-button-buy-now",
            );
            $buttons.prop("disabled", false).addClass("mt-button-disabled");
          } else {
            Swal.fire({
              icon: "error",
              title:
                response.data?.message ||
                mtTicketSearch.i18n.errorAddingToCart ||
                "Error adding to cart",
              confirmButtonText: mtTicketSearch.i18n.ok || "OK",
            });
            var $buttons = $resultItem.find(
              ".mt-result-button-add-cart, .mt-result-button-buy-now",
            );
            $buttons.removeClass("mt-button-disabled");
            $buttons.text(function () {
              return $(this).hasClass("mt-result-button-add-cart")
                ? "Add to Cart"
                : "Buy Now";
            });
          }
        },
        error: function () {
          Swal.fire({
            icon: "error",
            title:
              mtTicketSearch.i18n.errorAddingToCart || "Error adding to cart",
            confirmButtonText: mtTicketSearch.i18n.ok || "OK",
          });
          var $buttons = $resultItem.find(
            ".mt-result-button-add-cart, .mt-result-button-buy-now",
          );
          // Check if there are selected seats to determine button state
          var selectedSeats = $resultItem.data("selected-seats") || [];
          if (selectedSeats.length > 0) {
            $buttons.removeClass("mt-button-disabled");
          } else {
            $buttons.addClass("mt-button-disabled");
          }
          $buttons.text(function () {
            return $(this).hasClass("mt-result-button-add-cart")
              ? "Add to Cart"
              : "Buy Now";
          });
        },
      });
    },
  };

  // Ticket Seats View Functionality
  var TicketSeatsView = {
    init: function () {
      this.setupFormSubmit();
    },

    setupFormSubmit: function () {
      var self = this;
      $("#mt-ticket-seats-form").on("submit", function (e) {
        e.preventDefault();
        var orderNumber = $("#mt-ticket-order-number").val().trim();

        if (!orderNumber) {
          Swal.fire({
            icon: "error",
            title: mtTicketSearch.i18n.error || "Error",
            text: mtTicketSearch.i18n.pleaseEnterOrderNumber || "Please enter an order number",
          });
          return;
        }

        self.loadTicketSeats(orderNumber);
      });
    },

    loadTicketSeats: function (orderNumber) {
      var self = this;
      var $result = $("#mt-ticket-seats-result");
      var $loading = $result.find(".mt-ticket-seats-loading");
      var $error = $result.find(".mt-ticket-seats-error");
      var $seatmap = $result.find(".mt-ticket-seats-seatmap");

      // Show result container and loading
      $result.show();
      $loading.show();
      $error.hide();
      $seatmap.hide();

      $.ajax({
        url: mtTicketSearch.ajaxUrl,
        type: "POST",
        data: {
          action: "mt_get_ticket_seats",
          nonce: mtTicketSearch.nonce,
          order_number: orderNumber,
        },
        success: function (response) {
          $loading.hide();

          if (response.success && response.data) {
            var data = response.data;
            self.renderSeatMap(data);
            $seatmap.show();
          } else {
            var errorMsg =
              response.data && response.data.message
                ? response.data.message
                : (mtTicketSearch.i18n.failedToLoadTicket || "Failed to load ticket information.");
            $error.find(".mt-error-message").text(errorMsg);
            $error.show();
          }
        },
        error: function () {
          $loading.hide();
          $error
            .find(".mt-error-message")
            .text(mtTicketSearch.i18n.errorLoadingTicket || "An error occurred while loading ticket information.");
          $error.show();
        },
      });
    },

    renderSeatMap: function (data) {
      var self = this;

      // Update route info
      var routeText =
        data.route.start_station + " → " + data.route.end_station;
      var detailsText =
        (mtTicketSearch.i18n.date || "Date") + ": " +
        data.departure_date +
        " | " +
        (mtTicketSearch.i18n.time || "Time") + ": " +
        data.departure_time +
        " → " +
        data.arrival_time;

      $(".mt-ticket-seats-route").text(routeText);
      $(".mt-ticket-seats-details").text(detailsText);

      // Render seat map using existing seatmap functionality
      var $container = $(".mt-bus-seat-layout-view-only");
      $container.empty();

      if (!data.seat_layout || !data.seat_layout.config || !data.seat_layout.seats) {
        $container.html(
          '<div class="mt-seat-layout-error">Invalid seat layout</div>'
        );
        return;
      }

      var config = data.seat_layout.config;
      var seats = data.seat_layout.seats;
      var leftSeats = config.left || 0;
      var rightSeats = config.right || 0;
      var rows = config.rows || 10;

      // Create seat map HTML
      var html = '<div class="mt-seat-map-wrapper">';
      html += '<div class="mt-seat-map-legend">';
      html +=
        '<span class="mt-legend-item"><span class="mt-legend-seat mt-seat-available"></span> ' +
        (mtTicketSearch.i18n.available || "Available") +
        "</span>";
      html +=
        '<span class="mt-legend-item"><span class="mt-legend-seat mt-seat-reserved"></span> ' +
        (mtTicketSearch.i18n.reserved || "Reserved") +
        "</span>";
      html +=
        '<span class="mt-legend-item"><span class="mt-legend-seat mt-seat-ticket"></span> ' +
        (mtTicketSearch.i18n.yourSeats || "Your Seats") +
        "</span>";
      html +=
        '<span class="mt-legend-item"><span class="mt-legend-seat mt-seat-disabled"></span> ' +
        (mtTicketSearch.i18n.disabled || "Disabled") +
        "</span>";
      html += "</div>";

      html += '<div class="mt-seat-map">';
      html += '<div class="mt-seat-map-aisle-left"></div>';

      // Render seats
      for (var row = 1; row <= rows; row++) {
        html += '<div class="mt-seat-row">';
        html += '<span class="mt-seat-row-number">' + row + "</span>";

        // Left column seats
        for (var col = 0; col < leftSeats; col++) {
          var colLetter = String.fromCharCode(65 + col);
          var seatId = colLetter + row;
          var isSeatEnabled =
            seats[seatId] === true ||
            seats[seatId] === 1 ||
            seats[seatId] === "1";
          var isReserved = data.reserved_seats.indexOf(seatId) !== -1;
          var isTicketSeat = data.ticket_seats.indexOf(seatId) !== -1;
          var classes = "mt-seat mt-seat-view-only";

          if (!isSeatEnabled) {
            classes += " mt-seat-disabled";
          } else if (isTicketSeat) {
            classes += " mt-seat-ticket";
          } else if (isReserved) {
            classes += " mt-seat-reserved";
          } else {
            classes += " mt-seat-available";
          }

          html +=
            '<div class="' +
            classes +
            '" data-seat="' +
            seatId +
            '">' +
            seatId +
            "</div>";
        }

        // Aisle
        html += '<div class="mt-seat-aisle"></div>';

        // Right column seats
        for (var col = 0; col < rightSeats; col++) {
          var colLetter = String.fromCharCode(65 + leftSeats + col);
          var seatId = colLetter + row;
          var isSeatEnabled =
            seats[seatId] === true ||
            seats[seatId] === 1 ||
            seats[seatId] === "1";
          var isReserved = data.reserved_seats.indexOf(seatId) !== -1;
          var isTicketSeat = data.ticket_seats.indexOf(seatId) !== -1;
          var classes = "mt-seat mt-seat-view-only";

          if (!isSeatEnabled) {
            classes += " mt-seat-disabled";
          } else if (isTicketSeat) {
            classes += " mt-seat-ticket";
          } else if (isReserved) {
            classes += " mt-seat-reserved";
          } else {
            classes += " mt-seat-available";
          }

          html +=
            '<div class="' +
            classes +
            '" data-seat="' +
            seatId +
            '">' +
            seatId +
            "</div>";
        }

        html += "</div>";
      }

      html += "</div>"; // mt-seat-map
      html += "</div>"; // mt-seat-map-wrapper

      $container.html(html);
    },
  };

  $(document).ready(function () {
    TicketSearch.init();
    // Initialize ticket seats view if form exists
    if ($("#mt-ticket-seats-form").length) {
      TicketSeatsView.init();
    }
  });
})(jQuery);
