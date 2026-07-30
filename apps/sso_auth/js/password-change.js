/**
 * Handles the personal password form for SSO-backed users only.
 * The core handler remains untouched for every other backend.
 */
(function ($) {
	'use strict';

	var passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>]).{8,}$/;

	$(document).ready(function () {
		// The core settings script registers its click listener on DOM ready.
		// Run after it, then replace that listener only for an SSO user.
		window.setTimeout(function () {
			var $form = $('#passwordform[data-sso-auth-password-change="true"]');
			if ($form.length === 0) {
				return;
			}

			var $button = $form.find('#passwordbutton');
			var $error = $form.find('#password-error');
			var $success = $form.find('#password-changed');
			var $loading = $('<span>', {
				'class': 'sso-auth-password-loading hidden',
				text: t('sso_auth', 'Changing password…')
			});
			$button.after($loading);

			function showError(message) {
				$error.text(message).removeClass('hidden').addClass('inlineblock');
				$success.removeClass('inlineblock').addClass('hidden');
			}

			function setLoading(isLoading) {
				$button.prop('disabled', isLoading);
				$loading.toggleClass('hidden', !isLoading);
			}

			function logoutThroughCore() {
				// Reuse the exact URL rendered for the header's Log out action.
				// It carries ownCloud's request token and reaches
				// LoginController::logout(), which clears the session, remember-me
				// tokens and the current login token.
				var logoutUrl = $('#logout').attr('href');
				if (!logoutUrl) {
					logoutUrl = OC.generateUrl('/logout');
					if (typeof oc_requesttoken !== 'undefined') {
						logoutUrl += '?requesttoken=' + encodeURIComponent(oc_requesttoken);
					}
				}
				window.location.assign(logoutUrl);
			}

			$button.off('click');
			$form.on('submit', function (event) {
				event.preventDefault();
				var logoutPending = false;

				var oldPassword = $form.find('#pass1').val();
				var newPassword = $form.find('#pass2').val();
				if (oldPassword === '' || newPassword === '') {
					showError(t('sso_auth', 'Please provide the current and new password.'));
					return;
				}
				if (!passwordPattern.test(newPassword)) {
					showError(t('sso_auth', 'Password must be at least 8 characters and contain uppercase, lowercase, number, and special character'));
					return;
				}
				if (oldPassword === newPassword) {
					showError(t('sso_auth', 'The new password cannot be the same as the previous one'));
					return;
				}

				$error.removeClass('inlineblock').addClass('hidden');
				$success.removeClass('inlineblock').addClass('hidden');
				setLoading(true);

				$.post(OC.generateUrl('/settings/personal/changepassword'), $form.serialize())
					.done(function (data) {
						if (data.status !== 'success') {
							showError(data.data && data.data.message ? data.data.message : t('sso_auth', 'Unable to change password'));
							return;
						}

						$success.removeClass('hidden').addClass('inlineblock');
						logoutPending = true;
						$loading.text(t('sso_auth', 'Logging out…'));
						OC.Notification.showTemporary(t('sso_auth', 'Password changed successfully. You will be logged out.'));
						window.setTimeout(function () {
							logoutThroughCore();
						}, 1000);
					})
					.fail(function (response) {
						var data = response.responseJSON;
						showError(data && data.data && data.data.message ? data.data.message : t('sso_auth', 'Unable to change password'));
					})
					.always(function () {
						if (!logoutPending) {
							setLoading(false);
						}
					});
			});
		}, 0);
	});
})(jQuery);
