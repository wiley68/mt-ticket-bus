/**
 * Order actions functionality (Print and Download ticket)
 *
 * @package MT_Ticket_Bus
 */

(function ($) {
  "use strict";

  $(document).ready(function () {
    // Print ticket button
    $(document).on("click", ".mt-btn-print-ticket", function (e) {
      e.preventDefault();

      var $button = $(this);
      var orderId = $button.data("order-id");
      var orderKey = $button.data("order-key");

      if (!orderId || !orderKey) {
        alert(mtTicketOrderActions.i18n.error);
        return;
      }

      // Disable button during request
      $button.prop("disabled", true).text("Loading...");

      $.ajax({
        url: mtTicketOrderActions.ajaxUrl,
        type: "POST",
        data: {
          action: "mt_print_ticket",
          nonce: mtTicketOrderActions.nonce,
          order_id: orderId,
          order_key: orderKey,
        },
        success: function (response) {
          if (response.success && response.data.print_url) {
            // Open print page in new window
            var printWindow = window.open(
              response.data.print_url,
              "_blank",
              "width=800,height=600",
            );

            // Wait for window to load, then trigger print
            if (printWindow) {
              printWindow.onload = function () {
                setTimeout(function () {
                  printWindow.print();
                }, 500);
              };
            }
          } else {
            alert(response.data?.message || mtTicketOrderActions.i18n.error);
          }
        },
        error: function () {
          alert(mtTicketOrderActions.i18n.error);
        },
        complete: function () {
          $button
            .prop("disabled", false)
            .html(
              '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: currentColor; margin-right: 0.5rem;"><title>printer-outline</title><path d="M19 8C20.66 8 22 9.34 22 11V17H18V21H6V17H2V11C2 9.34 3.34 8 5 8H6V3H18V8H19M8 5V8H16V5H8M16 19V15H8V19H16M18 15H20V11C20 10.45 19.55 10 19 10H5C4.45 10 4 10.45 4 11V15H6V13H18V15M19 11.5C19 12.05 18.55 12.5 18 12.5C17.45 12.5 17 12.05 17 11.5C17 10.95 17.45 10.5 18 10.5C18.55 10.5 19 10.95 19 11.5Z" /></svg>' +
                mtTicketOrderActions.i18n.printTicket,
            );
        },
      });
    });

    // Download ticket button
    $(document).on("click", ".mt-btn-download-ticket", function (e) {
      e.preventDefault();

      var $button = $(this);
      var orderId = $button.data("order-id");
      var orderKey = $button.data("order-key");

      if (!orderId || !orderKey) {
        alert(mtTicketOrderActions.i18n.error);
        return;
      }

      // Disable button during request
      $button.prop("disabled", true).text("Loading...");

      // Create form and submit to download PDF
      var form = $("<form>", {
        method: "POST",
        action: mtTicketOrderActions.ajaxUrl,
        target: "_blank",
      });

      form.append(
        $("<input>", {
          type: "hidden",
          name: "action",
          value: "mt_download_ticket",
        }),
      );
      form.append(
        $("<input>", {
          type: "hidden",
          name: "nonce",
          value: mtTicketOrderActions.nonce,
        }),
      );
      form.append(
        $("<input>", {
          type: "hidden",
          name: "order_id",
          value: orderId,
        }),
      );
      form.append(
        $("<input>", {
          type: "hidden",
          name: "order_key",
          value: orderKey,
        }),
      );

      $("body").append(form);
      form.submit();
      form.remove();

      // Re-enable button after a delay
      setTimeout(function () {
        $button
          .prop("disabled", false)
          .html(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: currentColor; margin-right: 0.5rem;"><title>download</title><path d="M5,20H19V18H5M19,9H15V3H9V9H5L12,16L19,9Z" /></svg>' +
              mtTicketOrderActions.i18n.downloadTicket,
          );
      }, 2000);
    });

    // Generate QR code for ticket download
    var $qrContainer = $("#mt-ticket-qr-code");
    if ($qrContainer.length) {
      var downloadUrl = $qrContainer.data("download-url");
      if (downloadUrl) {
        // Wait for QRCode library to load (qrcodejs uses QRCode constructor)
        function generateQRCode() {
          try {
            // Clear container first
            $qrContainer.empty();

            // Create QR code using qrcodejs library
            new QRCode($qrContainer[0], {
              text: downloadUrl,
              width: 200,
              height: 200,
              colorDark: "#000000",
              colorLight: "#FFFFFF",
              correctLevel: QRCode.CorrectLevel.H,
            });
          } catch (error) {
            console.error("QR code generation error:", error);
            $qrContainer.html(
              '<p style="color: #dc2626; font-size: 0.875rem;">' +
                "QR code could not be generated" +
                "</p>",
            );
          }
        }

        // Check if library is loaded
        if (typeof QRCode !== "undefined") {
          generateQRCode();
        } else {
          // Retry after delays if library not loaded yet
          var retries = 0;
          var maxRetries = 10;
          var checkInterval = setInterval(function () {
            retries++;
            if (typeof QRCode !== "undefined") {
              clearInterval(checkInterval);
              generateQRCode();
            } else if (retries >= maxRetries) {
              clearInterval(checkInterval);
              console.error("QRCode library not loaded after retries");
              $qrContainer.html(
                '<p style="color: #dc2626; font-size: 0.875rem;">' +
                  "QR code could not be generated" +
                  "</p>",
              );
            }
          }, 200);
        }
      }
    }
  });
})(jQuery);
