/**
 * Optional pagination for ownCloud's user-management page.
 *
 * The stock page appends every loaded batch while scrolling. This override
 * keeps one page in the DOM and asks the existing settings endpoint only for
 * that page. It is loaded solely by sso_auth, so disabling the app restores
 * ownCloud's original behaviour.
 */
(function ($) {
	'use strict';

	var PAGE_SIZE = 15;
	var state = {
		page: 0,
		hasNextPage: false,
		isLoading: false,
		totalUsers: null,
		totalScope: null
	};

	function createControls() {
		var $controls = $('<div>', {
			'class': 'sso-auth-user-pagination',
			'aria-label': 'User list pagination'
		});

		$controls.append($('<button>', {
			type: 'button',
			'class': 'sso-auth-user-page-previous',
			text: '‹',
			title: 'Previous page',
			'aria-label': 'Previous page'
		}));
		$controls.append($('<span>', {
			'class': 'sso-auth-user-page-status'
		}));
		$controls.append($('<button>', {
			type: 'button',
			'class': 'sso-auth-user-page-next',
			text: '›',
			title: 'Next page',
			'aria-label': 'Next page'
		}));

		$('#userlist').after($controls);
		return $controls;
	}

	$(document).ready(function () {
		// This app is included on normal ownCloud pages too. Only activate the
		// override where the administration user list exists.
		if (typeof window.UserList === 'undefined' || $('#userlist').length === 0) {
			return;
		}

		var $controls = createControls();
		var originalEmpty = UserList.empty;

		function updateControls() {
			var status = 'Page ' + (state.page + 1);
			if (state.totalUsers !== null) {
				status += ' / ' + Math.ceil(state.totalUsers / PAGE_SIZE) + ' (' + state.totalUsers + ' users)';
			} else {
				status += ' / ?';
			}
			$controls.find('.sso-auth-user-page-status').text(status);
			$controls.find('.sso-auth-user-page-previous').prop('disabled', state.isLoading || state.page === 0);
			$controls.find('.sso-auth-user-page-next').prop('disabled', state.isLoading || !state.hasNextPage);
		}

		function refreshTotal(gid) {
			var pattern = UserList.filter || '';
			var scope = gid + '\u0000' + pattern;
			if (scope === state.totalScope) {
				return;
			}

			state.totalScope = scope;
			state.totalUsers = null;
			updateControls();

			// ownCloud's existing stats endpoint uses countUsers(), which is
			// efficient for the full user list. It cannot provide an accurate
			// count for a group/search filter, so do not display a misleading
			// total in those cases.
			if (gid !== '' || pattern !== '') {
				return;
			}

			$.get(OC.generateUrl('/settings/users/stats')).done(function (result) {
				if (state.totalScope !== scope || typeof result.totalUsers !== 'number') {
					return;
				}
				state.totalUsers = result.totalUsers;
				updateControls();
			});
		}

		function loadPage(gid, page) {
			if (state.isLoading) {
				return;
			}
			if (gid === undefined) {
				gid = '';
			}
			if (gid === '_everyone') {
				gid = '';
			}

			state.isLoading = true;
			state.page = page;
			UserList.currentGid = gid;
			UserList.noMoreEntries = true;
			$userList.addClass('sso-auth-user-list-loading');
			refreshTotal(gid);
			updateControls();

			// One extra record tells us whether Next is available without a
			// separate, expensive count query.
			$.get(
				OC.generateUrl('/settings/users/users'),
				{
					offset: page * PAGE_SIZE,
					limit: PAGE_SIZE + 1,
					gid: gid,
					pattern: UserList.filter
				}
			).done(function (result) {
				var users = result.slice(0, PAGE_SIZE);
				state.hasNextPage = result.length > PAGE_SIZE;

				// Retain only the current page, including after a group or search
				// change. Calling the original avoids resetting pagination state.
				originalEmpty.call(UserList);
				$.each(users, function (index, user) {
					UserList.add(user);
				});
				UserList.offset = (page + 1) * PAGE_SIZE;
			}).fail(function () {
				// Preserve the visible page when a request fails.
				OC.Notification.showTemporary('Unable to load users. Please try again.');
			}).always(function () {
				state.isLoading = false;
				$userList.removeClass('sso-auth-user-list-loading');
				updateControls();
			});
		}

		// Group and search changes call empty() before update() in the stock
		// script, so mark the next load as the first page.
		UserList.empty = function () {
			state.page = 0;
			state.hasNextPage = false;
			originalEmpty.apply(UserList, arguments);
			updateControls();
		};

		// Replace append-on-scroll before ownCloud's ready callback starts its
		// initial request. Existing group/search callers keep using update().
		UserList.update = function (gid) {
			loadPage(gid, state.page);
		};
		UserList._onScroll = function () {};
		UserList.initialUsersToLoad = PAGE_SIZE;
		UserList.perPageUsersToLoad = PAGE_SIZE;
		UserList.usersToLoad = PAGE_SIZE;

		$controls.on('click', '.sso-auth-user-page-previous', function () {
			if (state.page > 0) {
				loadPage(UserList.currentGid, state.page - 1);
			}
		});
		$controls.on('click', '.sso-auth-user-page-next', function () {
			if (state.hasNextPage) {
				loadPage(UserList.currentGid, state.page + 1);
			}
		});

		updateControls();
	});
})(jQuery);
