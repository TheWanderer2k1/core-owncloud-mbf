<?php

namespace OCA\SsoAuth;

use OCA\SsoAuth\Service\CentralAuthService;
use OCP\ILogger;
use OC\User\Database;

class UserBackend extends Database {

    private $centralAuthService;
    private $logger;
    private $userManager;

    public function __construct(CentralAuthService $centralAuthService, ILogger $logger, \OCP\IUserManager $userManager) {
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
            // If the uid is not an email (e.g. looks like a UUID or plain username), try to resolve to email
            $loginName = $uid;
            if (strpos($uid, '@') === false) {
                $user = $this->userManager->get($uid);
                if ($user !== null) {
                    $email = $user->getEMailAddress();
                    if (!empty($email)) $loginName = $email;
                }
            }
            $userUid = $this->centralAuthService->loginWithEmailPassword($loginName, $password);
            if ($userUid) {
                try {
                    $db = \OC::$server->getDatabaseConnection();
                    $qb = $db->getQueryBuilder();
                    $qb->update('accounts')
                       ->set('backend', $qb->createNamedParameter(get_class($this)))
                       ->where($qb->expr()->eq('lower_user_id', $qb->createNamedParameter(strtolower($userUid))));
                    $qb->execute();
                } catch (\Throwable $e) {}
                return $userUid;
            }
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
    /**
     * set password
     * @param string $uid The username
     * @param string $password The new password
     * @return bool
     */
    public function setPassword($uid, $password, $currentPassword = null) {
        $this->logger->error("Error setPassword: " . $uid . " - " . $password . " - " . $currentPassword);
        return $this->centralAuthService->updatePasswordUserSso($uid, $password);
    }
}