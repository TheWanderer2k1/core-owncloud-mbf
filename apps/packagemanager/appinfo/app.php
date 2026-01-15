<?php
/**
 * Package Manager App
 *
 * @author Admin
 * @copyright 2026
 * @license AGPL-3.0
 */

$app = new \OCA\PackageManager\Application();

// Add navigation entry for admin users
$userSession = \OC::$server->getUserSession();
$groupManager = \OC::$server->getGroupManager();
$user = $userSession->getUser();

if ($user !== null && $groupManager->isAdmin($user->getUID())) {
	$l = \OC::$server->getL10N('packagemanager');
	\OC::$server->getNavigationManager()->add([
		'id' => 'packagemanager',
		'order' => 80,
		'href' => \OC::$server->getURLGenerator()->linkToRoute('packagemanager.page.index'),
		'icon' => \OC::$server->getURLGenerator()->imagePath('packagemanager', 'packages.svg'),
		'name' => $l->t('Packages')
	]);
}
