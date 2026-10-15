<?php

namespace OCA\News\AppInfo;

use OCP\AppFramework\App;
use OCA\News\Controller\ConfigController;
use OCP\AppFramework\IAppContainer;
use OCP\IConfig;

class Application extends App {
    public function __construct(array $params = []) {
        parent::__construct('news', $params);
        $container = $this->getContainer();

        $container->registerService(ConfigController::class, function ($c) {
            $server = $c->query('ServerContainer');
            return new ConfigController(
                'news',
                $c->query('Request'),
                $server->getConfig()
            );
        });
    }
}
