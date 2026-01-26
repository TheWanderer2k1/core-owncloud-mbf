document.addEventListener("DOMContentLoaded", function() {
    var form = document.getElementById('sso-auth-login-form');
    var submitBtn = document.getElementById('sso-auth-login-submit');

    // Password toggle functionality
    var passwordToggle = document.getElementById('password-toggle');
    var passwordInput = document.getElementById('password');

    if (passwordToggle && passwordInput) {
        passwordToggle.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordToggle.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="20" height="20">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>`;
            } else {
                passwordInput.type = 'password';
                passwordToggle.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="20" height="20">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>`;
            }
        });
    }

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