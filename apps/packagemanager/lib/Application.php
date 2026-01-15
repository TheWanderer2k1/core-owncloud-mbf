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
		
		// Register services
		$container->registerService('DatabaseConnection', function(IContainer $c) {
			return $c->query('ServerContainer')->getDatabaseConnection();
		});
		
		$container->registerService('L10N', function(IContainer $c) {
			return $c->query('ServerContainer')->getL10N('packagemanager');
		});
	}
}
