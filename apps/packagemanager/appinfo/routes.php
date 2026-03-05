<?php
/**
 * Package Manager Routes
 */

return [
	'routes' => [
		// Config routes
		['name' => 'config#index', 'url'  => '/config', 'verb' => 'GET'],
        ['name' => 'config#save', 'url'  => '/config/save', 'verb' => 'POST'],

		// Page routes
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'page#history', 'url' => '/history', 'verb' => 'GET'],

		// API routes for packages
		['name' => 'packages#index', 'url' => '/api/packages', 'verb' => 'GET'],
		['name' => 'packages#create', 'url' => '/api/packages', 'verb' => 'POST'],
		['name' => 'packages#update', 'url' => '/api/packages/{id}', 'verb' => 'PUT'],
		['name' => 'packages#destroy', 'url' => '/api/packages/{id}', 'verb' => 'DELETE'],

		// API package registration for AM/KAM flow
		['name' => 'package_registration#register_amkam', 'url' => '/api/internal/register', 'verb' => 'POST'],

		// API package registration for SMS flow
		['name' => 'package_registration#update_package', 'url' => '/api/internal/update_package', 'verb' => 'POST'],

		// API subscription history
		['name' => 'packages#history', 'url' => '/api/history', 'verb' => 'GET'],
	]
];
