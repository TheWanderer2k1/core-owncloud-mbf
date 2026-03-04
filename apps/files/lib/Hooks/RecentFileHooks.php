<?php
namespace OCA\Files\Hooks;

use OCA\Files\Service\RecentActivityService;

/**
 * Filesystem hooks to track user file operations into oc_recent_files.
 */
class RecentFileHooks {

	/**
	 * Register all filesystem hooks (static pattern required by OCP\Util::connectHook).
	 */
	public static function connectHooks() {
		\OCP\Util::connectHook('OC_Filesystem', 'post_write', self::class, 'onPostWrite');
		\OCP\Util::connectHook('OC_Filesystem', 'post_rename', self::class, 'onPostRename');
		\OCP\Util::connectHook('OC_Filesystem', 'delete', self::class, 'onPreDelete');
		\OCP\Util::connectHook('OC_Filesystem', 'post_delete', self::class, 'onPostDelete');

		// Share via new EventDispatcher
		$dispatcher = \OC::$server->getEventDispatcher();
		$dispatcher->addListener('share.afterCreate', [self::class, 'onShareCreate']);

		// Share via legacy OC_Hook (fallback for older share paths)
		\OCP\Util::connectHook('OCP\Share', 'post_shared', self::class, 'onLegacyPostShared');
	}

	/**
	 * Hook: file deleted — remove all recent_files entries for this fileid.
	 * Uses post_delete; filecache entry may already be gone so we resolve
	 * fileid via pre_delete and store it in a static buffer.
	 */
	private static $pendingDeleteFileIds = [];

	public static function onPreDelete($params) {
		$path = $params[\OC\Files\Filesystem::signal_param_path] ?? '';
		if ($path === '') return;
		$user = \OC::$server->getUserSession()->getUser();
		if ($user === null) return;
		$uid = $user->getUID();

		$fileId = 0;
		try {
			$view = \OC\Files\Filesystem::getView();
			if ($view !== null) {
				$info = $view->getFileInfo($path);
				if ($info !== false) {
					$fileId = (int)$info->getId();
				}
			}
		} catch (\Exception $e) {}

		if ($fileId <= 0) {
			$internalPath = 'files' . ($path[0] === '/' ? '' : '/') . $path;
			$fileId = self::getService()->resolveFileId($uid, $internalPath);
		}

		if ($fileId > 0) {
			self::$pendingDeleteFileIds[] = $fileId;
		}
	}

	public static function onPostDelete($params) {
		if (empty(self::$pendingDeleteFileIds)) return;
		$db = \OC::$server->getDatabaseConnection();
		foreach (self::$pendingDeleteFileIds as $fileId) {
			$qb = $db->getQueryBuilder();
			$qb->delete('recent_files')
				->where($qb->expr()->eq('fileid', $qb->createNamedParameter($fileId, \PDO::PARAM_INT)));
			$qb->execute();
		}
		self::$pendingDeleteFileIds = [];
	}

	/**
	 * Hook: file written (edit / overwrite upload).
	 */
	public static function onPostWrite($params) {
		$path = $params[\OC\Files\Filesystem::signal_param_path] ?? '';
		if ($path === '') return;
		self::recordByPath($path, 'edit');
	}

	/**
	 * Hook: file renamed.
	 */
	public static function onPostRename($params) {
		$newPath = $params[\OC\Files\Filesystem::signal_param_newpath] ?? '';
		if ($newPath === '') return;
		self::recordByPath($newPath, 'rename');
	}

	/**
	 * Event: share created — record for the sharer.
	 */
	public static function onShareCreate($event) {
		if (!($event instanceof \Symfony\Component\EventDispatcher\GenericEvent)) return;
		$shareObject = $event->getArgument('shareObject');
		if ($shareObject === null) return;

		$user = \OC::$server->getUserSession()->getUser();
		if ($user === null) return;
		$uid = $user->getUID();

		$fileId = 0;
		// Try getNodeId() first (most reliable, no filesystem access needed)
		if (\method_exists($shareObject, 'getNodeId')) {
			try { $fileId = (int)$shareObject->getNodeId(); } catch (\Exception $e) {}
		}
		// Fallback: getNode()->getId()
		if ($fileId <= 0 && \method_exists($shareObject, 'getNode')) {
			try { $fileId = (int)$shareObject->getNode()->getId(); } catch (\Exception $e) {}
		}

		if ($fileId > 0) {
			self::getService()->record($uid, $fileId, 'share');
		}
	}

	/**
	 * Resolve user-relative path to fileid and record.
	 * Path from hooks is relative to user files root, e.g. "/doc.pdf"
	 * Uses Filesystem view so shared/mounted files are resolved correctly.
	 */
	private static function recordByPath(string $path, string $action) {
		$user = \OC::$server->getUserSession()->getUser();
		if ($user === null) return;
		$uid = $user->getUID();

		$fileId = 0;
		// Use Filesystem view — handles shares, external mounts, etc.
		try {
			$view = \OC\Files\Filesystem::getView();
			if ($view !== null) {
				$info = $view->getFileInfo($path);
				if ($info !== false) {
					$fileId = (int)$info->getId();
				}
			}
		} catch (\Exception $e) {
			// ignore
		}

		// Fallback to direct DB lookup on own storage
		if ($fileId <= 0) {
			$internalPath = 'files' . ($path[0] === '/' ? '' : '/') . $path;
			$fileId = self::getService()->resolveFileId($uid, $internalPath);
		}

		if ($fileId > 0) {
			self::getService()->record($uid, $fileId, $action);
		}
	}

	/**
	 * Legacy OC_Hook fallback for share.
	 * $params has keys: itemType, itemSource (fileid), uidOwner, shareWith, etc.
	 */
	public static function onLegacyPostShared($params) {
		$uid = $params['uidOwner'] ?? null;
		$fileId = isset($params['itemSource']) ? (int)$params['itemSource'] : 0;
		if ($uid && $fileId > 0) {
			self::getService()->record($uid, $fileId, 'share');
		}
	}

	private static function getService(): \OCA\Files\Service\RecentActivityService {
		return new \OCA\Files\Service\RecentActivityService(
			\OC::$server->getDatabaseConnection(),
			\OC::$server->getUserSession()
		);
	}
}

