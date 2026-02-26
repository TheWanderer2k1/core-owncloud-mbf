<?php
/**
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license AGPL-3.0
 */

namespace OCA\Files\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Controller for the "Recent" files view.
 * Returns files recently accessed/modified by the current user.
 */
class RecentController extends Controller {
	/** @var IUserSession */
	private $userSession;
	/** @var IDBConnection */
	private $db;

	public function __construct(
		$appName,
		IRequest $request,
		IUserSession $userSession,
		IDBConnection $db
	) {
		parent::__construct($appName, $request);
		$this->userSession = $userSession;
		$this->db = $db;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * Returns files the current user has actually interacted with (view, edit, rename, download, share).
	 * Queries from oc_recent_files JOIN oc_filecache.
	 *
	 * @param int $limit  max number of results (default 100)
	 * @return DataResponse
	 */
	public function getRecentFiles($limit = 100) {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new DataResponse([], \OCP\AppFramework\Http::STATUS_UNAUTHORIZED);
		}

		$uid = $user->getUID();
		$limit = min((int)$limit, 200);

		// Get distinct fileids with max timestamp, ordered by latest activity
		$qb = $this->db->getQueryBuilder();
		$qb->select('rf.fileid')
			->selectAlias($qb->createFunction('MAX(rf.timestamp)'), 'last_activity')
			->from('recent_files', 'rf')
			->where($qb->expr()->eq('rf.uid', $qb->createNamedParameter($uid)))
			->groupBy('rf.fileid')
			->orderBy('last_activity', 'DESC')
			->setMaxResults($limit);

		$result = $qb->execute();
		$recentRows = $result->fetchAll();
		$result->closeCursor();

		if (empty($recentRows)) {
			return new DataResponse(['files' => []]);
		}

		// Collect fileids and their timestamps
		$fileIds = [];
		$timestamps = [];
		foreach ($recentRows as $row) {
			$fid = (int)$row['fileid'];
			$fileIds[] = $fid;
			$timestamps[$fid] = (int)$row['last_activity'];
		}

		// Fetch file metadata from filecache
		$qb3 = $this->db->getQueryBuilder();
		$qb3->select('fc.fileid', 'fc.path', 'fc.name', 'fc.mimetype', 'fc.mimepart',
				'fc.size', 'fc.mtime', 'fc.etag', 'fc.permissions')
			->from('filecache', 'fc')
			->where($qb3->expr()->in('fc.fileid', $qb3->createNamedParameter($fileIds, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)));

		$result3 = $qb3->execute();
		$fileRows = [];
		foreach ($result3->fetchAll() as $row) {
			$fileRows[(int)$row['fileid']] = $row;
		}
		$result3->closeCursor();

		// Also check if any of these files are shared TO this user (for path display)
		$qbShare = $this->db->getQueryBuilder();
		$qbShare->select('s.file_source', 's.file_target')
			->from('share', 's')
			->where($qbShare->expr()->in('s.file_source', $qbShare->createNamedParameter($fileIds, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)))
			->andWhere($qbShare->expr()->orX(
				$qbShare->expr()->andX(
					$qbShare->expr()->eq('s.share_type', $qbShare->createNamedParameter(0)),
					$qbShare->expr()->eq('s.share_with', $qbShare->createNamedParameter($uid))
				)
			));
		$rShare = $qbShare->execute();
		$shareTargets = [];
		foreach ($rShare->fetchAll() as $sRow) {
			$shareTargets[(int)$sRow['file_source']] = $sRow['file_target'];
		}
		$rShare->closeCursor();

		// Build result in order of last_activity DESC
		$files = [];
		foreach ($fileIds as $fid) {
			if (!isset($fileRows[$fid])) continue;
			$row = $fileRows[$fid];

			// Determine display path and name for shared files
			if (isset($shareTargets[$fid])) {
				// Shared file — use file_target for both name and path
				$fileTarget = $shareTargets[$fid];
				$displayName = basename($fileTarget);
				$dirPath = dirname($fileTarget) ?: '/';
			} else {
				// Own file — strip "files" prefix
				$displayName = $row['name'];
				$visiblePath = substr($row['path'], strlen('files'));
				$dirPath = dirname($visiblePath) ?: '/';
			}
			if ($dirPath === '.') $dirPath = '/';

			$entry = $this->buildEntry($fid, $row, $dirPath);
			// Override name for shared files (file_target may differ from filecache name)
			$entry['name'] = $displayName;
			// Override mtime with actual last_activity timestamp
			$entry['mtime'] = $timestamps[$fid];
			$files[] = $entry;
		}

		return new DataResponse(['files' => $files]);
	}

	/**
	 * @NoAdminRequired
	 *
	 * Track a user action on a file (view, download, share).
	 *
	 * @param int    $fileId
	 * @param string $action  one of: view, download, share
	 * @return DataResponse
	 */
	public function trackActivity($fileId, $action) {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new DataResponse([], \OCP\AppFramework\Http::STATUS_UNAUTHORIZED);
		}

		$fileId = (int)$fileId;
		$allowed = ['view', 'download', 'share'];
		if ($fileId <= 0 || !in_array($action, $allowed, true)) {
			return new DataResponse(['error' => 'invalid params'], \OCP\AppFramework\Http::STATUS_BAD_REQUEST);
		}

		$uid = $user->getUID();
		$qb = $this->db->getQueryBuilder();
		$qb->insert('recent_files')
			->values([
				'uid'       => $qb->createNamedParameter($uid),
				'fileid'    => $qb->createNamedParameter($fileId, \PDO::PARAM_INT),
				'timestamp' => $qb->createNamedParameter(\time(), \PDO::PARAM_INT),
				'action'    => $qb->createNamedParameter($action),
			]);
		$qb->execute();

		return new DataResponse(['status' => 'ok']);
	}

	private function buildEntry(int $fileId, array $row, string $dirPath): array {
		return [
			'id'          => $fileId,
			'name'        => $row['name'],
			'path'        => $dirPath,
			'mimetype'    => $this->getMimetypeById((int)$row['mimetype']),
			'mimepart'    => $this->getMimetypeById((int)$row['mimepart']),
			'size'        => (int)$row['size'],
			'mtime'       => (int)$row['mtime'],
			'etag'        => $row['etag'],
			'permissions' => (int)$row['permissions'],
			'type'        => 'file',
		];
	}

	/** @var array<int,string> */
	private $mimetypeCache = [];

	/**
	 * Get numeric mimetype id from string.
	 */
	private function getMimetypeId(string $mimetype): int {
		$qb = $this->db->getQueryBuilder();
		$qb->select('id')
			->from('mimetypes')
			->where($qb->expr()->eq('mimetype', $qb->createNamedParameter($mimetype)));
		$result = $qb->execute();
		$row = $result->fetch();
		$result->closeCursor();
		return $row ? (int)$row['id'] : 0;
	}

	/**
	 * Get mimetype string from numeric id, with in-memory cache.
	 */
	private function getMimetypeById(int $id): string {
		if (isset($this->mimetypeCache[$id])) {
			return $this->mimetypeCache[$id];
		}
		$qb = $this->db->getQueryBuilder();
		$qb->select('mimetype')
			->from('mimetypes')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));
		$result = $qb->execute();
		$row = $result->fetch();
		$result->closeCursor();
		$mime = $row ? $row['mimetype'] : 'application/octet-stream';
		$this->mimetypeCache[$id] = $mime;
		return $mime;
	}
}

