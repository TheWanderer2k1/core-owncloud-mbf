/**
 * Package Manager - History JavaScript
 */

(function () {
  "use strict";

  var HistoryManager = {
    historyUrl: OC.generateUrl("/apps/packagemanager/api/history"),
    currentPage: 1,
    currentSearch: "",
    limit: 10,

    init: function () {
      var self = this;

      // Search button
      $("#history-search-btn").on("click", function () {
        self.currentSearch = $("#history-search").val().trim();
        self.currentPage = 1;
        self.load();
      });

      // Reset button
      $("#history-reset-btn").on("click", function () {
        $("#history-search").val("");
        self.currentSearch = "";
        self.currentPage = 1;
        self.load();
      });

      // Enter key in search input
      $("#history-search").on("keydown", function (e) {
        if (e.key === "Enter") {
          self.currentSearch = $(this).val().trim();
          self.currentPage = 1;
          self.load();
        }
      });

      // Pagination
      $("#history-prev").on("click", function () {
        if (self.currentPage > 1) {
          self.currentPage--;
          self.load();
        }
      });

      $("#history-next").on("click", function () {
        self.currentPage++;
        self.load();
      });

      // Initial load
      this.load();
    },

    load: function () {
      var self = this;
      var $tbody = $("#history-list");
      $tbody.html(
        '<tr><td colspan="9" style="text-align:center;padding:20px;">' +
          t("packagemanager", "Loading...") +
          "</td></tr>",
      );

      $.ajax({
        url: this.historyUrl,
        method: "GET",
        data: {
          page: this.currentPage,
          limit: this.limit,
          search: this.currentSearch,
        },
        success: function (response) {
          if (response.status === "success") {
            self.render(response.data, response.pagination);
          } else {
            $tbody.html(
              '<tr><td colspan="9" style="text-align:center;padding:20px;color:#c00;">' +
                escapeHtml(response.message || "Error") +
                "</td></tr>",
            );
          }
        },
        error: function () {
          $tbody.html(
            '<tr><td colspan="9" style="text-align:center;padding:20px;color:#c00;">Error loading history</td></tr>',
          );
        },
      });
    },

    render: function (rows, pagination) {
      var $tbody = $("#history-list");
      $tbody.empty();

      if (!rows || rows.length === 0) {
        $tbody.html(
          '<tr><td colspan="9" style="text-align:center;padding:40px;color:#999;">No history records found.</td></tr>',
        );
      } else {
        var actionLabels = {
          subscribe:
            '<span class="history-badge badge-subscribe">Subscribe</span>',
          extend: '<span class="history-badge badge-extend">Extend</span>',
          change: '<span class="history-badge badge-change">Change</span>',
          cancel: '<span class="history-badge badge-cancel">Cancel</span>',
          auto_expired:
            '<span class="history-badge badge-expired">Expired</span>',
        };

        rows.forEach(function (row) {
          var actionBadge =
            actionLabels[row.action_type] ||
            '<span class="history-badge">' +
              escapeHtml(row.action_type || "-") +
              "</span>";

          var expiryDate = "-";
          if (row.package_expiry_date && row.package_expiry_date > 0) {
            var d = new Date(row.package_expiry_date * 1000);
            var pad = function (n) {
              return String(n).padStart(2, "0");
            };
            expiryDate =
              d.getFullYear() +
              "-" +
              pad(d.getMonth() + 1) +
              "-" +
              pad(d.getDate()) +
              " " +
              pad(d.getHours()) +
              ":" +
              pad(d.getMinutes()) +
              ":" +
              pad(d.getSeconds());
          }

          var createdAt = "-";
          if (row.created_at) {
            createdAt = row.created_at.replace("T", " ").substring(0, 16);
          }

          var durationText = "-";
          if (row.package_duration) {
            durationText =
              row.package_duration + " " + (row.package_unit || "");
          }

          $tbody.append(
            "<tr>" +
              '<td class="history-cell">' +
              escapeHtml(row.user_id || "-") +
              "</td>" +
              '<td class="history-cell">' +
              actionBadge +
              "</td>" +
              '<td class="history-cell">' +
              escapeHtml(row.package_name || "-") +
              "</td>" +
              '<td class="history-cell history-mono">' +
              escapeHtml(row.package_code || "-") +
              "</td>" +
              '<td class="history-cell">' +
              escapeHtml(row.package_quota || "-") +
              "</td>" +
              '<td class="history-cell">' +
              (row.package_price
                ? parseFloat(row.package_price).toLocaleString("vi-VN")
                : "-") +
              "</td>" +
              '<td class="history-cell">' +
              escapeHtml(durationText) +
              "</td>" +
              '<td class="history-cell">' +
              escapeHtml(expiryDate) +
              "</td>" +
              '<td class="history-cell">' +
              escapeHtml(createdAt) +
              "</td>" +
              "</tr>",
          );
        });
      }

      // Update pagination UI
      var total = pagination.total;
      var totalPages = pagination.totalPages;
      var page = pagination.page;

      $("#history-total-info").text(
        "Total: " + total + " record" + (total !== 1 ? "s" : ""),
      );
      $("#history-page-info").text("Page " + page + " / " + (totalPages || 1));
      $("#history-prev").prop("disabled", page <= 1);
      $("#history-next").prop("disabled", page >= totalPages);
    },
  };

  function escapeHtml(text) {
    if (typeof text !== "string") return text;
    var map = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;",
    };
    return text.replace(/[&<>"']/g, function (m) {
      return map[m];
    });
  }

  $(document).ready(function () {
    HistoryManager.init();
  });
})();
