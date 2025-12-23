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
        return new TemplateResponse($this->appName, 'register');
    }

    /**
     * @PublicPage
     */
    public function register(string $email, string $phoneNumber, string $password) {
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
            if ($exists) {
                $parameters = [
                    'email' => $email,
                    'phoneNumber' => $phoneNumber,
                    'message' => 'SSO Account already exists. Please log in to create your MobiDrive account.'
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

            // call api send mail to user with sso account details
            // put this in a thread
            // $this->sendEmail($email, "Your SSO account has been created. Email: $email");

            return new DataResponse(['status' => 'success', 'message' => 'Registration successful. Please return to log in page.'], 200);
        } catch (\Exception $e) {
            $this->logger->error('Registration error: ' . $e->getMessage());
            return new DataResponse(['status' => 'error', 'message' => 'An error occurred during registration'], 500);
        }
    }

    /**
     * @PublicPage
     */
    public function login(string $ssoIdentifier, string $password) {
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

            // call api send mail to user with sso account details
            // put this in a thread
            // $this->sendEmail($email, "Your SSO account has been created. Email: $email");

            return new DataResponse(['status' => 'success', 'message' => 'Drive account created. Please return to main page to login using your SSO credentials.'], 200);
        } catch (\Exception $e) {
            $this->logger->error('Login error: ' . $e->getMessage());
            return new DataResponse(['status' => 'error', 'message' => 'An error occurred during login'], 500);
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

    private function checkSSOAccount(string $email, string $phoneNumber): bool {
        try {
            return true; // check will be implemented later
            $client = $this->http->newClient();
            $url = rtrim($this->ssoUrl, '/') . '/checkAccount';
            $token = $this->getToken();
            if ($token === null) {
                throw new \Exception("Unable to get admin token for SSO");
            }
            $response = $client->post($url, [
                'body' => json_encode([
                    'email' => $email,
                    'phoneNumber' => $phoneNumber
                ]),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $body = (string) $response->getBody();
            $data = json_decode($body, true);
            return isset($data['exists']) ? (bool)$data['exists'] : false;
        } catch (\Throwable $e) {
            $this->logger->error("SSO check account error: " . $e->getMessage());
            return false;
        }
    }

    private function createSSOAccount(string $email, string $phoneNumber, string $password): ?string {
        try {
            $client = $this->http->newClient();
            $url = rtrim($this->ssoUrl, '/') . '/partner/create?clientId=' . urlencode($this->clientId);
            $token = $this->getToken();
            if ($token === null) {
                throw new \Exception("Unable to get admin token for SSO");
            }
            $response = $client->post($url, [
                'body' => json_encode([
                    'email' => $email,
                    'phoneNumber' => $phoneNumber,
                    'password' => $password,
                    'loginType' => 0,
                    'loginTwoFactor' => 0,
                    'status' => 1
                ]),
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
            return $data["result"]["userId"];
        } catch (\Throwable $e) {
            $this->logger->error("SSO create account error: " . $e->getMessage());
            return null;
        }
    }

    private function sendEmail(string $toEmail, string $content): bool {
        // implement email sending logic here
        return true;
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
                $newUser = $this->userManager->createUser($ssoId, $randomPassword);
                if (!$newUser) {
                    throw new \Exception("Failed to create Drive user for SSO id $ssoId");
                }

                // basic config for new user
                if ($email) {
                    $newUser->setEmailAddress($email);
                    $newUser->setDisplayName($email);
                }
                $newUser->setQuota($this->config->getSystemValue('default_user_quota', '15 GB'));
                $defaultGroup = \OC::$server->getGroupManager()->get('default'); // default group must exist first
                $defaultGroup->addUser($newUser);
            }
            return $newUser;
        } catch (\Exception $e) {
            $this->logger->error('Create Drive user error: ' . $e->getMessage());
            return null;
        }
    }
}