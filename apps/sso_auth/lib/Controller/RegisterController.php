<?php

namespace OCA\SsoAuth\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IConfig;
use OCP\Http\Client\IClientService;
use OCP\IUserManager;
use OCP\ILogger;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class RegisterController extends Controller {
    private IConfig $config;
    private IClientService $http;
    private IUserManager $userManager;
    private ILogger $logger;
    private string $ssoUrl;
    private string $realmName;
    private string $clientId;
    private string $clientSecret;
    private string $adminUser;
    private string $adminPassword;


    public function __contruct($appName, IRequest $request, IConfig $config, IClientService $http, IUserManager $userManager, ILogger $logger) {
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

    public function register(string $email, string $phoneNumber, string $password) {
        try {
            // validate inputs
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return new DataResponse(['status' => 'error', 'message' => 'Invalid email address'], 400);
            }

            if (empty($phoneNumber)) {
                return new DataResponse(['status' => 'error', 'message' => 'Phone number is required'], 400);
            }

            // maybe implement password regex validation here
            if (empty($password) || strlen($password) < 8) {
                return new DataResponse(['status' => 'error', 'message' => 'Password must be at least 8 characters long'], 400);
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
                ]
                return new TemplateResponse($this->appName, 'login', $parameters);
            }

            // call api create SSO account
            $sso_account = $this->createSSOAccount($email, $phoneNumber, $password);
            if ($sso_account === null || !isset($sso_account['id'])) {
                throw new \Exception("Failed to create SSO account");
            }

            // create OwnCloud user with sso id as uid
            $uid = $sso_account['id'];
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

    public function login(string $identifier, string $password) {
        try {
            // validate inputs
            if (empty($identifier)) {
                return new DataResponse(['status' => 'error', 'message' => 'Email or Phone number is required'], 400);
            }

            // implement check regex for email or phone number
            // 

            if (empty($password)) {
                return new DataResponse(['status' => 'error', 'message' => 'Password is required'], 400);
            }

            $token = $this->getToken($identifier, $password);
            if (!$token) {
                return new DataResponse(['status' => 'error', 'message' => 'Invalid credentials'], 401);
            }
            // decode token to get sso id
            $decoded = (array) JWT::decode($token, new Key($this->clientSecret, 'HS256'));
            $uid = $decoded['sid'] ?? null;
            $email = $decoded['email'] ?? null;
            if (!$uid) {
                return new DataResponse(['status' => 'error', 'message' => 'Invalid token received'], 500);
            }

            // create Drive user
            $newUser = $this->createDriveUserFromSSOId($uid, $email);
            if (!$newUser) {
                throw new \Exception("Failed to create Drive user for SSO id $uid");
            }

            // call api send mail to user with sso account details
            // put this in a thread
            // $this->sendEmail($email, "Your SSO account has been created. Email: $email");

            return new DataResponse(['status' => 'success', 'message' => 'Drive account created. Please return to main page to login.'], 200);
        } catch (\Exception $e) {
            $this->logger->error('Login error: ' . $e->getMessage());
            return new DataResponse(['status' => 'error', 'message' => 'An error occurred during login'], 500);
        }
    }

    private function getToken(string $username = $this->adminUser, string $password = $this->adminPassword): ?string {
        try {
            $client = $this->http->newClient();
            $url = rtrim($this->ssoUrl, '/') . '/token';
            $response = $client->post($url, [
                'body' => json_encode([
                    'username' => $username,
                    'password' => $password,
                    'realmName' => $this->realmName,
                    'clientId' => $this->clientId,
                    'clientSecret' => $this->clientSecret,
                    'grant_type' => 'password'
                ]),
                'headers' => ['Content-Type' => 'application/json']
            ]);
            $body = (string) $response->getBody();
            $data = json_decode($body, true);
            if (!isset($data['access_token'])) return null;
            return $data['access_token'];
        } catch (\Throwable $e) {
            $this->logger->error("SSO token error: " . $e->getMessage());
            return null;
        }
    }

    private function checkSSOAccount(string $email, string $phoneNumber): bool {
        try {
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

    private function createSSOAccount(string $email, string $phoneNumber, string $password) {
        try {
            $client = $this->http->newClient();
            $url = rtrim($this->ssoUrl, '/') . '/createAccount';
            $token = $this->getToken();
            if ($token === null) {
                throw new \Exception("Unable to get admin token for SSO");
            }
            $response = $client->post($url, [
                'body' => json_encode([
                    'email' => $email,
                    'phoneNumber' => $phoneNumber,
                    'password' => $password
                ]),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $body = (string) $response->getBody();
            $data = json_decode($body, true);
            return $data;
        } catch (\Throwable $e) {
            $this->logger->error("SSO create account error: " . $e->getMessage());
            return null;
        }
    }

    private function sendEmail(string $toEmail, string $content): bool {
        // implement email sending logic here
        return true;
    }

    private function createDriveUserFromSSOId(string $ssoId, string $email): ?\OCP\IUser {
        try {
            // double check to ensure user does not already exist
            $newUser = null;
            $existingUser = $this->userManager->get($ssoId);
            if ($existingUser) {
                $this->logger->info("User with uid $ssoId already exists in Drive");
                $newUser = $existingUser;
            } else {
                $randomPassword = '123'; // generate random password here, user should login via SSO account only
                $newUser = $this->userManager->createUser($ssoId, $randomPassword);
                if (!$newUser) {
                    throw new \Exception("Failed to create Drive user for SSO id $ssoId");
                }

                // basic config for new user
                $newUser->setDisplayName($email);
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