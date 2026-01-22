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

      // Show new package modal
      $("button.new-package").on("click", function (e) {
        e.preventDefault();
        self.showCreateModal();
      });

      // Select all checkbox
      $("#select_all_packages").on("change", function () {
        var checked = $(this).prop("checked");
        $("#fileList .checkbox").prop("checked", checked);
        $("#fileList tr[data-id]").toggleClass("selected", checked);
        self.updateSelectionSummary();
      });

      // Individual row checkbox
      $(document).on("change", "#fileList .checkbox", function () {
        var $row = $(this).closest("tr");
        $row.toggleClass("selected", $(this).prop("checked"));
        self.updateSelectionSummary();
      });

      // Row click to select (like Files app)
      $(document).on("click", "#fileList tr[data-id]", function (e) {
        if ($(e.target).is("input, a, .icon")) return;
        var $checkbox = $(this).find(".checkbox");
        $checkbox.prop("checked", !$checkbox.prop("checked")).trigger("change");
      });

      // Delete package
      $(document).on("click", ".delete-package", function (e) {
        e.preventDefault();
        e.stopPropagation();
        var id = $(this).data("id");
        var name = $(this).data("name");

        if (
          confirm('Are you sure you want to delete package "' + name + '"?')
        ) {
          self.deletePackage(id);
        }
      });

      // Delete selected
      $(document).on("click", ".delete-selected", function (e) {
        e.preventDefault();
        var selectedIds = [];
        $("#fileList tr.selected").each(function () {
          selectedIds.push($(this).data("id"));
        });

        if (selectedIds.length > 0) {
          if (
            confirm(
              "Are you sure you want to delete " +
                selectedIds.length +
                " packages?"
            )
          ) {
            self.deleteMultiplePackages(selectedIds);
          }
        }
      });

      // Edit package
      $(document).on("click", ".edit-package", function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $row = $(this).closest("tr");
        self.makeRowEditable($row);
      });

      // Save edited package
      $(document).on("click", ".save-package", function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $row = $(this).closest("tr");
        self.savePackage($row);
      });

      // Cancel edit
      $(document).on("click", ".cancel-edit", function (e) {
        e.preventDefault();
        e.stopPropagation();
        self.loadPackages();
      });
    },

    showCreateModal: function () {
      var self = this;
      var template = $("#package-form-template").html();
      var $dialog = $(template);

      $("body").append($dialog);

      $dialog.ocdialog({
        closeOnEscape: true,
        modal: true,
        buttons: [
          {
            text: t("packagemanager", "Cancel"),
            click: function () {
              $(this).ocdialog("close");
            },
          },
          {
            text: t("packagemanager", "Save"),
            classes: "primary",
            click: function () {
              self.createPackageFromModal($(this));
            },
            defaultButton: true,
          },
        ],
        close: function () {
          $(this).ocdialog("destroy").remove();
        },
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
      var $tbody = $("#fileList");
      $tbody.find("tr[data-id]").remove();
      $tbody.find(".no-packages, .loading").remove();

      if (packages.length === 0) {
        $tbody.append(
          '<tr class="no-packages"><td colspan="6" style="text-align: center; padding: 40px; color: #999;">No packages found. Create your first package!</td></tr>'
        );
        return;
      }

      packages.forEach(function (pkg) {
        var $row = $('<tr data-id="' + pkg.id + '">');
        var $nameCol = $('<td class="column-name">');
        var $nameContainer = $('<div class="nametext">');
        $nameContainer.append('<input type="checkbox" class="checkbox"> ');
        $nameContainer.append(
          '<span class="innernametext">' + escapeHtml(pkg.name) + "</span>"
        );

        var $actions = $('<div class="fileactions">');
        $actions.append(
          '<a href="#" class="edit-package" data-id="' +
            pkg.id +
            '" title="Edit"><span class="icon icon-rename"></span></a>'
        );
        $actions.append(
          '<a href="#" class="delete-package" data-id="' +
            pkg.id +
            '" data-name="' +
            escapeHtml(pkg.name) +
            '" title="Delete"><span class="icon icon-delete"></span></a>'
        );

        $nameCol.append($nameContainer);

        $row.append($nameCol);
        $row.append(
          '<td class="column-code">' + escapeHtml(pkg.code) + "</td>"
        );
        $row.append(
          '<td class="column-price">' +
            parseFloat(pkg.price).toFixed(2) +
            "</td>"
        );
        $row.append('<td class="column-quota">' + escapeHtml(pkg.quota) + "</td>");
        $row.append('<td class="column-duration">' + pkg.duration + "</td>");
        $row.append(
          '<td class="column-unit">' + escapeHtml(pkg.unit) + "</td>"
        );

        var $actionsCol = $('<td class="column-actions">');
        $actionsCol.append($actions);
        $row.append($actionsCol);

        $tbody.append($row);
      });

      this.updateSelectionSummary();
    },

    updateSelectionSummary: function () {
      var $selectedRows = $("#fileList tr.selected");
      var count = $selectedRows.length;
      var total = $("#fileList tr[data-id]").length;
      var $headerName = $("#headerName .name span:first-child");
      var $selectedActions = $(".selectedActions");
      var $filestable = $("#filestable");

      if (count > 0) {
        $headerName.text(count + " packages selected");
        $selectedActions.removeClass("hidden");
        $filestable.addClass("multiselect");
        $("#modified span:first-child").text("");
      } else {
        $headerName.text("Package name");
        $selectedActions.addClass("hidden");
        $filestable.removeClass("multiselect");
        $("#modified span:first-child").text("Unit");
      }

      var $summary = $(".summary .info");
      $summary.text(total + " Packages");
    },

    createPackageFromModal: function ($dialog) {
      var self = this;
      var $form = $dialog.find("#modal-package-form");
      var formData = {
        name: $form.find('input[name="name"]').val(),
        code: $form.find('input[name="code"]').val(),
        price: $form.find('input[name="price"]').val(),
        quota: $form.find('input[name="quota"]').val(),
        duration: $form.find('input[name="duration"]').val(),
        unit: $form.find('select[name="unit"]').val(),
      };

      if (!formData.name) {
        OC.Notification.showTemporary("Package name are required !");
        return;
      }

      if (!formData.code) {
        OC.Notification.showTemporary("Package code are required !");
        return;
      }

      if (!formData.price) {
        OC.Notification.showTemporary("Price are required !");
        return;
      }

      if (!formData.quota) {
        OC.Notification.showTemporary("Quota are required !");
        return;
      }

      if (!formData.duration) {
        OC.Notification.showTemporary("Duration are required !");
        return;
      }

      $.ajax({
        url: this.baseUrl,
        method: "POST",
        data: formData,
        success: function (response) {
          if (response.status === "success") {
            OC.Notification.showTemporary("Package created successfully");
            $dialog.ocdialog("close");
            self.loadPackages();
          } else {
            OC.Notification.showTemporary("Error: " + response.message);
          }
        },
        error: function () {
          OC.Notification.showTemporary("Package code already existed !");
        },
      });
    },

    makeRowEditable: function ($row) {
      var name = $row.find(".innernametext").text();
      var code = $row.find(".column-code").text();
      var price = $row.find(".column-price").text();
      var quota = $row.find(".column-quota").text();
      var duration = $row.find(".column-duration").text();
      var unit = $row.find(".column-unit").text();

      $row
        .addClass("editing")
        .html(
          '<td class="column-name">' +
            '<div class="nametext">' +
            '<input type="text" class="filename edit-name" value="' +
            escapeHtml(name) +
            '">' +
            "</div>" +
            "</td>" +
            '<td class="column-code"><input type="text" class="edit-code" value="' +
            escapeHtml(code) +
            '"></td>' +
            '<td class="column-price"><input type="number" step="0.01" class="edit-price" value="' +
            price +
            '"></td>' +
            '<td class="column-quota"><input type="text" class="edit-quota" value="' +
            escapeHtml(quota) +
            '"></td>' +
            '<td class="column-duration"><input type="number" class="edit-duration" value="' +
            duration +
            '"></td>' +
            '<td class="column-unit">' +
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
            '<td class="column-actions">' +
            '<div class="fileactions visible" style="display: flex;">' +
            '<a href="#" class="save-package" title="Save"><span class="icon icon-checkmark"></span></a> ' +
            '<a href="#" class="cancel-edit" title="Cancel"><span class="icon icon-close"></span></a>' +
            "</div>" +
            "</td>"
        );
      $row.find(".edit-name").focus();
    },

    savePackage: function ($row) {
      var self = this;
      var id = $row.data("id");
      var formData = {
        name: $row.find(".edit-name").val(),
        code: $row.find(".edit-code").val(),
        price: $row.find(".edit-price").val(),
        quota: $row.find(".edit-quota").val(),
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

    deleteMultiplePackages: function (ids) {
      var self = this;
      var promises = ids.map(function (id) {
        return $.ajax({
          url: self.baseUrl + "/" + id,
          method: "DELETE",
        });
      });

      $.when.apply($, promises).then(
        function () {
          OC.Notification.showTemporary("Packages deleted successfully");
          self.loadPackages();
        },
        function () {
          OC.Notification.showTemporary("Some packages could not be deleted");
          self.loadPackages();
        }
      );
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
