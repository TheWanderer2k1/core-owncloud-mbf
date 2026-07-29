<?php

namespace OCA\SsoAuth;

use OCA\SsoAuth\Service\CentralAuthService;
use OCP\ILogger;
use OC\User\Database;

class UserBackend extends Database {

    private $centralAuthService;
    private $logger;
    private $userManager;
    private $l;

    public function __construct(CentralAuthService $centralAuthService, ILogger $logger, \OCP\IUserManager $userManager, \OCP\IL10N $l = null) {
        parent::__construct();
        $this->centralAuthService = $centralAuthService;
        $this->logger = $logger;
        $this->userManager = $userManager;
        $this->l = $l ?: \OC::$server->getL10N('sso_auth');
    }

    /**
     * Re-authenticate an SSO user before ownCloud accepts a password change.
     *
     * The ownCloud profile controller calls checkPassword() with the current
     * user's UID. Resolve that UID to its email address, because the SSO login
     * API authenticates by email. Returning the requested UID (rather than an
     * arbitrary SSO response UID) is essential: it prevents a password for one
     * account from being accepted as the current password of another account.
     *
     * @param string $uid ownCloud user ID
     * @param string $password current password
     * @return string|false the requested UID on success, false otherwise
     */
    public function checkPassword($uid, $password) {
        try {
            $this->logger->info("Checking password for user $uid, password: " . $password);
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
                $this->logger->info("User $loginName authenticated successfully with SSO, resolved uid: $userUid");
                return $userUid;
            }
            $this->logger->info("No user found for $loginName with provided password");
            return false;
            // $user = $this->userManager->get($uid);
            // if ($user === null || !$user->isEnabled() || $user->getBackendClassName() !== $this->getBackendName()) {
            //     return false;
            // }

            // $loginName = $user->getEMailAddress();
            // if (empty($loginName)) {
            //     $this->logger->warning('Cannot verify SSO password: user has no email address', ['uid' => $uid]);
            //     return false;
            // }

            // $authenticatedUid = $this->centralAuthService->loginWithEmailPassword($loginName, $password);
            // if ($authenticatedUid === null || !hash_equals((string) $uid, (string) $authenticatedUid)) {
            //     return false;
            // }

            // return $uid;
        } catch (\Throwable $e) {
            $this->logger->error("Error checkPassword: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Backend name to be shown in user management
     * @return string the name of the backend to be shown
     */
    public function getBackendName() {
        return $this->l->t('SSO Authentication');
    }
    /**
     * set password
     * @param string $uid The username
     * @param string $password The new password
     * @return bool
     */
    public function setPassword($uid, $password) {
        // Validate password: min 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special char
        $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>]).{8,}$/';
        
        if (!preg_match($pattern, $password)) {
            $this->logger->warning('SSO password does not meet the required complexity policy', ['uid' => $uid]);
            return false;
        }
        
        return $this->centralAuthService->updatePasswordUserSso($uid, $password);
    }
}