document.addEventListener("DOMContentLoaded", function() {
    var form = document.getElementById('sso-auth-register-form');
    var submitBtn = document.getElementById('sso-auth-register-submit');
    form.addEventListener('submit', async function (event) {
        try {
            event.preventDefault();
            let email = form.querySelector("input[name='email']").value;
            let phoneNumber = form.querySelector("input[name='phoneNumber']").value;
            let password = form.querySelector("input[name='password']").value;
            let confirmPassword = form.querySelector("input[name='confirmPassword']").value;

            // Basic validation
            if (!email) {
                OC.Notification.showTemporary(t('sso_auth', 'Email is required.'));
                return;
            }
            
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                OC.Notification.showTemporary(t('sso_auth', 'Email is invalid.'));
                return;
            }

            if (!phoneNumber) {
                OC.Notification.showTemporary(t('sso_auth', 'Phone number is required.'));
                return;
            }

            var phoneNumberRegex = /^(?:\+84|0)(3|5|7|8|9)[0-9]{8}$/;
            if (!phoneNumberRegex.test(phoneNumber)) {
                OC.Notification.showTemporary(t('sso_auth', 'Phone number is invalid.'));
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

            if (password !== confirmPassword) {
                OC.Notification.showTemporary(t('sso_auth', 'Confirm password do not match the original.'));
                return;
            }

            // If all validations pass, submit the form
            submitBtn.disabled = true;
            let submitBtnSpan = submitBtn.querySelector("span");
            submitBtnSpan.textContent = t('sso_auth', 'Registering...');
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
            });
            submitBtn.disabled = false;
            submitBtnSpan.textContent = t('sso_auth', 'Register');
            const result = await response.json();
            if (!response.ok) {
                OC.Notification.showTemporary(t('sso_auth', result.message || 'Registration failed.'));
                return;
            }

            OC.Notification.showTemporary(t('sso_auth', 'Registration successful. You can now log in.'));
            setTimeout(() => {
                window.location.href = OC.generateUrl('/login');
            }, 1000);
        } catch (error) {
            console.error('Error during registration:', error);
            OC.Notification.showTemporary(t('sso_auth', 'An unexpected error occurred. Please try again later.'));
        }
    });
});