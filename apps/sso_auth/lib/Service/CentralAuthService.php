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
            $data = json_decode($response->getBody(), true);
            if (!isset($data['access_token'])) return null;
            
            /* Check if user exists in OwnCloud */
            $user = $this->userManager->getByEmail($email);
            if ($user !== null) {
                return $user[0]->getUID();
            }
            return null;
        } catch (\Throwable $e) {
            $this->logger->error("SSO login error: " . $e->getMessage());
            return null;
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