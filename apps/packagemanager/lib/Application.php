<?php
/**
 * Package Manager Application
 */

namespace OCA\PackageManager;

use OCP\AppFramework\App;
use OCP\IContainer;

class Application extends App {
	
	public function __construct(array $urlParams = []) {
		parent::__construct('packagemanager', $urlParams);
		
		$container = $this->getContainer();
		
		// Register controllers
		$container->registerService('PackagesController', function(IContainer $c) {
			return new \OCA\PackageManager\Controller\PackagesController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('DatabaseConnection'),
				$c->query('L10N')
			);
		});
		
		$container->registerService('PageController', function(IContainer $c) {
			return new \OCA\PackageManager\Controller\PageController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('L10N')
			);
		});

		$container->registerService('PackageRegistrationController', function(IContainer $c) {
			return new \OCA\PackageManager\Controller\PackageRegistrationController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('ServerContainer')->getConfig(),
				$c->query('ServerContainer')->getUserManager(),
				$c->query('PackageMapper'),
				$c->query('SubscriptionStatusMapper'),
				$c->query('SubscriptionHistoryMapper'),
				$c->query('CustomLogService')
			);
		});
		
		// Register services
		$container->registerService('DatabaseConnection', function(IContainer $c) {
			return $c->query('ServerContainer')->getDatabaseConnection();
		});
		
		$container->registerService('L10N', function(IContainer $c) {
			return $c->query('ServerContainer')->getL10N('packagemanager');
		});

		$container->registerService('CustomLogService', function(IContainer $c) {
			return new \OCA\PackageManager\Service\LogService(
				$c->query('ServerContainer')->getConfig()
			);
		});

		$container->registerService('PackageMapper', function(IContainer $c) {
			return new \OCA\PackageManager\Db\PackageMapper(
				$c->query('DatabaseConnection')
			);
		});

		$container->registerService('SubscriptionStatusMapper', function(IContainer $c) {
			return new \OCA\PackageManager\Db\SubscriptionStatusMapper(
				$c->query('DatabaseConnection')
			);
		});

		$container->registerService('SubscriptionHistoryMapper', function(IContainer $c) {
			return new \OCA\PackageManager\Db\SubscriptionHistoryMapper(
				$c->query('DatabaseConnection')
			);
		});
	}
}
