<?php
/**
 * Package Manager - Packages Controller
 */

namespace OCA\PackageManager\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IDBConnection;
use OCP\IL10N;

class PackagesController extends Controller {
	
	private $db;
	private $l;
	
	public function __construct($appName, IRequest $request, IDBConnection $db, IL10N $l) {
		parent::__construct($appName, $request);
		$this->db = $db;
		$this->l = $l;
	}
	
	/**
	 * @NoAdminRequired
	 * Get all packages
	 */
	public function index($offset = 0, $limit = 30) {
		try {
			$qb = $this->db->getQueryBuilder();
			$qb->select('*')
				->from('packagemanager_packages')
				->setMaxResults($limit)
				->setFirstResult($offset);
			
			$result = $qb->execute();
			$packages = $result->fetchAll();
			$result->closeCursor();
			
			return new DataResponse([
				'status' => 'success',
				'data' => $packages
			]);
		} catch (\Exception $e) {
			return new DataResponse([
				'status' => 'error',
				'message' => $e->getMessage()
			], 500);
		}
	}
	
	/**
	 * @NoAdminRequired
	 * Create a new package
	 */
	public function create($name, $code, $price, $quota, $duration, $unit) {
		try {
			// Validate input
			if (empty($name) || empty($code)) {
				return new DataResponse([
					'status' => 'error',
					'message' => $this->l->t('Package name and code are required')
				], 400);
			}
			
			// Check if code already exists
			$qb = $this->db->getQueryBuilder();
			$qb->select('id')
				->from('packagemanager_packages')
				->where($qb->expr()->eq('code', $qb->createNamedParameter($code)));
			
			$result = $qb->execute();
			$exists = $result->fetch();
			$result->closeCursor();
			
			if ($exists) {
				return new DataResponse([
					'status' => 'error',
					'message' => $this->l->t('Package code already exists'),
				], 400);
			}
			
			// Insert new package
			$qb = $this->db->getQueryBuilder();
			$qb->insert('packagemanager_packages')
				->values([
					'name' => $qb->createNamedParameter($name),
					'code' => $qb->createNamedParameter($code),
					'quota' => $qb->createNamedParameter($quota),
					'price' => $qb->createNamedParameter($price),
					'duration' => $qb->createNamedParameter($duration),
					'unit' => $qb->createNamedParameter($unit)
				]);
			
			$qb->execute();
			$id = $qb->getLastInsertId();
			
			return new DataResponse([
				'status' => 'success',
				'data' => [
					'id' => $id,
					'name' => $name,
					'code' => $code,
					'price' => $price,
					'duration' => $duration,
					'unit' => $unit
				]
			]);
		} catch (\Exception $e) {
			return new DataResponse([
				'status' => 'error',
				'message' => $e->getMessage()
			], 500);
		}
	}
	
	/**
	 * @NoAdminRequired
	 * Update a package
	 */
	public function update($id, $name, $code, $price, $quota, $duration, $unit) {
		try {
			// Validate input
			if (empty($name) || empty($code)) {
				return new DataResponse([
					'status' => 'error',
					'message' => $this->l->t('Package name and code are required')
				], 400);
			}
			
			// Check if code already exists for another package
			$qb = $this->db->getQueryBuilder();
			$qb->select('id')
				->from('packagemanager_packages')
				->where($qb->expr()->eq('code', $qb->createNamedParameter($code)))
				->andWhere($qb->expr()->neq('id', $qb->createNamedParameter($id)));
			
			$result = $qb->execute();
			$exists = $result->fetch();
			$result->closeCursor();
			
			if ($exists) {
				return new DataResponse([
					'status' => 'error',
					'message' => $this->l->t('Package code already exists')
				], 400);
			}
			
			// Update package
			$qb = $this->db->getQueryBuilder();
			$qb->update('packagemanager_packages')
				->set('name', $qb->createNamedParameter($name))
				->set('code', $qb->createNamedParameter($code))
				->set('price', $qb->createNamedParameter($price))
				->set('quota', $qb->createNamedParameter($quota))
				->set('duration', $qb->createNamedParameter($duration))
				->set('unit', $qb->createNamedParameter($unit))
				->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));
			
			$qb->execute();
			
			return new DataResponse([
				'status' => 'success',
				'data' => [
					'id' => $id,
					'name' => $name,
					'code' => $code,
					'price' => $price,
					'duration' => $duration,
					'unit' => $unit
				]
			]);
		} catch (\Exception $e) {
			return new DataResponse([
				'status' => 'error',
				'message' => $e->getMessage()
			], 500);
		}
	}
	
	/**
	 * @NoAdminRequired
	 * Delete a package
	 */
	public function destroy($id) {
		try {
			$qb = $this->db->getQueryBuilder();
			$qb->delete('packagemanager_packages')
				->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));
			
			$qb->execute();
			
			return new DataResponse([
				'status' => 'success',
				'message' => $this->l->t('Package deleted successfully')
			]);
		} catch (\Exception $e) {
			return new DataResponse([
				'status' => 'error',
				'message' => $e->getMessage()
			], 500);
		}
	}

	/**
	 * @NoAdminRequired
	 * Get subscription history with pagination and search
	 */
	public function history($page = 1, $limit = 20, $search = '') {
		try {
			$page = max(1, (int)$page);
			$limit = max(1, min(100, (int)$limit));
			$offset = ($page - 1) * $limit;

			$qb = $this->db->getQueryBuilder();

			// Count query
			$countQb = $this->db->getQueryBuilder();
			$countQb->select($countQb->createFunction('COUNT(*) as total'))
				->from('packagemanager_subscription_history');

			$dataQb = $this->db->getQueryBuilder();
			$dataQb->select('*')
				->from('packagemanager_subscription_history')
				->orderBy('created_at', 'DESC')
				->setMaxResults($limit)
				->setFirstResult($offset);

			if (!empty($search)) {
				$searchParam = $dataQb->createNamedParameter('%' . $search . '%');
				$searchParamCount = $countQb->createNamedParameter('%' . $search . '%');

				$dataQb->where(
					$dataQb->expr()->orX(
						$dataQb->expr()->like('user_id', $searchParam),
						$dataQb->expr()->like('package_name', $dataQb->createNamedParameter('%' . $search . '%')),
						$dataQb->expr()->like('package_code', $dataQb->createNamedParameter('%' . $search . '%')),
						$dataQb->expr()->like('action_type', $dataQb->createNamedParameter('%' . $search . '%'))
					)
				);

				$countQb->where(
					$countQb->expr()->orX(
						$countQb->expr()->like('user_id', $searchParamCount),
						$countQb->expr()->like('package_name', $countQb->createNamedParameter('%' . $search . '%')),
						$countQb->expr()->like('package_code', $countQb->createNamedParameter('%' . $search . '%')),
						$countQb->expr()->like('action_type', $countQb->createNamedParameter('%' . $search . '%'))
					)
				);
			}

			$countResult = $countQb->execute();
			$totalRow = $countResult->fetch();
			$countResult->closeCursor();
			$total = (int)($totalRow['total'] ?? 0);

			$dataResult = $dataQb->execute();
			$rows = $dataResult->fetchAll();
			$dataResult->closeCursor();

			return new DataResponse([
				'status' => 'success',
				'data' => $rows,
				'pagination' => [
					'page' => $page,
					'limit' => $limit,
					'total' => $total,
					'totalPages' => (int)ceil($total / $limit),
				],
			]);
		} catch (\Exception $e) {
			return new DataResponse([
				'status' => 'error',
				'message' => $e->getMessage()
			], 500);
		}
	}
}
