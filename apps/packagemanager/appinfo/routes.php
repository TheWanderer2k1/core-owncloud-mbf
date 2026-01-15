<?php
/**
 * Package Manager Routes
 */

return [
	'routes' => [
		// Page routes
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		
		// API routes for packages
		['name' => 'packages#index', 'url' => '/api/packages', 'verb' => 'GET'],
		['name' => 'packages#create', 'url' => '/api/packages', 'verb' => 'POST'],
		['name' => 'packages#update', 'url' => '/api/packages/{id}', 'verb' => 'PUT'],
		['name' => 'packages#destroy', 'url' => '/api/packages/{id}', 'verb' => 'DELETE'],
	]
];
