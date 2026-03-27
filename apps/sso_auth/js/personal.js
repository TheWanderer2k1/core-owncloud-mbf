document.addEventListener("DOMContentLoaded", function() {
    var deleteAccountForm = document.getElementById('delete-account-form');
    if (deleteAccountForm) {
        deleteAccountForm.addEventListener('submit', async function (event) {
            try {
                event.preventDefault();
                if (!confirm(t('sso_auth', 'Are you sure you want to delete your account? This action cannot be undone.'))) {
                    return;
                }
                const response = await fetch(deleteAccountForm.action, {
                    method: 'POST',
                    body: new FormData(deleteAccountForm)
                });
                const result = await response.json();
                if (!response.ok) {
                    OC.Notification.showTemporary(t('sso_auth', result.message || 'Account deletion failed.'));
                    return;
                }
                OC.Notification.showTemporary(t('sso_auth', 'Account deleted successfully. You will be logged out.'));
                setTimeout(() => {
                    window.location.href = "/";
                }, 1500);
            } catch (error) {
                    console.error('Error during account deletion:', error);
                    OC.Notification.showTemporary(t('sso_auth', 'An unexpected error occurred. Please try again later.'));
                }
            }
        );
    }
});