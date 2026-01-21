/**
 * Seatmap functionality for ticket products
 *
 * @package MT_Ticket_Bus
 */

(function ($) {
    'use strict';

    var SeatmapManager = {
        productId: null,
        scheduleId: null,
        busId: null,
        routeId: null,
        selectedDate: null,
        selectedTime: null,
        selectedCourseInfo: null, // Store course availability info
        selectedSeats: [], // Array of selected seat IDs
        currentMonth: null,
        currentYear: null,
        seatLayout: null,
        availableSeats: [],

        init: function () {
            var $seatmapBlock = $('.mt-ticket-seatmap-block');
            if (!$seatmapBlock.length) {
                return;
            }

            // Get data attributes
            this.productId = $seatmapBlock.data('product-id');
            this.scheduleId = $seatmapBlock.data('schedule-id');
            this.busId = $seatmapBlock.data('bus-id');
            this.routeId = $seatmapBlock.data('route-id');

            if (!this.productId || !this.scheduleId || !this.busId) {
                return;
            }

            // Initialize calendar
            var $calendar = $seatmapBlock.find('.mt-calendar-container');
            if ($calendar.length) {
                this.currentMonth = parseInt($calendar.data('month'), 10);
                this.currentYear = parseInt($calendar.data('year'), 10);
                this.loadCalendarDates();
            }

            // Calendar navigation
            $seatmapBlock.on('click', '.mt-calendar-prev', this.prevMonth.bind(this));
            $seatmapBlock.on('click', '.mt-calendar-next', this.nextMonth.bind(this));

            // Date selection
            $seatmapBlock.on('click', '.mt-calendar-day:not(.mt-calendar-day-disabled)', this.selectDate.bind(this));

            // Time selection
            $seatmapBlock.on('click', '.mt-time-option', this.selectTime.bind(this));

            // Seat selection (toggle) - can select available or already selected seats
            $seatmapBlock.on('click', '.mt-seat.mt-seat-available, .mt-seat.mt-seat-selected', this.selectSeat.bind(this));
        },

        loadCalendarDates: function () {
            var self = this;
            var $calendar = $('.mt-ticket-seatmap-block .mt-calendar-container');
            var $grid = $calendar.find('.mt-calendar-grid');

            // Show loading
            $grid.addClass('mt-loading');

            $.ajax({
                url: mtTicketBus.ajaxurl || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'mt_get_available_dates',
                    nonce: mtTicketBus.nonce,
                    schedule_id: this.scheduleId,
                    bus_id: this.busId,
                    month: this.currentMonth,
                    year: this.currentYear,
                },
                success: function (response) {
                    if (response.success && response.data.dates) {
                        self.renderCalendar(response.data.dates, response.data.month, response.data.year);
                    } else {
                        console.error('Failed to load dates:', response.data);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error:', error);
                },
                complete: function () {
                    $grid.removeClass('mt-loading');
                },
            });
        },

        renderCalendar: function (dates, month, year) {
            var $calendar = $('.mt-ticket-seatmap-block .mt-calendar-container');
            var $grid = $calendar.find('.mt-calendar-grid');
            var $monthYear = $calendar.find('.mt-calendar-month-year');

            // Update month/year display
            var monthNames = [
                'Януари', 'Февруари', 'Март', 'Април', 'Май', 'Юни',
                'Юли', 'Август', 'Септември', 'Октомври', 'Ноември', 'Декември'
            ];
            $monthYear.text(monthNames[month - 1] + ' ' + year);

            // Clear existing days (keep weekday headers)
            $grid.find('.mt-calendar-day').remove();

            // Get first day of month and number of days
            var firstDay = new Date(year, month - 1, 1).getDay();
            var daysInMonth = new Date(year, month, 0).getDate();
            var today = new Date();
            today.setHours(0, 0, 0, 0);

            // Create date map for quick lookup
            var dateMap = {};
            dates.forEach(function (dateInfo) {
                dateMap[dateInfo.date] = dateInfo;
            });

            // Add empty cells for days before month starts
            for (var i = 0; i < firstDay; i++) {
                $grid.append('<div class="mt-calendar-day mt-calendar-day-empty"></div>');
            }

            // Add days
            for (var day = 1; day <= daysInMonth; day++) {
                var date = year + '-' + String(month).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                var dateObj = new Date(year, month - 1, day);
                dateObj.setHours(0, 0, 0, 0);

                var $day = $('<div class="mt-calendar-day"></div>');
                $day.text(day);

                // Check if date is available
                if (dateMap[date]) {
                    var dateInfo = dateMap[date];
                    if (dateInfo.available) {
                        $day.addClass('mt-calendar-day-available');
                    } else {
                        $day.addClass('mt-calendar-day-unavailable');
                    }
                    $day.attr('data-date', date);
                } else if (dateObj < today) {
                    $day.addClass('mt-calendar-day-disabled');
                    $day.attr('title', mtTicketBus.i18n.pastDate || 'Past date');
                } else {
                    $day.addClass('mt-calendar-day-disabled');
                    $day.attr('title', mtTicketBus.i18n.unavailableDate || 'Unavailable date');
                }

                $grid.append($day);
            }
        },

        prevMonth: function (e) {
            e.preventDefault();
            this.currentMonth--;
            if (this.currentMonth < 1) {
                this.currentMonth = 12;
                this.currentYear--;
            }
            this.loadCalendarDates();
        },

        nextMonth: function (e) {
            e.preventDefault();
            this.currentMonth++;
            if (this.currentMonth > 12) {
                this.currentMonth = 1;
                this.currentYear++;
            }
            this.loadCalendarDates();
        },

        selectDate: function (e) {
            var $day = $(e.currentTarget);
            var date = $day.data('date');

            if (!date) {
                return;
            }

            // Update selected state
            $('.mt-calendar-day').removeClass('mt-calendar-day-selected');
            $day.addClass('mt-calendar-day-selected');

            this.selectedDate = date;
            this.selectedTime = null;
            this.selectedSeats = []; // Reset selected seats when date changes

            // Show selected date
            var dateObj = new Date(date);
            var dateFormatted = dateObj.toLocaleDateString('bg-BG', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            });
            $('.mt-selected-date-value').text(dateFormatted);
            $('.mt-date-selected').show();

            // Show time picker
            $('.mt-seatmap-time-picker').show();
            $('.mt-seatmap-container').hide();

            // Reset time selection
            $('.mt-time-option').removeClass('mt-time-option-selected');
            $('.mt-time-selected').hide();

            // Load course availability for selected date
            this.loadCourseAvailability(date);
        },

        loadCourseAvailability: function (date) {
            var self = this;
            var $timeOptions = $('.mt-time-option');

            if (!$timeOptions.length) {
                return;
            }

            // Show loading state on all time options
            $timeOptions.addClass('mt-loading');

            $.ajax({
                url: mtTicketBus.ajaxurl || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'mt_get_course_availability',
                    nonce: mtTicketBus.nonce,
                    schedule_id: this.scheduleId,
                    bus_id: this.busId,
                    date: date,
                },
                success: function (response) {
                    if (response.success && response.data && response.data.courses) {
                        self.updateCourseAvailability(response.data.courses);
                    } else {
                        // Remove loading state even on error
                        $timeOptions.removeClass('mt-loading');
                    }
                },
                error: function (xhr, status, error) {
                    $timeOptions.removeClass('mt-loading');
                },
            });
        },

        updateCourseAvailability: function (coursesAvailability) {
            var self = this;
            var $timeOptions = $('.mt-time-option');

            $timeOptions.each(function () {
                var $option = $(this);
                var departureTime = $option.data('departure-time');

                if (!departureTime) {
                    $option.removeClass('mt-loading');
                    return;
                }

                // Normalize time format for comparison (HH:MM:SS or HH:MM -> HH:MM)
                var normalizeTime = function(timeStr) {
                    if (!timeStr) return '';
                    return timeStr.substring(0, 5); // Get HH:MM part
                };

                var normalizedOptionTime = normalizeTime(departureTime);

                // Find availability for this course
                var courseInfo = coursesAvailability.find(function (course) {
                    if (!course.departure_time) return false;
                    var normalizedCourseTime = normalizeTime(course.departure_time);
                    return normalizedCourseTime === normalizedOptionTime;
                });

                if (courseInfo) {
                    var availableText = mtTicketBus.i18n.availableSeats || 'available seats';
                    var ofText = mtTicketBus.i18n.of || 'of';
                    var tooltipText = '';

                    if (courseInfo.available_seats > 0) {
                        tooltipText = courseInfo.available_seats + ' ' + availableText + ' ' + ofText + ' ' + courseInfo.total_seats;
                        $option.addClass('mt-course-available');
                        $option.removeClass('mt-course-unavailable');
                        $option.prop('disabled', false);
                    } else {
                        tooltipText = mtTicketBus.i18n.noAvailableSeats || 'No available seats';
                        $option.addClass('mt-course-unavailable');
                        $option.removeClass('mt-course-available');
                        $option.prop('disabled', true);
                    }

                    // Set tooltip text
                    $option.attr('title', tooltipText);
                    
                    // Store course info in data attribute for later use
                    $option.data('course-info', courseInfo);
                } else {
                    // If no course info found, remove any existing tooltip
                    $option.removeAttr('title');
                    $option.removeData('course-info');
                }

                // Remove loading state
                $option.removeClass('mt-loading');
            });
        },

        selectTime: function (e) {
            var $timeOption = $(e.currentTarget);
            var departureTime = $timeOption.data('departure-time');
            var arrivalTime = $timeOption.data('arrival-time');
            var courseInfo = $timeOption.data('course-info');

            if (!departureTime || !this.selectedDate) {
                return;
            }

            // Update selected state
            $('.mt-time-option').removeClass('mt-time-option-selected');
            $timeOption.addClass('mt-time-option-selected');

            this.selectedTime = departureTime;
            this.selectedCourseInfo = courseInfo; // Store course info
            this.selectedSeats = []; // Reset selected seats when time changes

            // Show selected time with availability info
            var timeFormatted = departureTime.substring(0, 5) + ' → ' + arrivalTime.substring(0, 5);
            var availabilityText = '';
            
            if (courseInfo) {
                var availableText = mtTicketBus.i18n.availableSeats || 'available seats';
                var ofText = mtTicketBus.i18n.of || 'of';
                
                if (courseInfo.available_seats > 0) {
                    availabilityText = ' (' + courseInfo.available_seats + ' ' + availableText + ' ' + ofText + ' ' + courseInfo.total_seats + ')';
                } else {
                    availabilityText = ' (' + (mtTicketBus.i18n.noAvailableSeats || 'No available seats') + ')';
                }
            }
            
            $('.mt-selected-time-value').text(timeFormatted + availabilityText);
            $('.mt-time-selected').show();

            // Load seat map
            this.loadSeatMap();
        },

        loadSeatMap: function () {
            var self = this;
            var $container = $('.mt-seatmap-container');
            var $layout = $container.find('.mt-bus-seat-layout');

            $container.show();
            $layout.html('<div class="mt-seat-layout-loading">' + (mtTicketBus.i18n.loading || 'Loading...') + '</div>');

            $.ajax({
                url: mtTicketBus.ajaxurl || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'mt_get_available_seats',
                    nonce: mtTicketBus.nonce,
                    schedule_id: this.scheduleId,
                    bus_id: this.busId,
                    date: this.selectedDate,
                    departure_time: this.selectedTime,
                },
                success: function (response) {
                    if (response.success && response.data.seat_layout) {
                        self.seatLayout = response.data.seat_layout;
                        self.availableSeats = response.data.available_seats || [];
                        self.renderSeatMap(response.data.seat_layout, response.data.available_seats);
                    } else {
                        $layout.html('<div class="mt-seat-layout-error">' + (mtTicketBus.i18n.loadingError || 'Error loading layout.') + '</div>');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error:', error);
                    $layout.html('<div class="mt-seat-layout-error">Грешка при зареждане на схемата.</div>');
                },
            });
        },

        renderSeatMap: function (layoutData, availableSeats) {
            var $layout = $('.mt-bus-seat-layout');
            $layout.empty();

            if (!layoutData || !layoutData.config || !layoutData.seats) {
                $layout.html('<div class="mt-seat-layout-error">' + (mtTicketBus.i18n.invalidLayout || 'Invalid seat layout.') + '</div>');
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
            html += '<span class="mt-legend-item"><span class="mt-legend-seat mt-seat-available"></span> Свободно</span>';
            html += '<span class="mt-legend-item"><span class="mt-legend-seat mt-seat-reserved"></span> Заето</span>';
            html += '<span class="mt-legend-item"><span class="mt-legend-seat mt-seat-selected"></span> Избрано</span>';
            html += '<span class="mt-legend-item"><span class="mt-legend-seat mt-seat-disabled"></span> Недостъпно</span>';
            html += '</div>';

            html += '<div class="mt-seat-map">';
            html += '<div class="mt-seat-map-aisle-left"></div>'; // Left aisle indicator

            // Render seats
            for (var row = 1; row <= rows; row++) {
                html += '<div class="mt-seat-row">';
                html += '<span class="mt-seat-row-number">' + row + '</span>';

                // Left column seats
                for (var col = 0; col < leftSeats; col++) {
                    var colLetter = String.fromCharCode(65 + col); // A, B, C...
                    var seatId = colLetter + row;
                    html += this.renderSeat(seatId, seats, availableSeats);
                }

                // Aisle
                html += '<div class="mt-seat-aisle"></div>';

                // Right column seats
                for (var col = 0; col < rightSeats; col++) {
                    var colLetter = String.fromCharCode(65 + leftSeats + col);
                    var seatId = colLetter + row;
                    html += this.renderSeat(seatId, seats, availableSeats);
                }

                html += '</div>';
            }

            html += '<div class="mt-seat-map-aisle-right"></div>'; // Right aisle indicator
            html += '</div>'; // mt-seat-map
            html += '</div>'; // mt-seat-map-wrapper

            $layout.html(html);
        },

        renderSeat: function (seatId, seats, availableSeats) {
            var isSeatEnabled = seats[seatId] === true || seats[seatId] === 1 || seats[seatId] === '1';
            var isAvailable = availableSeats.indexOf(seatId) !== -1;
            var isSelected = this.selectedSeats.indexOf(seatId) !== -1;
            var classes = 'mt-seat';

            if (!isSeatEnabled) {
                classes += ' mt-seat-disabled';
            } else if (isSelected) {
                classes += ' mt-seat-selected';
            } else if (!isAvailable) {
                classes += ' mt-seat-reserved';
            } else {
                classes += ' mt-seat-available';
            }

            var seatTitle = (mtTicketBus.i18n.seat || 'Seat') + ' ' + seatId;
            return '<button type="button" class="' + classes + '" data-seat-id="' + seatId + '" title="' + seatTitle + '">' + seatId + '</button>';
        },

        selectSeat: function (e) {
            var $seat = $(e.currentTarget);
            var seatId = $seat.data('seat-id');

            if (!seatId || !this.selectedDate || !this.selectedTime) {
                return;
            }

            // Check if seat is available (can only toggle available seats)
            if (!$seat.hasClass('mt-seat-available') && !$seat.hasClass('mt-seat-selected')) {
                return; // Can't select reserved or disabled seats
            }

            // Toggle seat selection
            var seatIndex = this.selectedSeats.indexOf(seatId);
            if (seatIndex > -1) {
                // Deselect seat
                this.selectedSeats.splice(seatIndex, 1);
                $seat.removeClass('mt-seat-selected');
                $seat.addClass('mt-seat-available');
            } else {
                // Select seat
                this.selectedSeats.push(seatId);
                $seat.removeClass('mt-seat-available');
                $seat.addClass('mt-seat-selected');
            }

            // Trigger custom event for ticket summary block with all selected seats
            $(document).trigger('mt_seats_updated', {
                productId: this.productId,
                scheduleId: this.scheduleId,
                busId: this.busId,
                routeId: this.routeId,
                date: this.selectedDate,
                time: this.selectedTime,
                seats: this.selectedSeats.slice(), // Copy array
                seatCount: this.selectedSeats.length,
            });
        },
    };

    // Initialize on document ready
    $(document).ready(function () {
        SeatmapManager.init();
    });

    // Re-initialize on AJAX content load (for dynamic content)
    $(document).on('mt_seatmap_init', function () {
        SeatmapManager.init();
    });

})(jQuery);
