<?php

namespace OCA\SsoAuth\Service;

use OCP\IConfig;
use OCP\ILogger;
use OCP\Http\Client\IClientService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class CentralAuthService {

    private $http;
    private $userManager;

    private IConfig $config;

    private ILogger $logger;

    private string $ssoUrl;
    private string $realmName;
    private string $clientId;
    private string $clientSecret;
    private string $adminUser;
    private string $adminPassword;

    public function __construct(IClientService $http, IConfig $config, ILogger $logger, \OCP\IUserManager $userManager) {
        $this->http = $http;
        $this->config = $config;
        $this->logger = $logger;
        $this->userManager = $userManager;

        $this->ssoUrl        = $config->getAppValue('sso_auth', 'sso_url', '');
        $this->realmName     = $config->getAppValue('sso_auth', 'realm', '');
        $this->clientId      = $config->getAppValue('sso_auth', 'client_id', '');
        $this->clientSecret  = $config->getAppValue('sso_auth', 'client_secret', '');
        $this->adminUser     = $config->getAppValue('sso_auth', 'admin_user', '');
        $this->adminPassword = $config->getAppValue('sso_auth', 'admin_password', '');
    }

    public function loginWithEmailPassword(string $email, string $password): ?string {
        try {
            $client = $this->http->newClient();
            $url = rtrim($this->ssoUrl, '/') . '/login';
            $response = $client->post($url, [
                'body' => json_encode([
                    'username' => $email,
                    'password' => $password,
                    'realmName' => $this->realmName,
                    'clientId' => $this->clientId,
                    'clientSecret' => $this->clientSecret
                ]),
                'headers' => ['Content-Type' => 'application/json']
            ]);
            $body = (string) $response->getBody();
            $data = json_decode($body, true);
            if (!isset($data['access_token'])) return null;
            /* Check if user exists in OwnCloud */
            $user = $this->userManager->getByEmail($email);
            if ($user !== null && count($user) > 0) {
                $uid = $user[0]->getUID();
                return $uid;
            }
            return null;
        } catch (\Throwable $e) {
            $this->logger->error("SSO login error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Login with Admin credentials to get token
     */
    private function loginWithSso(string $email, string $password) {
        try {
            $client = $this->http->newClient();
            $url = rtrim($this->ssoUrl, '/') . '/login';
            $payload = [
                'username' => $email,
                'password' => $password,
                'realmName' => $this->realmName,
                'clientId' => $this->clientId,
                'clientSecret' => $this->clientSecret
            ];
            $jsonBody = json_encode($payload);
            $response = $client->post($url, [
                'body' => $jsonBody,
                'headers' => ['Content-Type' => 'application/json']
            ]);
            $body = (string) $response->getBody();
            $data = json_decode($body, true);
            return $data;
        } catch (\Throwable $th) {
            throw new \Exception("Admin Login failed: " . $th->getMessage());
        }
    }

    /**
     * Update user password on SSO
     * @param string $ssoId The user's SSO ID (which we treat as email/uid in this context)
     * @param string $newPassword
     * @return bool
     */
    public function updatePasswordUserSso(string $ssoId, string $newPassword): bool {
        try {
            // 1. Login as Admin
            $authData = $this->loginWithSso($this->adminUser, $this->adminPassword);
            $accessToken = $authData['access_token'];

            // 2. Call Update API
            $url = rtrim($this->ssoUrl, '/') . '/partner/update?id=' . urlencode($ssoId);
            $client = $this->http->newClient();
            $response = $client->put($url, [
                'body' => json_encode(['password' => $newPassword]),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $accessToken
                ]
            ]);
            $body = (string) $response->getBody();
            $data = json_decode($body, true);
            if (isset($data['success']) && $data['success'] == true) return true;
            return false;
        } catch (\Throwable $e) {
            $this->logger->error("Failed to update SSO password for $ssoId: " . $e->getMessage());
            return false;
        }
    }

    private function decodeToken(string $token): array {
        $decoded = (array) JWT::decode($token, new Key($this->clientSecret, 'HS256'));

        return [
            'ssoId' => $decoded['sid'] ?? '',
            'email' => $decoded['email'] ?? '',
            'sub'   => $decoded['sub'] ?? ''
        ];
    }
}