<?php

namespace OCA\FAQ\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;
use OCA\FAQ\Controller\FaqController;
use OCA\FAQ\Db\FaqMapper;

class Application extends App {

    public function __construct(array $params = []) {
        parent::__construct('faq', $params);
        $container = $this->getContainer();

        $container->registerService(FaqController::class, function ($c) {
            $server = $c->query('ServerContainer');
            return new FaqController(
                'faq',
                $c->query('Request'),
                $c->query(FaqMapper::class),
                $server->getUserSession()
            );
        });

        $container->registerService(FaqMapper::class, function ($c) {
            $server = $c->query('ServerContainer');
            return new FaqMapper(
                $server->getDb()
            );
        });
    }
}
