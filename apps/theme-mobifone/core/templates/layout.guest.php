<?php
$currentAppId = basename(dirname(dirname(dirname(__FILE__))));
$appDir = basename(dirname(dirname(dirname(dirname(__FILE__)))));
$scriptUrl = \OC::$server->getURLGenerator()->linkTo($appDir . "/" . $currentAppId, 'core/js/redirect.js');
?>
<!DOCTYPE html>
<html>
    <head data-requesttoken="<?php p($_['requesttoken']); ?>">
        <meta charset="utf-8">
        <title> <?php p($theme->getTitle()); ?> </title>
    </head>
    <body>
        Redirecting...
        <script src="<?php print_unescaped($scriptUrl); ?>"></script>
    </body>
</html>

