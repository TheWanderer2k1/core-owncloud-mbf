<?php
define('OC_CONSOLE', 1);
require_once 'lib/base.php';

$db = \OC::$server->getDatabaseConnection();

echo "=== oc_mounts (first 5 rows) ===" . PHP_EOL;
$r = $db->executeQuery('SELECT * FROM oc_mounts LIMIT 5');
echo json_encode($r->fetchAll(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "=== oc_storages (first 5 rows) ===" . PHP_EOL;
$r2 = $db->executeQuery('SELECT * FROM oc_storages LIMIT 5');
echo json_encode($r2->fetchAll(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "=== filecache root entries (path='' or 'files') ===" . PHP_EOL;
$r3 = $db->executeQuery("SELECT fileid, storage, path, name FROM oc_filecache WHERE path IN ('', 'files') LIMIT 10");
echo json_encode($r3->fetchAll(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "=== filecache recent files (mtime DESC, limit 5) ===" . PHP_EOL;
$r4 = $db->executeQuery("SELECT fileid, storage, path, name, mtime FROM oc_filecache WHERE path LIKE 'files/%' AND path NOT LIKE 'files_trashbin/%' ORDER BY mtime DESC LIMIT 5");
echo json_encode($r4->fetchAll(), JSON_PRETTY_PRINT) . PHP_EOL;

