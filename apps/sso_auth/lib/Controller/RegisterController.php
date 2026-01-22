<?php

namespace OCA\SsoAuth\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCA\SsoAuth\Service\LogService;
use OCP\IRequest;
use OCP\IConfig;
use OCP\Http\Client\IClientService;
use OCP\IUserManager;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class RegisterController extends Controller {
    private IConfig $config;
    private IClientService $http;
    private IUserManager $userManager;
    private $logger;
    private string $ssoUrl;
    private string $realmName;
    private string $clientId;
    private string $clientSecret;
    private string $adminUser;
    private string $adminPassword;


    public function __construct($appName, IRequest $request, IConfig $config, IClientService $http, IUserManager $userManager, LogService $logger) {
        parent::__construct($appName, $request);
        $this->config = $config;
        $this->http = $http;
        $this->userManager = $userManager;
        $this->logger = $logger;
        $this->ssoUrl        = $config->getAppValue('sso_auth', 'sso_url', '');
        $this->realmName     = $config->getAppValue('sso_auth', 'realm', '');
        $this->clientId      = $config->getAppValue('sso_auth', 'client_id', '');
        $this->clientSecret  = $config->getAppValue('sso_auth', 'client_secret', '');
        $this->adminUser     = $config->getAppValue('sso_auth', 'admin_user', '');
        $this->adminPassword = $config->getAppValue('sso_auth', 'admin_password', '');
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function index() {
        return new TemplateResponse($this->appName, 'register', [], 'guest');
    }

    /**
     * @PublicPage
     */
    public function register(string $email = null, string $phoneNumber = null, string $password = null) {
        try {
            // validate inputs
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return new DataResponse(['status' => 'error', 'message' => 'Invalid email address'], 400);
            }

            $phonePattern = '/^(?:\+84|0)(3|5|7|8|9)[0-9]{8}$/';
            if (empty($phoneNumber) || !preg_match($phonePattern, $phoneNumber)) {
                return new DataResponse(['status' => 'error', 'message' => 'Invalid phone number'], 400);
            }

            $passwordPattern = '/^(?=.*[A-Z])(?=.*[^a-zA-Z0-9]).{8,}$/';
            if (empty($password) || !preg_match($passwordPattern, $password)) {
                return new DataResponse(['status' => 'error', 'message' => 'Password must contain at least one uppercase letter, one special character, and be at least 8 characters long'], 400);
            }

            // call api check SSO account
            // if account exists, redirect user to login page with message
            // else create account in SSO and Drive
            $exists = $this->checkSSOAccount($email, $phoneNumber);
            $this->logger->error("SSO account already exists for email: $email, phone: $phoneNumber, exists: $exists");
            if ($exists) {
                // $this->logger->error("SSO account already exists for email: $email, phone: $phoneNumber, exists: $exists");
                $parameters = [
                    'email' => $email,
                    'phoneNumber' => $phoneNumber
                ];
                return new TemplateResponse($this->appName, 'login', $parameters);
            }

            // call api create SSO account
            $uid = $this->createSSOAccount($email, $phoneNumber, $password);
            if (!$uid) {
                throw new \Exception("Failed to create SSO account");
            }

            // create OwnCloud user with sso id as uid
            $newUser = $this->createDriveUserFromSSOId($uid, $email);
            if (!$newUser) {
                throw new \Exception("Failed to create Drive user for SSO id $uid");
            }

            return new DataResponse(['status' => 'success', 'message' => 'Registration successful. Please return to log in page.'], 200);
        } catch (\Exception $e) {
            $this->logger->error('Registration error: ' . $e->getMessage());
            return new DataResponse(['status' => 'error', 'message' => 'An error occurred during registration'], 500);
        }
    }

    /**
     * @PublicPage
     */
    public function login(string $ssoIdentifier = null, string $password = null) {
        try {
            // validate inputs
            $phonePattern = '/^(?:\+84|0)(3|5|7|8|9)[0-9]{8}$/';
            if (empty($ssoIdentifier) || (!preg_match($phonePattern, $ssoIdentifier) && !filter_var($ssoIdentifier, FILTER_VALIDATE_EMAIL))) {
                return new DataResponse(['status' => 'error', 'message' => 'Invalid phone number or email'], 400);
            }

            $passwordPattern = '/^(?=.*[A-Z])(?=.*[^a-zA-Z0-9]).{8,}$/';
            if (empty($password) || !preg_match($passwordPattern, $password)) {
                return new DataResponse(['status' => 'error', 'message' => 'Password must contain at least one uppercase letter, one special character, and be at least 8 characters long'], 400);
            }

            $token = $this->getToken($ssoIdentifier, $password);
            if (!$token) {
                return new DataResponse(['status' => 'error', 'message' => 'Invalid credentials'], 401);
            }

            // decode token to get sso id
            $decoded = (array) JWT::jsonDecode(JWT::urlsafeB64Decode(explode('.', $token)[1])); // no signature verification
            $uid = $decoded['sub'] ?? null;
            $email = $decoded['email'] ?? null;
            $this->logger->info("Decoded SSO token for uid: $uid, email: $email");
            if (!$uid) {
                $this->logger->debug("Invalid token payload: " . json_encode($decoded));
                throw new \Exception("Invalid token payload");
            }

            // create Drive user
            $newUser = $this->createDriveUserFromSSOId($uid, $email);
            if (!$newUser) {
                throw new \Exception("Failed to create Drive user for SSO id $uid");
            }

            return new DataResponse(['status' => 'success', 'message' => 'Drive account created. Please return to main page to login using your SSO credentials.'], 200);
        } catch (\Exception $e) {
            $this->logger->error('Login error: ' . $e->getMessage());
            return new DataResponse(['status' => 'error', 'message' => 'An error occurred during login'], 500);
        }
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     */
    public function registerSMS(string $phoneNumber = null, string $quota = null) {
        try {
            // validate input
            $phonePattern = '/^(?:\+84|0)(3|5|7|8|9)[0-9]{8}$/';
            if (empty($phoneNumber) || !preg_match($phonePattern, $phoneNumber)) {
                return new DataResponse(['status' => 'error', 'message' => 'Invalid phone number'], 400);
            }

            // validate quota string here
            $quotaPattern = '/^\d+\sGB$/';
            if (empty($quota) || !preg_match($quotaPattern, $quota)) {
                return new DataResponse(['status' => 'error', 'message' => 'Invalid quota format. Use format like "5 GB"'], 400);
            }

            // call api check SSO account
            // if account exists, extract sso id from result
            // else create account in SSO and Drive
            $uid = $this->checkSSOAccount(null, $phoneNumber);
            if (!$uid) {
                $defaultPassword = 'MbfDrive@2026';
                $uid = $this->createSSOAccount(null, $phoneNumber, $defaultPassword);
                if (!$uid) {
                    throw new \Exception("Failed to create SSO account");
                }
            }

            // create OwnCloud user with sso id as uid
            $newUser = $this->createDriveUserFromSSOId($uid);
            if (!$newUser) {
                throw new \Exception("Failed to create Drive user for SSO id $uid");
            }

            // ensure correct quota for this user
            $newUser->setQuota($quota);

            return new DataResponse([
                'status' => 'success', 
                'message' => 'SMS Registration successful.',
                'account' => [
                    'username' => $phoneNumber,
                    'password' => isset($defaultPassword) ? $defaultPassword : null
                ]
            ], 200);
        } catch (\Exception $e) {
            $this->logger->error('Registration SMS error: ' . $e->getMessage());
            return new DataResponse(['status' => 'error', 'message' => 'An error occurred during sms registration'], 500);
        }
    }

    private function getToken(string $username = null, string $password = null): ?string {
        try {
            $username = $username ?? $this->adminUser;
            $password = $password ?? $this->adminPassword;
            $client = $this->http->newClient();
            $url = rtrim($this->ssoUrl, '/') . '/login';
            $response = $client->post($url, [
                'body' => json_encode([
                    'username' => $username,
                    'password' => $password,
                    'realmName' => $this->realmName,
                    'clientId' => $this->clientId,
                    'clientSecret' => $this->clientSecret
                ]),
                'headers' => ['Content-Type' => 'application/json']
            ]);
            $body = (string) $response->getBody();
            $data = json_decode($body, true);
            if (!isset($data['access_token'])) {
                $this->logger->debug("SSO get token response: " . $body);
                throw new \Exception("No access_token in response");
            }
            return $data['access_token'];
        } catch (\Throwable $e) {
            $this->logger->error("SSO token error: " . $e->getMessage());
            return null;
        }
    }

    private function checkSSOAccount(string $email = null, string $phoneNumber = null): ?string {
        try {
            $client = $this->http->newClient();
            $url = rtrim($this->ssoUrl, '/') . '/user/public/check-email-phone-none-exist';
            $response = $client->post($url, [
                'body' => json_encode([
                    'username' => $email ? $email : '',
                    'phoneNumber' => $phoneNumber ? $phoneNumber : '',
                    'clientId' => $this->clientId,
                    'realmName' => $this->realmName
                ]),
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ]);
            $body = (string) $response->getBody();
            $data = json_decode($body, true);
            if (!isset($data["success"])) {
                $this->logger->debug("SSO check account response: " . $body);
                throw new \Exception("There is no success field in response");
            }
            if (!(bool)$data["success"] && isset($data["result"]["ssoId"])) {
                $this->logger->debug("SSO check account indicates existence: " . $data["result"]["ssoId"]);
                return $data["result"]["ssoId"];
            }
            $this->logger->debug("SSO check account indicates non-existence: " . $body);
            return null;
        } catch (\Throwable $e) {
            $this->logger->error("SSO check account error: " . $e->getMessage());
            return null;
        }
    }

    private function createSSOAccount(string $email = null, string $phoneNumber = null, string $password): ?string {
        try {
            $client = $this->http->newClient();
            $url = rtrim($this->ssoUrl, '/') . '/partner/create?clientId=' . urlencode($this->clientId);
            $token = $this->getToken();
            if ($token === null) {
                throw new \Exception("Unable to get admin token for SSO");
            }
            $body = [
                'password' => $password,
                'loginType' => 0,
                'loginTwoFactor' => 0,
                'status' => 1,
                'isAdmin' => false,
                'tenantCode' => $this->clientId . "-TENANT",
                'domain' => 'https://drive.mobifone.vn',
            ];
            if ($email !== null) {
                $body['email'] = $email;
                $body['registerType'] = 0;
            }
            if ($phoneNumber !== null) {
                $body['phoneNumber'] = $phoneNumber;
                $body['registerType'] = 1;
            }
            $response = $client->post($url, [
                'body' => json_encode($body),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $body = (string) $response->getBody();
            $data = json_decode($body, true);
            if (!$data["success"]) {
                $this->logger->debug("SSO create account response: " . $body);
                throw new \Exception("Response indicates failure");
            }

            // add this user to drive tenant on sso side
            // 200 means ok, else there will be exception
            $ssoUserId = $data["result"]["userId"];
            $url = rtrim($this->ssoUrl, '/') . '/partner/create-tenant?userId=' . urlencode($ssoUserId);
            $body = [
                'clientId' => $this->clientId,
                'tenantCode' => $this->clientId . "-TENANT",
                'domain' => 'https://drive.mobifone.vn',
            ];
            $response = $client->post($url, [
                'body' => json_encode($body),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $this->logger->debug("User $ssoUserId is in tenant " . $this->clientId . "-TENANT");

            return $ssoUserId;
        } catch (\Throwable $e) {
            $this->logger->error("SSO create account error: " . $e->getMessage());
            return null;
        }
    }

    private function createDriveUserFromSSOId(string $ssoId, string $email = null): ?\OCP\IUser {
        try {
            // double check to ensure user does not already exist
            $newUser = null;
            $existingUser = $this->userManager->get($ssoId);
            if ($existingUser) {
                $this->logger->info("User with uid $ssoId already exists in Drive");
                $newUser = $existingUser;
            } else {
                $randomPassword = \OC::$server->getSecureRandom()->generate(10); // generate random password here, user should login via SSO account only
                $userBackend = $this->userManager->getBackend('OCA\SsoAuth\UserBackend');
                $userBackend->createUser($ssoId, $randomPassword);
                $newUser = $this->userManager->createUserFromBackend($ssoId, $randomPassword, $userBackend);
                $this->logger->debug("Created new Drive user with uid $ssoId");
                if (!$newUser) {
                    throw new \Exception("Failed to create Drive user for SSO id $ssoId");
                }

                // basic config for new user
                if ($email) {
                    $newUser->setEmailAddress($email);
                    $newUser->setDisplayName($email);
                }
                $newUser->setQuota($this->config->getSystemValue('default_user_quota', '5 GB'));
                $defaultGroup = \OC::$server->getGroupManager()->get('default'); // default group must exist first
                if ($defaultGroup) {
                    $defaultGroup->addUser($newUser);
                } else {
                    $this->logger->error('Default group does not exist. User created but not added to any group.');
                }
            }
            return $newUser;
        } catch (\Exception $e) {
            $this->logger->error('Create Drive user error: ' . $e->getMessage());
            return null;
        }
    }
}