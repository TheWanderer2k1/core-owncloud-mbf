/*
 * Tracks user file interactions (view details, download, share) and
 * silently refreshes the Recent list so files move to the correct
 * time group without flicker or page reload.
 *
 * Works on ALL file lists (main "All files" + Recent).
 */
$(document).ready(function () {

	var trackUrl = OC.generateUrl('/apps/files/api/v1/recent/track');

	function refreshRecentList() {
		var recentList = OCA.Files.RecentPlugin && OCA.Files.RecentPlugin.recentFileList;
		if (recentList) {
			recentList.silentReload();
		}
	}

	function trackAction(fileId, action) {
		if (!fileId || fileId <= 0) return;
		$.ajax({
			url: trackUrl,
			type: 'POST',
			data: {fileId: fileId, action: action},
			headers: {requesttoken: OC.requestToken}
		}).done(function () {
			refreshRecentList();
		});
	}

	// Hook a single FileList instance: wrap _updateDetailsView + Download action
	function hookFileList(fileList) {
		if (!fileList || fileList._recentTrackerHooked) return;
		fileList._recentTrackerHooked = true;

		// 1) Details / view — any click that opens the sidebar
		var origUpdate = fileList._updateDetailsView;
		if (origUpdate) {
			fileList._updateDetailsView = function (fileName, show) {
				if (fileName) {
					var $tr = this.findFileEl(fileName);
					var fid = $tr.length ? parseInt($tr.attr('data-id'), 10) : 0;
					if (fid) trackAction(fid, 'view');
				}
				return origUpdate.apply(this, arguments);
			};
		}

		// 2) Download action
		var fa = fileList.fileActions;
		if (fa && fa.actions && fa.actions.all && fa.actions.all.Download) {
			var origDl = fa.actions.all.Download.action;
			fa.actions.all.Download.action = function (filename, context) {
				var fid = context.$file ? parseInt(context.$file.attr('data-id'), 10) : 0;
				if (fid) trackAction(fid, 'download');
				return origDl.apply(this, arguments);
			};
		}
	}

	// 3) Share — clicking the share tab in the sidebar (works globally)
	$(document).on('click', '.tabHeaders .tabHeader[data-tabid="shareTabView"]', function () {
		var lists = [];
		if (OCA.Files.App && OCA.Files.App.fileList) lists.push(OCA.Files.App.fileList);
		if (OCA.Files.RecentPlugin && OCA.Files.RecentPlugin.recentFileList) lists.push(OCA.Files.RecentPlugin.recentFileList);
		for (var i = 0; i < lists.length; i++) {
			var model = lists[i]._currentFileModel;
			if (model) {
				var fid = model.get('id') || 0;
				if (fid) { trackAction(fid, 'share'); break; }
			}
		}
	});

	// Wait for main fileList, then hook it
	var waitMain = setInterval(function () {
		if (!OCA || !OCA.Files || !OCA.Files.App || !OCA.Files.App.fileList) return;
		clearInterval(waitMain);
		hookFileList(OCA.Files.App.fileList);
	}, 300);

	// Also hook the Recent file list whenever it gets created
	var waitRecent = setInterval(function () {
		if (!OCA || !OCA.Files || !OCA.Files.RecentPlugin) return;
		var rl = OCA.Files.RecentPlugin.recentFileList;
		if (rl) {
			hookFileList(rl);
			clearInterval(waitRecent);
		}
	}, 500);
});

