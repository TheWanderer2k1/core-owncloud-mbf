document.addEventListener("DOMContentLoaded", function () {
  // Select the label for the username input
  const userLabel = document.querySelector('label[for="user"]');
  if (userLabel) {
    userLabel.textContent = t("sso_auth", "Email");
  }

  // Select the username input itself to update aria-label and placeholder
  const userInput = document.getElementById("user");
  if (userInput) {
    userInput.setAttribute("aria-label", t("sso_auth", "Email"));
    userInput.setAttribute("placeholder", t("sso_auth", "Email"));
  }
});
