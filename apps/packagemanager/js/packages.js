/**
 * Package Manager JavaScript
 */

(function () {
  "use strict";

  var PackageManager = {
    baseUrl: OC.generateUrl("/apps/packagemanager/api/packages"),

    init: function () {
      this.bindEvents();
      this.loadPackages();
    },

    bindEvents: function () {
      var self = this;

      // Show/hide new package form
      $(".button.new").on("click", function (e) {
        e.preventDefault();
        $("#new-package-form").slideToggle();
      });

      // Cancel button
      $(".button.cancel").on("click", function (e) {
        e.preventDefault();
        $("#new-package-form").slideUp();
        $("#package-form")[0].reset();
      });

      // Submit form
      $("#package-form").on("submit", function (e) {
        e.preventDefault();
        self.createPackage();
      });

      // Delete package
      $(document).on("click", ".delete-package", function (e) {
        e.preventDefault();
        var id = $(this).data("id");
        var name = $(this).data("name");

        if (
          confirm('Are you sure you want to delete package "' + name + '"?')
        ) {
          self.deletePackage(id);
        }
      });

      // Edit package (inline editing)
      $(document).on("click", ".edit-package", function (e) {
        e.preventDefault();
        var $row = $(this).closest("tr");
        self.makeRowEditable($row);
      });

      // Save edited package
      $(document).on("click", ".save-package", function (e) {
        e.preventDefault();
        var $row = $(this).closest("tr");
        self.savePackage($row);
      });

      // Cancel edit
      $(document).on("click", ".cancel-edit", function (e) {
        e.preventDefault();
        self.loadPackages();
      });
    },

    loadPackages: function () {
      var self = this;

      $.ajax({
        url: this.baseUrl,
        method: "GET",
        success: function (response) {
          if (response.status === "success") {
            self.renderPackages(response.data);
          } else {
            OC.Notification.showTemporary(
              "Error loading packages: " + response.message
            );
          }
        },
        error: function () {
          OC.Notification.showTemporary("Error loading packages");
        },
      });
    },

    renderPackages: function (packages) {
      var $tbody = $("#packages-list");
      $tbody.empty();

      if (packages.length === 0) {
        $tbody.append(
          '<tr><td colspan="6" class="no-packages">No packages found. Create your first package!</td></tr>'
        );
        return;
      }

      packages.forEach(function (pkg) {
        var $row = $('<tr data-id="' + pkg.id + '">');
        $row.append('<td class="name">' + escapeHtml(pkg.name) + "</td>");
        $row.append('<td class="code">' + escapeHtml(pkg.code) + "</td>");
        $row.append(
          '<td class="price">' + parseFloat(pkg.price).toFixed(2) + "</td>"
        );
        $row.append('<td class="duration">' + pkg.duration + "</td>");
        $row.append('<td class="unit">' + escapeHtml(pkg.unit) + "</td>");
        $row.append(
          '<td class="actions">' +
            '<a href="#" class="edit-package" data-id="' +
            pkg.id +
            '" title="Edit"><span class="icon icon-rename"></span></a> ' +
            '<a href="#" class="delete-package" data-id="' +
            pkg.id +
            '" data-name="' +
            escapeHtml(pkg.name) +
            '" title="Delete"><span class="icon icon-delete"></span></a>' +
            "</td>"
        );
        $tbody.append($row);
      });
    },

    createPackage: function () {
      var self = this;
      var formData = {
        name: $("#package-name").val(),
        code: $("#package-code").val(),
        price: $("#package-price").val(),
        duration: $("#package-duration").val(),
        unit: $("#package-unit").val(),
      };

      $.ajax({
        url: this.baseUrl,
        method: "POST",
        data: formData,
        success: function (response) {
          if (response.status === "success") {
            OC.Notification.showTemporary("Package created successfully");
            $("#package-form")[0].reset();
            $("#new-package-form").slideUp();
            self.loadPackages();
          } else {
            OC.Notification.showTemporary("Error: " + response.message);
          }
        },
        error: function () {
          OC.Notification.showTemporary("Error creating package");
        },
      });
    },

    makeRowEditable: function ($row) {
      var id = $row.data("id");
      var name = $row.find(".name").text();
      var code = $row.find(".code").text();
      var price = $row.find(".price").text();
      var duration = $row.find(".duration").text();
      var unit = $row.find(".unit").text();

      $row.html(
        '<td><input type="text" class="edit-name" value="' +
          escapeHtml(name) +
          '"></td>' +
          '<td><input type="text" class="edit-code" value="' +
          escapeHtml(code) +
          '"></td>' +
          '<td><input type="number" class="edit-price" step="0.01" value="' +
          price +
          '"></td>' +
          '<td><input type="number" class="edit-duration" value="' +
          duration +
          '"></td>' +
          "<td>" +
          '<select class="edit-unit">' +
          '<option value="day"' +
          (unit === "day" ? " selected" : "") +
          ">Day</option>" +
          '<option value="month"' +
          (unit === "month" ? " selected" : "") +
          ">Month</option>" +
          '<option value="year"' +
          (unit === "year" ? " selected" : "") +
          ">Year</option>" +
          "</select>" +
          "</td>" +
          '<td class="actions">' +
          '<a href="#" class="save-package" title="Save"><span class="icon icon-checkmark"></span></a> ' +
          '<a href="#" class="cancel-edit" title="Cancel"><span class="icon icon-close"></span></a>' +
          "</td>"
      );
    },

    savePackage: function ($row) {
      var self = this;
      var id = $row.data("id");
      var formData = {
        id: id,
        name: $row.find(".edit-name").val(),
        code: $row.find(".edit-code").val(),
        price: $row.find(".edit-price").val(),
        duration: $row.find(".edit-duration").val(),
        unit: $row.find(".edit-unit").val(),
      };

      $.ajax({
        url: this.baseUrl + "/" + id,
        method: "PUT",
        data: formData,
        success: function (response) {
          if (response.status === "success") {
            OC.Notification.showTemporary("Package updated successfully");
            self.loadPackages();
          } else {
            OC.Notification.showTemporary("Error: " + response.message);
          }
        },
        error: function () {
          OC.Notification.showTemporary("Error updating package");
        },
      });
    },

    deletePackage: function (id) {
      var self = this;

      $.ajax({
        url: this.baseUrl + "/" + id,
        method: "DELETE",
        success: function (response) {
          if (response.status === "success") {
            OC.Notification.showTemporary("Package deleted successfully");
            self.loadPackages();
          } else {
            OC.Notification.showTemporary("Error: " + response.message);
          }
        },
        error: function () {
          OC.Notification.showTemporary("Error deleting package");
        },
      });
    },
  };

  function escapeHtml(text) {
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
    PackageManager.init();
  });
})();
