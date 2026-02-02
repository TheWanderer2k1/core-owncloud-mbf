$(document).ready(function () {
  $("#packagemanager-config-form").submit(function (event) {
    event.preventDefault();
    var $form = $(this);
    var $submitBtn = $form.find('button[type="submit"]');
    var $originalText = $submitBtn.text();
    var cbsBaseUrl = $form.find("#cbs_api_base_url").val().trim();

    if (cbsBaseUrl === "") {
      OC.Notification.showTemporary(
        t("packagemanager", "CBS base URL is required.")
      );
      return;
    }

    var urlPattern = new RegExp(
      "^(https?:\\/\\/)?" +
        "((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|" +
        "((\\d{1,3}\\.){3}\\d{1,3}))" +
        "(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*" +
        "(\\?[;&a-z\\d%_.~+=-]*)?" +
        "(\\#[-a-z\\d_]*)?$",
      "i"
    );
    if (!urlPattern.test(cbsBaseUrl)) {
      OC.Notification.showTemporary(
        t("packagemanager", "Invalid CBS base URL.")
      );
      return;
    }

    var adminUser = $form.find("#cbs_admin_user").val().trim();
    if (adminUser === "") {
      OC.Notification.showTemporary(
        t("packagemanager", "Admin user is required.")
      );
      return;
    }

    var adminPassword = $form.find("#cbs_admin_pass").val().trim();
    if (adminPassword === "") {
      OC.Notification.showTemporary(
        t("packagemanager", "Admin password is required.")
      );
      return;
    }

    var hashSecretKey = $form.find("#cbs_hash_secret_key").val().trim();
    if (hashSecretKey === "") {
      OC.Notification.showTemporary(
        t("packagemanager", "Hash secret key is required.")
      );
      return;
    }

    var productCode = $form.find("#cbs_product_code").val().trim();
    if (productCode === "") {
      OC.Notification.showTemporary(
        t("packagemanager", "Product code is required.")
      );
      return;
    }

    $submitBtn.text("Saving...").prop("disabled", true);

    $.ajax({
      type: "POST",
      url: $form.attr("action"),
      data: $form.serialize(),
      success: function (response) {
        if (response.status === "success") {
          OC.Notification.showTemporary(
            t("packagemanager", "Saved config successfully.")
          );
        } else {
          OC.Notification.showTemporary(t("packagemanager", "Error saving."));
        }
      },
      error: function (xhr) {
        var response = xhr.responseJSON;
        if (!response && xhr.responseText) {
          try {
            response = JSON.parse(xhr.responseText);
          } catch (e) {
            console.error("Failed to parse error response", e);
          }
        }
        var message =
          response && response.message
            ? response.message
            : t("packagemanager", "Error saving.");
        OC.Notification.showTemporary(message);
      },
      complete: function () {
        $submitBtn.text($originalText).prop("disabled", false);
      },
    });
  });
});
