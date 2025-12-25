document.addEventListener("DOMContentLoaded", function() {
    var form = document.getElementById('sso-auth-login-form');
    var submitBtn = document.getElementById('sso-auth-login-submit');
    form.addEventListener('submit', async function (event) {
        try {
            event.preventDefault();
            let ssoIdentifier = form.querySelector("input[name='ssoIdentifier']").value;
            let password = form.querySelector("input[name='password']").value;

            // Basic validation
            if (!ssoIdentifier) {
                OC.Notification.showTemporary(t('sso_auth', 'SSO Identifier is required.'));
                return;
            }
            
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            var phoneNumberRegex = /^(?:\+84|0)(3|5|7|8|9)[0-9]{8}$/;
            if (!phoneNumberRegex.test(ssoIdentifier) && !emailRegex.test(ssoIdentifier)) {
                OC.Notification.showTemporary(t('sso_auth', 'Email or phone number is invalid.'));
                return;
            }

            if (!password) {
                OC.Notification.showTemporary(t('sso_auth', 'Password is required.'));
                return;
            }

            var passwordRegex = /^(?=.*[A-Z])(?=.*[^a-zA-Z0-9]).{8,}$/;
            if (!passwordRegex.test(password)) {
                OC.Notification.showTemporary(t('sso_auth', 'Password must contain at least one uppercase letter, one special character, and be at least 8 characters long.'));
                return;
            }

            // If all validations pass, submit the form
            submitBtn.disabled = true;
            let submitBtnSpan = submitBtn.querySelector("span");
            submitBtnSpan.textContent = t('sso_auth', 'Checking...');
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
            });
            const result = await response.json();
            if (!response.ok) {
                submitBtn.disabled = false;
                submitBtnSpan.textContent = t('sso_auth', 'Log in');
                OC.Notification.showTemporary(t('sso_auth', result.message || 'Account creation failed.'));
                return;
            }
            submitBtnSpan.textContent = t('sso_auth', 'Drive account created');
            OC.Notification.showTemporary(t('sso_auth', 'Account created successfully. You can now log in.'));
            setTimeout(() => {
                window.location.href = OC.generateUrl('/login');
            }, 1500);
        } catch (error) {
            console.error('Error during account creation:', error);
            OC.Notification.showTemporary(t('sso_auth', 'An unexpected error occurred. Please try again later.'));
        }
    });
});