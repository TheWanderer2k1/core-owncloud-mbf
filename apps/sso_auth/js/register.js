document.addEventListener("DOMContentLoaded", function() {
    var form = document.getElementById('sso-auth-register-form');
    var submitBtn = document.getElementById('sso-auth-register-submit');

    function showError(inputId, errorId, message) {
        var input = document.getElementById(inputId);
        var error = document.getElementById(errorId);
        if (input && error) {
            input.style.borderColor = '#ff6b6b';
            error.textContent = message;
            error.style.display = 'block';
        }
        return false;
    }

    function clearError(inputId, errorId) {
        var input = document.getElementById(inputId);
        var error = document.getElementById(errorId);
        if (input && error) {
            input.style.borderColor = '';
            error.style.display = 'none';
        }
    }

    function clearAllErrors() {
        clearError('email', 'email-error');
        clearError('phoneNumber', 'phone-error');
        clearError('password', 'password-error');
        clearError('confirmPassword', 'confirm-password-error');
    }

    ['email', 'phoneNumber', 'password', 'confirmPassword'].forEach(function(fieldName) {
        var input = document.querySelector("input[name='" + fieldName + "']");
        if (input) {
            input.addEventListener('input', function() {
                var errorId = fieldName === 'phoneNumber' ? 'phone-error' :
                             fieldName === 'confirmPassword' ? 'confirm-password-error' :
                             fieldName + '-error';
                clearError(fieldName, errorId);
            });
        }
    });

    form.addEventListener('submit', async function (event) {
        try {
            event.preventDefault();
            clearAllErrors();

            let email = form.querySelector("input[name='email']").value.trim();
            let phoneNumber = form.querySelector("input[name='phoneNumber']").value.trim();
            let password = form.querySelector("input[name='password']").value;
            let confirmPassword = form.querySelector("input[name='confirmPassword']").value;

            var hasError = false;

            if (!email) {
                showError('email', 'email-error', 'Vui lòng nhập email.');
                hasError = true;
            } else {
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    showError('email', 'email-error', 'Email không hợp lệ.');
                    hasError = true;
                }
            }

            if (!phoneNumber) {
                showError('phoneNumber', 'phone-error', 'Vui lòng nhập số điện thoại.');
                hasError = true;
            } else {
                var phoneNumberRegex = /^(?:\+84|0)(3|5|7|8|9)[0-9]{8}$/;
                if (!phoneNumberRegex.test(phoneNumber)) {
                    showError('phoneNumber', 'phone-error', 'Số điện thoại không hợp lệ. Ví dụ: 0912345678');
                    hasError = true;
                }
            }

            if (!password) {
                showError('password', 'password-error', 'Vui lòng nhập mật khẩu.');
                hasError = true;
            } else {
                var passwordRegex = /^(?=.*[A-Z])(?=.*[^a-zA-Z0-9]).{8,}$/;
                if (!passwordRegex.test(password)) {
                    showError('password', 'password-error', 'Mật khẩu phải có tối thiểu 8 ký tự, có chữ HOA và ký tự đặc biệt.');
                    hasError = true;
                }
            }

            if (!confirmPassword) {
                showError('confirmPassword', 'confirm-password-error', 'Vui lòng xác nhận mật khẩu.');
                hasError = true;
            } else if (password !== confirmPassword) {
                showError('confirmPassword', 'confirm-password-error', 'Mật khẩu xác nhận không khớp.');
                hasError = true;
            }

            if (hasError) {
                return;
            }

            submitBtn.disabled = true;
            let submitBtnSpan = submitBtn.querySelector("span");
            submitBtnSpan.textContent = t('sso_auth', 'Registering...');
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
            });
            submitBtnSpan.textContent = t('sso_auth', 'Register');
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.includes("text/html")) {
                const html = await response.text();
                document.open();
                document.write(html);
                document.close();
                return;
            }
            const result = await response.json();
            if (!response.ok) {
                submitBtn.disabled = false;
                OC.Notification.showTemporary(t('sso_auth', result.message || 'Registration failed.'));
                return;
            }

            OC.Notification.showTemporary(t('sso_auth', 'Registration successful. You can now log in.'));
            setTimeout(() => {
                window.location.href = OC.generateUrl('/login');
            }, 1500);
        } catch (error) {
            console.error('Error during registration:', error);
            OC.Notification.showTemporary(t('sso_auth', 'An unexpected error occurred. Please try again later.'));
        }
    });
});