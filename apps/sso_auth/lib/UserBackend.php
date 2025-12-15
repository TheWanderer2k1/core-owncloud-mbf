<?php

namespace OCA\SsoAuth;

use OCA\SsoAuth\Service\CentralAuthService;
use OCP\ILogger;
use OC\User\Database;
use OCP\IUserManager;

class UserBackend extends Database {

    private $centralAuthService;
    private $logger;
    private $userManager;

    public function __construct(CentralAuthService $centralAuthService, ILogger $logger, IUserManager $userManager) {
        parent::__construct();
        $this->centralAuthService = $centralAuthService;
        $this->logger = $logger;
        $this->userManager = $userManager;
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
            $this->logger->error('SSO login successs' . $userUid);
            if (!$userUid) return false;

            $this->ensureLocalUser($userUid);

            return $userUid;
        } catch (\Throwable $e) {
            $this->logger->error('SSO login failed' . $e);
            return false;
        }
    }

    private function ensureLocalUser(string $uid): void {
        if ($this->userManager->userExists($uid)) {
            return;
        }

        $this->userManager->createUserFromBackend($uid, $this);
        $this->logger->error("Created local user for SSO uid=$uid");
    }

    
    /**
     * Backend name to be shown in user management
     * @return string the name of the backend to be shown
     */
    public function getBackendName() {
        return 'SSO Auth (Database)';
    }
}
