/*
 * Recent files list - shows files recently modified/accessed by the user.
 * Similar to Google Drive's "Recent" view, grouped by time period.
 */

/* global moment */
// HACK: needs to load AFTER the files app (for unit tests)
$(document).ready(function() {
	(function(OCA) {
		/**
		 * @class OCA.Files.RecentFileList
		 * @augments OCA.Files.FileList
		 *
		 * @classdesc Recent files list, grouped by time period (Today, Yesterday, etc.)
		 */
		var RecentFileList = function($el, options) {
			this.initialize($el, options);
		};

		RecentFileList.prototype = _.extend({}, OCA.Files.FileList.prototype, {
			id: 'recent',
			appName: t('files', 'Recent'),

			_clientSideSort: true,
			_allowSelection: false,

			/**
			 * @private
			 */
			initialize: function($el, options) {
				OCA.Files.FileList.prototype.initialize.apply(this, arguments);
				if (this.initialized) {
					return;
				}
				// Hide only the checkbox + its label, not the whole th
				this.$el.find('.select-all').hide();
				this.$el.find('label[for="select_all_files"]').hide();
				// Hide sort indicators and disable sort clicks
				this.$el.find('thead th .sort-indicator').hide();
				this.$el.find('thead th').css('cursor', 'default').off('click.filesort');
				OC.Plugins.attach('OCA.Files.RecentFileList', this);
			},

			updateEmptyContent: function() {
				var dir = this.getCurrentDirectory();
				if (dir === '/') {
					this.$el.find('#emptycontent').toggleClass('hidden', !this.isEmpty);
					this.$el.find('#filestable thead th').toggleClass('hidden', this.isEmpty);
					// Re-hide checkbox and sort after toggling header visibility
					this.$el.find('.select-all').hide();
					this.$el.find('label[for="select_all_files"]').hide();
					this.$el.find('thead th .sort-indicator').hide();
				} else {
					OCA.Files.FileList.prototype.updateEmptyContent.apply(this, arguments);
				}
			},

			getDirectoryPermissions: function() {
				return OC.PERMISSION_READ | OC.PERMISSION_DELETE;
			},

			// Disable header sort clicks
			_onClickHeader: function() {},

			// No summary row in Recent view
			_createSummary: function() {
				return {
					calculate: function() {},
					add: function() {},
					remove: function() {},
					update: function() {},
					updateHidden: function() {},
					setFilter: function() {},
					getTotal: function() { return 0; },
					summary: {totalDirs: 0, totalFiles: 0}
				};
			},

			updateStorageStatistics: function() {
				// no-op: no storage info needed for this view
			},

			/**
			 * Returns the time group label for a given mtime (ms).
			 */
			_getTimeGroup: function(mtime) {
				var now = moment();
				var fileTime = moment(mtime);
				var diffDays = now.clone().startOf('day').diff(fileTime.clone().startOf('day'), 'days');

				if (diffDays === 0) return t('files', 'Today');
				if (diffDays === 1) return t('files', 'Yesterday');
				if (diffDays <= 7) return t('files', 'This week');
				if (diffDays <= 30) return t('files', 'This month');
				if (diffDays <= 365) return t('files', 'This year');
				return t('files', 'Older');
			},

			/**
			 * Override _nextPage to inject time-group separator rows.
			 */
			_nextPage: function(animate) {
				var index = this.$fileList.children('tr[data-file]').length,
					count = this.pageSize(),
					hidden, tr, fileData, newTrs = [],
					isAllSelected = this.isAllSelected(),
					showHidden = this._filesConfig.get('showhidden');

				if (index >= this.files.length) {
					return false;
				}

				// Determine last rendered group from existing separators
				var $lastSep = this.$fileList.find('tr.recent-group-header').last();
				var lastGroup = $lastSep.length ? $lastSep.data('group') : null;

				while (count > 0 && index < this.files.length) {
					fileData = this.files[index];
					var group = this._getTimeGroup(fileData.mtime);

					// Inject separator when group changes
					if (group !== lastGroup) {
						var $sep = $('<tr class="recent-group-header">' +
							'<td colspan="10"><span class="recent-group-label">' +
							_.escape(group) + '</span></td></tr>');
						$sep.data('group', group);
						this.$fileList.append($sep);
						lastGroup = group;
					}

					if (this._filter) {
						hidden = fileData.name.toLowerCase().indexOf(this._filter.toLowerCase()) === -1;
					} else {
						hidden = false;
					}
					tr = this._renderRow(fileData, {updateSummary: false, silent: true, hidden: hidden});
					this.$fileList.append(tr);
					if (isAllSelected || this._selectedFiles[fileData.id]) {
						tr.addClass('selected');
						tr.find('.selectCheckBox').prop('checked', true);
					}
					if (animate) {
						tr.addClass('appear transparent');
					}
					newTrs.push(tr);
					index++;
					if (showHidden || !tr.hasClass('hidden-file')) {
						count--;
					}
				}

				// Trigger animations
				if (animate) {
					window.setTimeout(function() {
						$(newTrs).removeClass('transparent');
					}, 0);
				}
				return newTrs;
			},

			/**
			 * Parse raw API response into our file array format.
			 */
			_parseFiles: function(data) {
				if (!data || !data.files) return [];
				var files = _.map(data.files, function(file) {
					return {
						id: file.id,
						name: file.name,
						path: file.path,
						mimetype: file.mimetype,
						mimepart: file.mimepart,
						size: file.size,
						mtime: file.mtime * 1000,
						etag: file.etag,
						permissions: file.permissions,
						type: file.type || 'file',
						icon: OC.MimeType.getIconUrl(file.mimetype),
						extraData: file.path !== '/' ? file.path + '/' + file.name : '/' + file.name
					};
				});
				files.sort(function(a, b) { return b.mtime - a.mtime; });
				return files;
			},

			/**
			 * Reload the list from the server (with loading mask).
			 */
			reload: function() {
				this.showMask();
				if (this._reloadCall) {
					this._reloadCall.abort();
				}

				this._setCurrentDir('/', false);

				this._reloadCall = $.ajax({
					url: OC.generateUrl('/apps/files/api/v1/recent'),
					type: 'GET',
					dataType: 'json'
				});

				var callBack = this.reloadCallback.bind(this);
				return this._reloadCall.then(callBack, callBack);
			},

			reloadCallback: function(data) {
				delete this._reloadCall;
				this.hideMask();
				this.setFiles(this._parseFiles(data));
				return true;
			},

			/**
			 * Silent reload — fetches fresh data and swaps the entire
			 * tbody in a single DOM operation. The old content stays
			 * visible until the new one is ready, so there is no flicker.
			 */
			silentReload: function() {
				if (this._silentReloadCall) {
					this._silentReloadCall.abort();
				}
				var self = this;
				this._silentReloadCall = $.ajax({
					url: OC.generateUrl('/apps/files/api/v1/recent'),
					type: 'GET',
					dataType: 'json'
				});
				this._silentReloadCall.done(function(data) {
					delete self._silentReloadCall;
					var files = self._parseFiles(data);

					// Build a brand-new tbody offscreen
					var $newBody = $('<tbody id="fileList"></tbody>');
					var lastGroup = null;
					for (var i = 0; i < files.length; i++) {
						var fileData = files[i];
						var group = self._getTimeGroup(fileData.mtime);
						if (group !== lastGroup) {
							$newBody.append(
								$('<tr class="recent-group-header">' +
									'<td colspan="10"><span class="recent-group-label">' +
									_.escape(group) + '</span></td></tr>')
									.data('group', group)
							);
							lastGroup = group;
						}
						$newBody.append(
							self._renderRow(fileData, {updateSummary: false, silent: true, hidden: false})
						);
					}

					// Atomic swap — old tbody replaced in one repaint
					var scrollTop = $('#app-content').scrollTop();
					self.$fileList.replaceWith($newBody);
					self.$fileList = $newBody;
					self.files = files;
					self.isEmpty = files.length === 0;
					self.updateEmptyContent();
					self.fileSummary.calculate(files);
					self._selectedFiles = {};
					self._selectionSummary.clear();
					self.updateSelectionSummary();
					$('#app-content').scrollTop(scrollTop);
					self.$fileList.trigger(jQuery.Event('updated'));
				});
				return this._silentReloadCall;
			}
		});

		OCA.Files.RecentFileList = RecentFileList;
	})(OCA);
});


