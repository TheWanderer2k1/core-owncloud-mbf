<?php


namespace OCA\SsoAuth\AppInfo;

use OCA\SsoAuth\Controller\ConfigController;
use OCA\SsoAuth\Controller\RegisterController;
use OCA\SsoAuth\Service\CentralAuthService;
use OCA\SsoAuth\Service\LogService;
use OCP\AppFramework\App;
use OCA\SsoAuth\UserBackend;

class Application extends App {
    public function __construct(array $params = []) {
        parent::__construct('sso_auth', $params);

        $container = $this->getContainer();
        
        $container->registerService(CentralAuthService::class, function ($c) {
            $server = $c->query('ServerContainer');
            return new CentralAuthService(
                $server->getHTTPClientService(),
                $server->getConfig(),
                $server->getLogger(),
                $server->getUserManager(),
            );
        });

        $container->registerService(LogService::class, function ($c) {
            $server = $c->query('ServerContainer');
            return new LogService(
                $server->getConfig()
            );
        });

        /**
         * ConfigController
         */
        $container->registerService(ConfigController::class, function ($c) {
            return new ConfigController(
                'sso_auth',
                $c->query('Request'),
                $c->query('ServerContainer')->getConfig()
            );
        });

        /**
         * RegisterController
         */
        $container->registerService(RegisterController::class, function ($c) {
            $server = $c->query('ServerContainer');
            return new RegisterController(
                'sso_auth',
                $c->query('Request'),
                $server->getConfig(),
                $server->getHTTPClientService(),
                $server->getUserManager(),
                $c->query(LogService::class)
            );
        });

        /**
         * UserBackend (SSO)
         */
        $container->registerService(UserBackend::class, function ($c) {
            $server = $c->query('ServerContainer');
            return new UserBackend(
                $c->query(CentralAuthService::class),
                $server->getLogger(),
                $server->getUserManager()
            );
        });

        /**
         * Register UserBackend into OwnCloud
         */
        $userManager = $container->query('ServerContainer')->getUserManager();
        $userManager->registerBackend(
            $container->query(UserBackend::class)
        );
    }
}