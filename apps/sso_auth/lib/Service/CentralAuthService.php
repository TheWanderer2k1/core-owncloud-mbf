<?php

namespace OCA\SsoAuth\Service;

use OCP\IConfig;
use OCP\ILogger;
use OCP\Http\Client\IClientService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class CentralAuthService {

    private $http;
    private $config;
    private $userManager;

    public function __construct(IClientService $http, IConfig $config, ILogger $logger, \OCP\IUserManager $userManager) {
        $this->http = $http;
        $this->config = $config;
        $this->logger = $logger;
        $this->userManager = $userManager;

        $this->ssoUrl       = $config->getAppValue('sso_auth', 'sso_url', '');
        $this->realmName    = $config->getAppValue('sso_auth', 'realm', '');
        $this->clientId     = $config->getAppValue('sso_auth', 'client_id', '');
        $this->clientSecret = $config->getAppValue('sso_auth', 'client_secret', '');
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
            $this->logger->error("DATA:  " . json_encode($data));
            if (!isset($data['access_token'])) {
                // Log the specific error from SSO if available
                $errorMessage = isset($data['error_description']) ? $data['error_description'] : 
                                (isset($data['message']) ? $data['message'] : 
                                (isset($data['error']) ? $data['error'] : 'Unknown Error'));
                                
                $this->logger->error("CentralAuthService: Login failed (API returned " . $response->getStatusCode() . "): " . $errorMessage);
                return null;
            }

            // Decode token to verify or extract info if needed, 
            // but primarily we need to check if user exists in OwnCloud
            
            // Assuming the verified email is the one we sent, or extracted from token
            // For now, trust the input email if API returns success (or parse from token if preferred)
            
            // Check if user exists in OwnCloud
            $user = $this->userManager->getByEmail($email);
            
            if ($user !== null) {
                return $user[0]->getUID();
            }
            
            $users = $this->userManager->getByEmail($email);
            if (count($users) > 0) {
                return $users[0]->getUID();
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
