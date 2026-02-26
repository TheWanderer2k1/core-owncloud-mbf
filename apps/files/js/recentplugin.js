/*
 * Plugin that registers the "Recent" file list into the Files app navigation.
 */

// HACK: needs to load AFTER the files app (for unit tests)
$(document).ready(function() {
	(function(OCA) {
		/**
		 * @namespace OCA.Files.RecentPlugin
		 */
		OCA.Files.RecentPlugin = {
			name: 'Recent',

			/** @type {OCA.Files.RecentFileList} */
			recentFileList: null,

			attach: function() {
				var self = this;
				$('#app-content-recent').on('show.plugin-recent', function(e) {
					self.showFileList($(e.target));
				});
				$('#app-content-recent').on('hide.plugin-recent', function() {
					self.hideFileList();
				});
			},

			detach: function() {
				if (this.recentFileList) {
					this.recentFileList.destroy();
					OCA.Files.fileActions.off('setDefault.plugin-recent', this._onActionsUpdated);
					OCA.Files.fileActions.off('registerAction.plugin-recent', this._onActionsUpdated);
					$('#app-content-recent').off('.plugin-recent');
					this.recentFileList = null;
				}
			},

			showFileList: function($el) {
				if (!this.recentFileList) {
					this.recentFileList = this._createRecentFileList($el);
				}
				return this.recentFileList;
			},

			hideFileList: function() {
				if (this.recentFileList) {
					this.recentFileList.$fileList.empty();
				}
			},

			_createRecentFileList: function($el) {
				var fileActions = this._createFileActions();
				var list = new OCA.Files.RecentFileList($el, {
					fileActions: fileActions,
					scrollContainer: $('#app-content')
				});
				list.appName = t('files', 'Recent');
				list.$el.find('#emptycontent').html(
					'<div class="icon-recent"></div>' +
					'<h2>' + t('files', 'No recently modified files') + '</h2>' +
					'<p>' + t('files', 'Files you recently modified will show up here') + '</p>'
				);
				return list;
			},

			_createFileActions: function() {
				var fileActions = new OCA.Files.FileActions();
				fileActions.registerDefaultActions();
				fileActions.merge(OCA.Files.fileActions);

				if (!this._globalActionsInitialized) {
					this._onActionsUpdated = _.bind(this._onActionsUpdated, this);
					OCA.Files.fileActions.on('setDefault.plugin-recent', this._onActionsUpdated);
					OCA.Files.fileActions.on('registerAction.plugin-recent', this._onActionsUpdated);
					this._globalActionsInitialized = true;
				}

				// When clicking a folder, navigate to it in the main files view
				fileActions.register('dir', 'Open', OC.PERMISSION_READ, '', function(filename, context) {
					OCA.Files.App.setActiveView('files', {silent: true});
					OCA.Files.App.fileList.changeDirectory(
						OC.joinPaths(context.$file.attr('data-path'), filename), true, true
					);
				});
				fileActions.setDefault('dir', 'Open');

				return fileActions;
			},

			_onActionsUpdated: function(ev) {
				if (!this.recentFileList) {
					return;
				}
				if (ev.action) {
					this.recentFileList.fileActions.registerAction(ev.action);
				} else if (ev.defaultAction) {
					this.recentFileList.fileActions.setDefault(
						ev.defaultAction.mime,
						ev.defaultAction.name
					);
				}
			}
		};
	})(OCA);

	OC.Plugins.register('OCA.Files.App', OCA.Files.RecentPlugin);
});
