<?php
namespace OCA\Files\Service;

use OCP\IDBConnection;
use OCP\IUserSession;

/**
 * Service to record and query user file operations in oc_recent_files.
 */
class RecentActivityService {
	/** @var IDBConnection */
	private $db;
	/** @var IUserSession */
	private $userSession;

	public function __construct(IDBConnection $db, IUserSession $userSession) {
		$this->db = $db;
		$this->userSession = $userSession;
	}

	/**
	 * Record a user operation on a file.
	 *
	 * @param string $uid     user who performed the action
	 * @param int    $fileId  filecache fileid
	 * @param string $action  one of: view, edit, rename, download, share
	 */
	public function record(string $uid, int $fileId, string $action) {
		if ($fileId <= 0 || $uid === '') {
			return;
		}
		$qb = $this->db->getQueryBuilder();
		$qb->insert('recent_files')
			->values([
				'uid'       => $qb->createNamedParameter($uid),
				'fileid'    => $qb->createNamedParameter($fileId, \PDO::PARAM_INT),
				'timestamp' => $qb->createNamedParameter(\time(), \PDO::PARAM_INT),
				'action'    => $qb->createNamedParameter($action),
			]);
		$qb->execute();
	}

	/**
	 * Record activity for the currently logged-in user.
	 *
	 * @param int    $fileId
	 * @param string $action
	 */
	public function recordForCurrentUser(int $fileId, string $action) {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return;
		}
		$this->record($user->getUID(), $fileId, $action);
	}

	/**
	 * Resolve a user-relative path (e.g. "files/doc.pdf") to a filecache fileid.
	 *
	 * @param string $uid
	 * @param string $path  internal path like "files/something.txt"
	 * @return int  fileid or 0 if not found
	 */
	public function resolveFileId(string $uid, string $path): int {
		$qb = $this->db->getQueryBuilder();
		$qb->select('fc.fileid')
			->from('filecache', 'fc')
			->innerJoin('fc', 'storages', 's', $qb->expr()->eq('fc.storage', 's.numeric_id'))
			->where($qb->expr()->eq('s.id', $qb->createNamedParameter('home::' . $uid)))
			->andWhere($qb->expr()->eq('fc.path', $qb->createNamedParameter($path)))
			->setMaxResults(1);
		$result = $qb->execute();
		$row = $result->fetch();
		$result->closeCursor();
		return $row ? (int)$row['fileid'] : 0;
	}
}

