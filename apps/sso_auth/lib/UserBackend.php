<?php

namespace OCA\SsoAuth;

use OCA\SsoAuth\Service\CentralAuthService;
use OCP\ILogger;
use OC\User\Database;

class UserBackend extends Database {

    private $centralAuthService;
    private $logger;

    public function __construct(CentralAuthService $centralAuthService, ILogger $logger) {
        parent::__construct();
        $this->centralAuthService = $centralAuthService;
        $this->logger = $logger;
    }

    /**
     * Check if the password is correct without logging in the user
     * @param string $uid The username
     * @param string $password The password
     * @return string|false user uid on success, false otherwise
     */
    public function checkPassword($uid, $password) {
        try {
            $userUid = $this->centralAuthService->loginWithEmailPassword($uid, $password);
            if ($userUid) return $userUid;
            return false;
        } catch (\Throwable $e) {
            $this->logger->error("Error checkPassword: " . $e->getMessage());
        }
        return false;
    }
    
    /**
     * Backend name to be shown in user management
     * @return string the name of the backend to be shown
     */
    public function getBackendName() {
        return 'SSO Authentication';
    }
}