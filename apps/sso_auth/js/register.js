document.addEventListener("DOMContentLoaded", function () {
  var form = document.getElementById("sso-auth-register-form");
  var submitBtn = document.getElementById("sso-auth-register-submit");

  function showError(inputId, errorId, message) {
    var input = document.getElementById(inputId);
    var error = document.getElementById(errorId);
    if (input && error) {
      input.style.borderColor = "#ff6b6b";
      error.textContent = message;
      error.style.display = "block";
    }
    return false;
  }

  function clearError(inputId, errorId) {
    var input = document.getElementById(inputId);
    var error = document.getElementById(errorId);
    if (input && error) {
      input.style.borderColor = "";
      error.style.display = "none";
    }
  }

  function clearAllErrors() {
    clearError("email", "email-error");
    clearError("phoneNumber", "phone-error");
    clearError("password", "password-error");
    clearError("confirmPassword", "confirm-password-error");
  }

  ["email", "phoneNumber", "password", "confirmPassword"].forEach(function (
    fieldName
  ) {
    var input = document.querySelector("input[name='" + fieldName + "']");
    if (input) {
      input.addEventListener("input", function () {
        var errorId =
          fieldName === "phoneNumber"
            ? "phone-error"
            : fieldName === "confirmPassword"
            ? "confirm-password-error"
            : fieldName + "-error";
        clearError(fieldName, errorId);
      });
    }
  });

  form.addEventListener("submit", async function (event) {
    try {
      event.preventDefault();
      clearAllErrors();

      let email = form.querySelector("input[name='email']").value.trim();
      let phoneNumber = form
        .querySelector("input[name='phoneNumber']")
        .value.trim();
      let password = form.querySelector("input[name='password']").value;
      let confirmPassword = form.querySelector(
        "input[name='confirmPassword']"
      ).value;

      var hasError = false;

      if (!email) {
        showError("email", "email-error", "Email required !");
        hasError = true;
      } else {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
          showError("email", "email-error", "Email is not valid.");
          hasError = true;
        }
      }

      if (!phoneNumber) {
        showError("phoneNumber", "phone-error", "Phone number is required !");
        hasError = true;
      } else {
        var phoneNumberRegex = /^(?:\+84|0)(3|5|7|8|9)[0-9]{8}$/;
        if (!phoneNumberRegex.test(phoneNumber)) {
          showError(
            "phoneNumber",
            "phone-error",
            "Phone number is not valid. Example: 0912345678"
          );
          hasError = true;
        }
      }

      if (!password) {
        showError("password", "password-error", "Password is required !");
        hasError = true;
      } else {
        var passwordRegex = /^(?=.*[A-Z])(?=.*[^a-zA-Z0-9]).{8,}$/;
        if (!passwordRegex.test(password)) {
          showError(
            "password",
            "password-error",
            "Mật khẩu phải có tối thiểu 8 ký tự, có chữ HOA và ký tự đặc biệt."
          );
          hasError = true;
        }
      }

      if (!confirmPassword) {
        showError(
          "confirmPassword",
          "confirm-password-error",
          "Confirm password is required !"
        );
        hasError = true;
      } else if (password !== confirmPassword) {
        showError(
          "confirmPassword",
          "confirm-password-error",
          "Confirm password is not match !"
        );
        hasError = true;
      }

      if (hasError) {
        return;
      }

      submitBtn.disabled = true;
      let submitBtnSpan = submitBtn.querySelector("span");
      let originalBtnText = submitBtnSpan.textContent;

      // Clear general error
      var generalError = document.getElementById("general-error");
      if (generalError) {
        generalError.style.display = "none";
        generalError.textContent = "";
        generalError.style.borderColor = "#ff6b6b";
        generalError.style.color = "#ff6b6b";
        generalError.style.background = "rgba(255, 107, 107, 0.1)";
      }

      submitBtnSpan.textContent = "Registering...";

      try {
        const response = await fetch(form.action, {
          method: "POST",
          body: new FormData(form),
        });

        submitBtnSpan.textContent = originalBtnText;

        // Handle HTML response (e.g. CSRF error or redirect)
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.includes("text/html")) {
          const html = await response.text();
          document.open();
          document.write(html);
          document.close();
          return;
        }

        const result = await response.json();

        if (!response.ok || result.status === "error") {
          submitBtn.disabled = false;
          let errorMsg = result.message || "Registration failed.";

          if (generalError) {
            generalError.textContent = errorMsg;
            generalError.style.display = "block";
          } else {
            alert(errorMsg);
          }
          return;
        }

        // Success handling
        let successMsg = "Registration successful. You can now log in.";
        if (generalError) {
          generalError.style.borderColor = "#2ecc71";
          generalError.style.color = "#2ecc71";
          generalError.style.background = "rgba(46, 204, 113, 0.1)";
          generalError.textContent = successMsg;
          generalError.style.display = "block";
        }

        setTimeout(() => {
          if (typeof OC !== "undefined" && OC.generateUrl) {
            window.location.href = OC.generateUrl("/login");
          } else {
            window.location.href = "/index.php/login";
          }
        }, 1500);
      } catch (error) {
        submitBtn.disabled = false;
        submitBtnSpan.textContent = originalBtnText;
        console.error("Error during registration:", error);
        let unexpectedError =
          "An unexpected error occurred. Please try again later.";
        if (generalError) {
          generalError.textContent = unexpectedError;
          generalError.style.display = "block";
        } else {
          alert(unexpectedError);
        }
      }
    } catch (e) {
      console.error("Critical registration error:", e);
      submitBtn.disabled = false;
    }
  });
});
