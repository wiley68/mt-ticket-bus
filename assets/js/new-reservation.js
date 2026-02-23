/**
 * New Reservation admin page: customer toggle, courses, seat map, form submit.
 *
 * @package MT_Ticket_Bus
 */

(function ($) {
  "use strict";

  var data = window.mtNewReservationData;
  if (!data || !data.products || !data.ajaxUrl || !data.nonce) return;

  var $form = $("#mt-new-reservation-form");
  var $product = $("#mt-product-id");
  var $date = $("#mt-departure-date");
  var $course = $("#mt-departure-time");
  var $guestFields = $("#mt-guest-fields");
  var $seatmapContainer = $("#mt-seatmap-container");
  var $seatsInputs = $("#mt-seats-inputs");
  var $coursesLoading = $("#mt-courses-loading");
  var selectedSeats = [];
  var allowedDates = []; // allowed departure dates for selected product (Y-m-d)

  function showGuestFields(show) {
    $guestFields.toggle(!!show);
  }

  $("#mt-customer-type").on("change", function () {
    showGuestFields(parseInt($(this).val(), 10) === 0);
  });
  showGuestFields(parseInt($("#mt-customer-type").val(), 10) === 0);

  function fetchAllowedDates(scheduleId, busId, done) {
    var now = new Date();
    var months = [];
    for (var i = 0; i < 3; i++) {
      var m = now.getMonth() + 1 + i;
      var y = now.getFullYear();
      if (m > 12) {
        m -= 12;
        y += 1;
      }
      months.push({ month: m, year: y });
    }
    var allDates = [];
    var pending = months.length;
    months.forEach(function (obj) {
      $.post(data.ajaxUrl, {
        action: "mt_get_available_dates_admin",
        nonce: data.nonce,
        schedule_id: scheduleId,
        bus_id: busId,
        month: obj.month,
        year: obj.year,
      })
        .done(function (res) {
          if (res.success && res.data && res.data.dates) {
            res.data.dates.forEach(function (d) {
              if (d.available && d.date) allDates.push(d.date);
            });
          }
        })
        .always(function () {
          pending--;
          if (pending === 0) done(allDates.sort());
        });
    });
  }

  function updateDateInputState() {
    var productId = $product.val();
    var hasProduct = productId && data.products[productId];
    $date.prop("disabled", !hasProduct);
    if (!hasProduct) {
      $date.val("");
      allowedDates = [];
      return;
    }
    var prod = data.products[productId];
    $date.attr("min", new Date().toISOString().slice(0, 10));
    $date.val("");
    allowedDates = [];
    $date.siblings(".mt-date-hint").remove();
    $date.siblings(".mt-date-error").remove();
    $date.after(
      '<span class="mt-date-hint description" style="display:block; margin-top:4px;">' +
        (data.i18n && data.i18n.loadingDates
          ? data.i18n.loadingDates
          : "Loading available dates…") +
        "</span>",
    );
    fetchAllowedDates(prod.schedule_id, prod.bus_id, function (dates) {
      allowedDates = dates;
      $date.siblings(".mt-date-hint").remove();
      if (allowedDates.length) {
        $date.after(
          '<p class="mt-date-hint description" style="margin-top:4px;">' +
            (data.i18n && data.i18n.selectValidDate
              ? data.i18n.selectValidDate
              : "Select a date valid for this schedule.") +
            "</p>",
        );
      } else {
        $date.prop("disabled", true);
        $date.after(
          '<p class="mt-date-hint description" style="margin-top:4px; color:#b32d2e;">' +
            (data.i18n && data.i18n.noDatesForSchedule
              ? data.i18n.noDatesForSchedule
              : "No available dates for this schedule.") +
            "</p>",
        );
      }
    });
  }

  function isDateAllowed(dateStr) {
    if (!dateStr || !allowedDates.length) return true;
    return allowedDates.indexOf(dateStr) !== -1;
  }

  function validateDateInput() {
    var dateVal = $date.val();
    $date.siblings(".mt-date-error").remove();
    if (!dateVal) return true;
    if (!allowedDates.length) return true;
    if (isDateAllowed(dateVal)) return true;
    $date.after(
      '<p class="mt-date-error description" style="margin-top:4px; color:#b32d2e;">' +
        (data.i18n && data.i18n.dateNotAvailable
          ? data.i18n.dateNotAvailable
          : "This date is not available for the selected schedule.") +
        "</p>",
    );
    $date.val("");
    return false;
  }

  function loadCourses() {
    var productId = $product.val();
    var scheduleId = productId
      ? data.products[productId] && data.products[productId].schedule_id
      : 0;
    if (!scheduleId) {
      $course.html('<option value="">— Select product first —</option>');
      return;
    }
    $course.html('<option value="">Loading...</option>');
    $coursesLoading.show();
    $.post(data.ajaxUrl, {
      action: "mt_get_courses_by_schedule",
      nonce: data.nonce,
      schedule_id: scheduleId,
    })
      .done(function (res) {
        if (
          res.success &&
          res.data &&
          res.data.courses &&
          res.data.courses.length
        ) {
          var opts = '<option value="">— Select course —</option>';
          res.data.courses.forEach(function (c) {
            opts +=
              '<option value="' +
              (c.value || "") +
              '">' +
              (c.label || c.value) +
              "</option>";
          });
          $course.html(opts);
        } else {
          $course.html('<option value="">— No courses —</option>');
        }
      })
      .fail(function () {
        $course.html('<option value="">— Error —</option>');
      })
      .always(function () {
        $coursesLoading.hide();
        loadSeatmap();
      });
  }

  function loadSeatmap() {
    var productId = $product.val();
    var prod = productId ? data.products[productId] : null;
    var scheduleId = prod ? prod.schedule_id : 0;
    var busId = prod ? prod.bus_id : 0;
    var dateVal = $date.val();
    var timeVal = $course.val();
    selectedSeats = [];
    $seatsInputs.empty();

    if (!scheduleId || !busId || !dateVal || !timeVal) {
      $seatmapContainer.html(
        '<p class="description">Select product, date and course.</p>',
      );
      return;
    }

    $seatmapContainer.html('<p class="description">Loading...</p>');
    $.post(data.ajaxUrl, {
      action: "mt_get_available_seats_admin",
      nonce: data.nonce,
      schedule_id: scheduleId,
      bus_id: busId,
      date: dateVal,
      departure_time: timeVal,
    })
      .done(function (res) {
        if (!res.success || !res.data) {
          $seatmapContainer.html(
            '<p class="description">Error loading seats.</p>',
          );
          return;
        }
        var layout = res.data.seat_layout || {};
        var seats = layout.seats || {};
        var config = layout.config || {};
        var available = res.data.available_seats || [];
        var availableSet = {};
        available.forEach(function (s) {
          availableSet[s] = true;
        });

        var leftSeats = config.left || 0;
        var rightSeats = config.right || 0;
        var rows = config.rows || 0;
        var useRowLayout = leftSeats > 0 && rightSeats >= 0 && rows > 0;

        var html =
          '<div class="mt-new-reservation-seatmap" style="display:inline-block; margin-top:8px;">';

        if (useRowLayout) {
          for (var row = 1; row <= rows; row++) {
            html += '<div class="mt-new-reservation-seat-row">';
            for (var col = 0; col < leftSeats; col++) {
              var colLetter = String.fromCharCode(65 + col);
              var seatId = colLetter + row;
              var isAvailable = availableSet[seatId];
              var enabled = seats[seatId] === true && isAvailable;
              var cls = "mt-seat-cell";
              if (!enabled) cls += " mt-seat-disabled";
              html +=
                '<button type="button" class="' +
                cls +
                '" data-seat="' +
                (seatId || "").replace(/"/g, "&quot;") +
                '" ' +
                (enabled ? "" : 'disabled="disabled"') +
                ">" +
                (seatId || "") +
                "</button>";
            }
            if (leftSeats > 0 && rightSeats > 0) {
              html += '<span class="mt-new-reservation-seat-aisle"></span>';
            }
            for (var col = 0; col < rightSeats; col++) {
              var colLetter = String.fromCharCode(65 + leftSeats + col);
              var seatId = colLetter + row;
              var isAvailable = availableSet[seatId];
              var enabled = seats[seatId] === true && isAvailable;
              var cls = "mt-seat-cell";
              if (!enabled) cls += " mt-seat-disabled";
              html +=
                '<button type="button" class="' +
                cls +
                '" data-seat="' +
                (seatId || "").replace(/"/g, "&quot;") +
                '" ' +
                (enabled ? "" : 'disabled="disabled"') +
                ">" +
                (seatId || "") +
                "</button>";
            }
            html += "</div>";
          }
        } else {
          html +=
            '<div class="mt-new-reservation-seat-row mt-new-reservation-seat-row-wrap">';
          var seatIds = Object.keys(seats).sort();
          seatIds.forEach(function (seatId) {
            var isAvailable = availableSet[seatId];
            var enabled = seats[seatId] === true && isAvailable;
            var cls = "mt-seat-cell";
            if (!enabled) cls += " mt-seat-disabled";
            html +=
              '<button type="button" class="' +
              cls +
              '" data-seat="' +
              (seatId || "").replace(/"/g, "&quot;") +
              '" ' +
              (enabled ? "" : 'disabled="disabled"') +
              ">" +
              (seatId || "") +
              "</button>";
          });
          html += "</div>";
        }

        html += "</div>";
        $seatmapContainer.html(html);

        $seatmapContainer
          .find(".mt-seat-cell:not(.mt-seat-disabled)")
          .on("click", function () {
            var seat = $(this).data("seat");
            var idx = selectedSeats.indexOf(seat);
            if (idx >= 0) {
              selectedSeats.splice(idx, 1);
              $(this).removeClass("mt-seat-selected");
            } else {
              selectedSeats.push(seat);
              $(this).addClass("mt-seat-selected");
            }
          });
      })
      .fail(function () {
        $seatmapContainer.html(
          '<p class="description">Error loading seats.</p>',
        );
      });
  }

  $product.on("change", function () {
    $course.html('<option value="">— Select date —</option>');
    updateDateInputState();
    loadSeatmap();
  });
  $date.on("change", function () {
    if (!validateDateInput()) return;
    loadCourses();
  });
  $course.on("change", function () {
    loadSeatmap();
  });

  updateDateInputState();

  $form.on("submit", function (e) {
    var customerId = parseInt($("#mt-customer-type").val(), 10);
    if (customerId === 0) {
      var first = $("#mt-guest-first").val() || "";
      var last = $("#mt-guest-last").val() || "";
      var email = $("#mt-guest-email").val() || "";
      if (!first.trim() || !last.trim() || !email.trim()) {
        e.preventDefault();
        alert("Please enter guest first name, last name and email.");
        return;
      }
    }
    if (!validateDateInput()) {
      e.preventDefault();
      return;
    }
    $seatsInputs.empty();
    if (selectedSeats.length === 0) {
      e.preventDefault();
      alert("Please select at least one seat.");
      return;
    }
    selectedSeats.forEach(function (s) {
      $seatsInputs.append(
        '<input type="hidden" name="seats[]" value="' +
          (s || "").replace(/"/g, "&quot;") +
          '" />',
      );
    });
  });
})(jQuery);
