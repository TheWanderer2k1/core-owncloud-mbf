<?php
/**
 * Package Manager - Package Registration Controller
 */

namespace OCA\PackageManager\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCA\PackageManager\Service\LogService;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\Http\Client\IClientService;
use OCA\PackageManager\Db\PackageMapper;
use OCA\PackageManager\Db\Package;
use OCA\PackageManager\Db\SubscriptionStatusMapper;
use OCA\PackageManager\Db\SubscriptionStatus;
use OCA\PackageManager\Db\SubscriptionHistoryMapper;
use OCA\PackageManager\Db\SubscriptionHistory;
// use exception
use OCP\AppFramework\Db\DoesNotExistException;

class PackageRegistrationController extends Controller {
    private $logger;
    private IUserManager $userManager;
    private IConfig $config;
    private IClientService $http;
    // database mappers
    private PackageMapper $packageMapper;
    private SubscriptionStatusMapper $subscriptionStatusMapper;
    private SubscriptionHistoryMapper $subscriptionHistoryMapper;
    // cbs config
    private string $cbsAdminUser;
    private string $cbsAdminPass;
    private string $cbsApiBaseUrl;
    private string $cbsProductCode;
    private string $cbsHashSecretKey;


    public function __construct($appName, IRequest $request, IConfig $config, IClientService $http, IUserManager $userManager, 
                                PackageMapper $packageMapper, SubscriptionStatusMapper $subscriptionStatusMapper, 
                                SubscriptionHistoryMapper $subscriptionHistoryMapper, LogService $logger) {
        parent::__construct($appName, $request);
        $this->config = $config;
        $this->http = $http;
        $this->userManager = $userManager;
        $this->packageMapper = $packageMapper;
        $this->subscriptionStatusMapper = $subscriptionStatusMapper;
        $this->subscriptionHistoryMapper = $subscriptionHistoryMapper;
        $this->logger = $logger;
        // cbs config
        $this->cbsAdminUser = $this->config->getAppValue($appName, 'cbs_admin_user', '');
        $this->cbsAdminPass = $this->config->getAppValue($appName, 'cbs_admin_pass', '');
        $this->cbsApiBaseUrl = $this->config->getAppValue($appName, 'cbs_api_base_url', '');
        $this->cbsProductCode = $this->config->getAppValue($appName, 'cbs_product_code', '');
        $this->cbsHashSecretKey = $this->config->getAppValue($appName, 'cbs_hash_secret_key', '');
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     */
    public function registerAmkam(int $type = 0, string $timeStamp = '', string $customerId = '', string $customerName = '', 
                            string $token = '', array $listPackage = [], string $customerEmail = '', string $ssoCustomerId = '', 
                            string $tenantCode = '', string $contractNo = ''): DataResponse {
        try {
            // validate input
            if (empty($type) || empty($timeStamp) || empty($customerId) || empty($customerName) ||
                empty($token) || empty($ssoCustomerId) || empty($tenantCode) || empty($contractNo)) {
                return new DataResponse([
                    'status' => 2,
                    'message' => 'Invalid input'
                ], 400);
            }
            if ($type == 6 && empty($listPackage)) {
                return new DataResponse([
                    'status' => 2,
                    'message' => 'List package is required'
                ], 400);
            }
            // validate token
            if (!$this->validateToken($timeStamp, $customerId, $customerName, $token)) {
                return new DataResponse([
                    'status' => 2,
                    'message' => 'Invalid token'
                ], 400);
            }

            if ($type == 1) {
                /* Call complete create contract cbs api */
                $client = $this->http->newClient();
                $url = rtrim($this->cbsApiBaseUrl, '/') . '/api/v1/customer/completeCreateContract';
                $authString = $this->cbsAdminUser . ':' . $this->cbsAdminPass;
                $base64String = base64_encode($authString);
                try {
                   $response = $client->post($url, [
                        'body' => json_encode([
                            'accessLink' => 'https://drive.mobifone.vn',
                            'domain' => 'https://drive.mobifone.vn',
                            'tenantCode' => $tenantCode,
                            'contractNo' => $contractNo,
                            'productCode' => $this->cbsProductCode
                        ]),
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Basic ' . $base64String
                        ]
                    ]);
                return new DataResponse([
                    'status' => 1, 
                    'message' => 'Complete create contract processed'
                ], 200);
                } catch (\Throwable $th) {
                    return new DataResponse([
                        'status' => 2, 
                        'message' => 'Complete contract failed'
                    ], 500);
                }
            } elseif ($type == 6) {
                $packageInfo = $listPackage[0];
                $ssoId = $ssoCustomerId;
                $packageCode = $packageInfo['packageCode'];
                if ($packageInfo['action'] == 1) {
                    /* Register */
                    if (empty($ssoId)) {
                        return new DataResponse([
                            'status' => 2, 
                            'message' => 'ssoCustomerId is required'
                        ], 400);
                    }

                    if (empty($packageCode)) {
                        return new DataResponse([
                            'status' => 2, 
                            'message' => 'Package code is required'
                        ], 400);
                    }
                    /* Get this package info from db */
                    try {
                        $package = $this->packageMapper->findByCode($packageCode);
                    } catch (DoesNotExistException $e) {
                        $this->logger->error("Package not found: " . $packageCode);
                        return new DataResponse([
                            'status' => 2, 
                            'message' => "Package $packageCode not found"
                        ], 404);
                    }
                    /* Check if Drive user already exists in local database */
                    $driveUser = $this->userManager->get($ssoId);
                    if (!$driveUser) {
                        /* Create Drive account from sso id */
                        $driveUser = $this->createDriveUserFromSSOId($ssoId, $customerEmail);
                        if (!$driveUser) {
                            $this->logger->error("Failed to create Drive user for SSO id: " . $ssoId);
                            throw new \Exception("Failed to create Drive user");
                        }
                        /* Modify subscription status */
                        $result = $this->modifySubscriptionStatus($ssoId, $package, 'subscribe');
                        if (!$result) {
                            throw new \Exception("Failed to modify subscription status for new user $ssoId");
                        }
                        /* Update user quota */
                        $driveUser->setQuota($package->getQuota());
                        return new DataResponse([
                            'status' => 1, 
                            'message' => 'Registration successfully'
                        ], 200);
                    }

                    /* Check if Drive user is active */
                    if (!$driveUser->isEnabled()) {
                        /* Check if user's used space is greater than this package quota */
                        $usedSpace = $this->getUserUsedSpace($ssoId);
                        if ($usedSpace === null) {
                            $this->logger->error("Failed to get used space for user $ssoId");
                            throw new \Exception("Failed to get user used space");
                        }
                        $packageQuotaBytes = \OCP\Util::computerFileSize($package->getQuota());
                        if ($usedSpace > $packageQuotaBytes) {
                            $this->logger->debug("User $ssoId used space $usedSpace exceeds package quota $packageQuotaBytes");
                            return new DataResponse([
                                'status' => 2, 
                                'message' => 'Cannot activate account: used space exceeds package quota'
                            ], 400);
                        }

                        /* (Re)activate user */
                        $driveUser->setEnabled(true);

                        /* Add new/update record to subscription_status */
                        $result = $this->modifySubscriptionStatus($ssoId, $package, 'subscribe');
                        if (!$result) {
                            throw new \Exception("Failed to modify subscription status for re-activated user $ssoId");
                        }

                        /* Update user quota */
                        $driveUser->setQuota($package->getQuota());

                        /* Return success */
                        return new DataResponse([
                            'status' => 1, 
                            'message' => 'Registration successful'
                        ], 200);
                    }

                    /* Modify subscription status */
                    $result = $this->modifySubscriptionStatus($ssoId, $package, 'subscribe');
                    if (!$result) {
                        throw new \Exception("Failed to modify subscription status for existing user $ssoId");
                    }

                    /* Update user q    uota */
                    $driveUser->setQuota($package->getQuota());

                    /* Return success */
                    return new DataResponse([
                        'status' => 1,
                        'message' => 'Registration successful'
                    ], 200);
                } elseif ($packageInfo['action'] == 3) {
                    /* Cancel */
                    $result = $this->cancel($ssoId, $packageCode);
                    if (!$result) {
                        return new DataResponse([
                            'status' => 2, 
                            'message' => 'Cancellation failed'
                        ], 400);
                    }
                    return new DataResponse([
                        'status' => 1, 
                        'message' => 'Cancellation successful'
                    ], 200);
                } else {
                    return new DataResponse([
                        'status' => 2, 
                        'message' => 'Only accept action 1 (register) or 3 (cancel)'
                    ], 400);
                }
            } else {
                return new DataResponse([
                    'status' => 2, 
                    'message' => 'Only accept type 1 or 6'
                ], 400);
            }
        } catch (\Throwable $e) {
            $this->logger->error("CBS Register error: " . $e->getMessage());
            return new DataResponse([
                'status' => 2, 
                'message' => 'An error occurred during registration'
            ], 500);
        }
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     */
    public function updatePackage(string $isdn = '', string $serviceCode = '', $groupCode = '', string $packageCode = '', string $commandCode = '',
                                string $regDatetime = '', string $staDatetime = '', string $endDatetime = '', string $expireDatetime = '', string $status = '',
                                string $channel = '', string $charge_price = '', string $message_send = '', string $org_request = '') {
        try {
            // validate input
            if (empty($isdn) || empty($packageCode) || empty($commandCode) || empty($status)) {
                $this->logger->info("SMS registration: Invalid input");
                return new DataResponse([
                    'resultCode' => 0
                ], 400);
            }

            // validate phone number
            $phoneNumber = '0' . ltrim($isdn, '0');
            $phonePattern = '/^(?:\+84|0)(3|5|7|8|9)[0-9]{8}$/';
            if (!preg_match($phonePattern, $phoneNumber)) {
                $this->logger->info("SMS registration: Invalid phone number format: " . $phoneNumber);
                return new DataResponse([
                    'resultCode' => 0
                ], 400);
            }

            // check for SSO account from phone number
            // if account exists, extract sso id from result
            // else create account in SSO and Drive
            $ssoId = $this->checkSSOAccount(null, $phoneNumber);
            if (!$ssoId) {
                $defaultPassword = 'MbfDrive@2026';
                $ssoId = $this->createSSOAccount(null, $phoneNumber, $defaultPassword);
                if (!$ssoId) {
                    throw new \Exception("Failed to create SSO account for phone number $phoneNumber");
                }
            }

            // create Drive user with sso id as uid
            $driveUser = $this->userManager->get($ssoId);
            if (!$driveUser) {
                $driveUser = $this->createDriveUserFromSSOId($ssoId);
                if (!$driveUser) {
                    throw new \Exception("Failed to create Drive user for SSO id $ssoId");
                }
            }

            // check if command code is eq status: 0 means GH, 1 means DK, 3 means HUY 
            $command = explode(' ', $commandCode)[0];
            if (($command == 'GH' && $status != 0) ||
                ($command == 'DK' && $status != 1) ||
                ($command == 'HUY' && $status != 3)) {
                $this->logger->info("SMS registration: Command code and status do not match: command code = " . $commandCode . ", status = " . $status);
                return new DataResponse([
                    'resultCode' => 0
                ], 400);
            }

            // get package info
            try {
                $package = $this->packageMapper->findByCode($packageCode);
            } catch (DoesNotExistException $e) {
                $this->logger->error("Package not found: " . $packageCode);
                return new DataResponse([
                    'resultCode' => 0
                ], 404);
            }

            switch ($status) {
                case 0:
                    $result = $this->extend($ssoId, $package);
                    if (!$result) {
                        // send sms
                        $this->sendSMS($serviceCode, $isdn, "Gia hạn gói " . $package->getName() . " không thành công.");
                        return new DataResponse([
                            'resultCode' => 0
                        ], 400);
                    }
                    $this->sendSMS($serviceCode, $isdn, "Gia hạn gói " . $package->getName() . " thành công.");
                    break;
                case 1:
                    $result = $this->register($ssoId, $package);
                    if (!$result) {
                        // send sms
                        $this->sendSMS($serviceCode, $isdn, "Đăng ký gói " . $package->getName() . " không thành công.");
                        return new DataResponse([
                            'resultCode' => 0
                        ], 400);
                    }
                    if (isset($defaultPassword)) {
                        $this->sendSMS($serviceCode, $isdn, "Đăng ký gói " . $package->getName() . " thành công.", ['user' => $phoneNumber, 'password' => $defaultPassword]);
                    } else {
                        $this->sendSMS($serviceCode, $isdn, "Đăng ký gói " . $package->getName() . " thành công");
                    }
                    break;
                case 3:
                    $result = $this->cancel($ssoId, $packageCode);
                    if (!$result) {
                        // send sms
                        $this->sendSMS($serviceCode, $isdn, "Hủy gói " . $package->getName() . " không thành công.");
                        return new DataResponse([
                            'resultCode' => 0
                        ], 400);
                    }
                    $this->sendSMS($serviceCode, $isdn, "Hủy gói " . $package->getName() . " thành công.");
                    break;
                default:
                    $this->logger->info("SMS registration: Invalid status value: " . $status);
                    return new DataResponse([
                        'resultCode' => 0
                    ], 400);
            }
            return new DataResponse([
                'resultCode' => 1
            ], 200);
        } catch (\Throwable $e) {
            $this->logger->error("updatePackage func error: " . $e->getMessage());
            // send sms
            // code
            return new DataResponse([
                'resultCode' => 0
            ], 500);
        }
    }

    private function register(string $ssoId, Package $package) {
        try {
            $driveUser = $this->userManager->get($ssoId);
            // Check if drive user is active
            if (!$driveUser->isEnabled()) {
                // Check if user's used space is greater than this package quota
                $usedSpace = $this->getUserUsedSpace($ssoId);
                if ($usedSpace === null) {
                    $this->logger->error("Failed to get used space for user $ssoId");
                    throw new \Exception("Failed to get user used space");
                }
                $packageQuotaBytes = \OCP\Util::computerFileSize($package->getQuota());
                if ($usedSpace > $packageQuotaBytes) {
                    $this->logger->debug("User $ssoId used space $usedSpace exceeds package quota $packageQuotaBytes");
                    return false;
                }
                // (Re)activate user
                $driveUser->setEnabled(true);
            }
            // Modify subscription status
            $result = $this->modifySubscriptionStatus($ssoId, $package, 'subscribe');
            if (!$result) {
                throw new \Exception("Failed to modify subscription status for existing user $ssoId");
            }
            // Update user quota
            $driveUser->setQuota($package->getQuota());
            return true;
        } catch (\Throwable $e) {
            $this->logger->error("SMS registration error: " . $e->getMessage());
            throw $e;
        }
    }

    private function extend(string $ssoId, Package $package) {
        try {
            $driveUser = $this->userManager->get($ssoId);
            // Check if drive user is active
            if (!$driveUser->isEnabled()) {
                $this->logger->info("Drive user not enable: " . $ssoId);
                return false;
            }
            try {
                $subscriptionStatus = $this->subscriptionStatusMapper->findByUserId($ssoId);
                // Check if same package name
                if ($subscriptionStatus->getPackageId() != $package->getId()) {
                    $this->logger->info("Subscription package ID " . $subscriptionStatus->getPackageId() . " does not match given package ID " . $package->getId() . " for user $ssoId");
                    return false;
                }
                // Check if user's used space is greater than this package quota
                $usedSpace = $this->getUserUsedSpace($ssoId);
                if ($usedSpace === null) {
                    $this->logger->error("Failed to get used space for user $ssoId");
                    throw new \Exception("Failed to get user used space");
                }
                $packageQuotaBytes = \OCP\Util::computerFileSize($package->getQuota());
                if ($usedSpace > $packageQuotaBytes) {
                    $this->logger->debug("User $ssoId used space $usedSpace exceeds package quota $packageQuotaBytes");
                    return false;
                }
                // Extend package duration
                $actionType = 'extend';
                // calculating duration
                if ($package->getUnit() == 'day') {
                    $durationInSeconds = $package->getDuration() * 86400;
                } elseif ($package->getUnit() == 'month') {
                    $durationInSeconds = $package->getDuration() * 2592000;
                } elseif ($package->getUnit() == 'year') {
                    $durationInSeconds = $package->getDuration() * 31536000;
                } else {
                    throw new \Exception("Invalid package unit: " . $package->getUnit());
                }
                $endAt = $subscriptionStatus->getEndAt() + $durationInSeconds;
                $subscriptionStatus->setEndAt($endAt);
                // active this subscription again in case it was expired
                $subscriptionStatus->setStatus('active');
                $this->subscriptionStatusMapper->update($subscriptionStatus);

                 /* Add subscription history */
                 $subscriptionHistory = new SubscriptionHistory(
                    $subscriptionStatus->getId(),
                    $ssoId,
                    $package->getId(),
                    $actionType,
                    "User $ssoId extended package " . $package->getCode() . " for " . $package->getDuration() . " " . $package->getUnit(),
                    null,
                    $package->getName(),
                    $package->getCode(),
                    $package->getQuota(),
                    $package->getDuration(),
                    $package->getUnit(),
                    $package->getPrice(),
                    $subscriptionStatus->getEndAt()
                );
                $this->subscriptionHistoryMapper->insert($subscriptionHistory);

                 /* Update user quota */
                 $driveUser->setQuota($package->getQuota());
            } catch (DoesNotExistException $e) {
                $this->logger->error("No subscription status found for user $ssoId");
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            $this->logger->error("Extend package error: " . $e->getMessage());
            throw $e;
        }
    }

    private function cancel(string $ssoId, string $packageCode) {
        try {
            /* Validate input */
            if (empty($ssoId)) {
                $this->logger->error("SSO ID is required for cancellation");
                return false;
            }
            if (empty($packageCode)) {
                $this->logger->error("Package code is required for cancellation");
                return false;
            }
            /* Get this package info from db */
            try {
                $package = $this->packageMapper->findByCode($packageCode);
            } catch (DoesNotExistException $e) {
                $this->logger->error("Package not found: " . $packageCode);
                return false;
            }
            /* Check if Drive user already exists */
            $driveUser = $this->userManager->get($ssoId);
            if (!$driveUser) {
                $this->logger->error("Drive user not found for SSO id: " . $ssoId);
                return false;
            }

            /* Check if Drive user is active */
            if (!$driveUser->isEnabled()) {
                $this->logger->error("Drive user $ssoId is already disabled");
                return false;
            }

            /* Get subscription status */
            try {
                $subscriptionStatus = $this->subscriptionStatusMapper->findByUserId($ssoId);
            } catch (DoesNotExistException $e) {
                $this->logger->error("No subscription status found for user $ssoId");
                return false;
            }
            
            /* Check if this subscription corresponds to the given package */
            if ($subscriptionStatus->getPackageId() != $package->getId()) {
                $this->logger->error("Subscription package ID " . $subscriptionStatus->getPackageId() . " does not match given package ID " . $package->getId() . " for user $ssoId");
                return false;
            }

            /* Check if subscription is still active */
            if ($subscriptionStatus->getStatus() != 'active') {
                $this->logger->error("Subscription for user $ssoId is not active");
                return true;
            }

            /* Set subscription status to cancelled */
            $subscriptionStatus->setStatus('cancelled');
            $this->subscriptionStatusMapper->update($subscriptionStatus);

            /* Add subscription history */
            $subscriptionHistory = new SubscriptionHistory(
                $subscriptionStatus->getId(),
                $ssoId,
                $package->getId(),
                'cancel',
                "User $ssoId cancelled package " . $package->getCode(),
                null,
                $package->getName(),
                $package->getCode(),
                $package->getQuota(),
                $package->getDuration(),
                $package->getUnit(),
                $package->getPrice(),
                $subscriptionStatus->getEndAt()
            );
            $this->subscriptionHistoryMapper->insert($subscriptionHistory);

            /* Set user quota to default */
            $driveUser->setQuota($this->config->getSystemValue('default_user_quota', '5 GB'));

            /* Check if user's used space is greater than default quota */
            $usedSpace = $this->getUserUsedSpace($ssoId);
            $defaultQuotaBytes = \OCP\Util::computerFileSize($this->config->getSystemValue('default_user_quota', '5 GB'));
            if ($usedSpace === null || $usedSpace > $defaultQuotaBytes) {
                $this->logger->debug("User $ssoId used space $usedSpace exceeds default quota $defaultQuotaBytes");
                // disable user account
                $driveUser->setEnabled(false);
                return true;
            }

            return true;
        } catch (\Throwable $e) {
            $this->logger->error("CBS cancellation error: " . $e->getMessage());
            throw $e;
        }
    }

    private function sendSMS(string $serviceCode, string $isdn, string $content, string $optional) {
        try {
            $client = $this->http->newClient();
            $url = rtrim($this->cbsApiBaseUrl, '/') . '/ws/soap/vasp/sendmessage';
            $soapXML = '<?xml version="1.0" encoding="UTF-8"?>' .
                        '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" ' .
                        'xmlns:obj="http://object.app.telsoft/">' .
                            '<soapenv:Header/>' .
                            '<soapenv:Body>' .
                                '<obj:sendMessage>' .
                                    '<ServiceCode>' . $serviceCode . '</ServiceCode>' .
                                    '<ISDN>' . $isdn . '</ISDN>' .
                                    '<Content>' . $content . '</Content>';
                                    
            if (isset($optional['brandname'])) {
                $soapXML .= '<Brandname>' . $optional['brandname'] . '</Brandname>';
            }
            if (isset($optional['user'])) {
                $soapXML .= '<User>' . $optional['user'] . '</User>';
            }
            if (isset($optional['password'])) {
                $soapXML .= '<Password>' . $optional['password'] . '</Password>';
            }
            $soapXML .= '</obj:sendMessage>' .
                        '</soapenv:Body>' .
                    '</soapenv:Envelope>';

            $response = $client->post($url, [
                'body' => $soapXML,
                'headers' => [
                    'Content-Type' => 'text/xml; charset=UTF-8',
                    'SOAPAction'   => '""'
                ]
            ]);
            $body = (string) $response->getBody();
            $data = json_decode($body, true);
            if (isset($data['resultCode']) && $data['resultCode'] == 'OK') {
                $this->logger->info("SMS sent successfully to $isdn with content: $content");
            } else {
                $this->logger->error("Failed to send SMS to $isdn. Response: " . $body);
            }
        } catch (\Throwable $e) {
            $this->logger->error("Send SMS error: " . $e->getMessage());
        }
    }

    private function createDriveUserFromSSOId(string $ssoId, string $email = null): ?\OCP\IUser {
        try {
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
            return $newUser;
        } catch (\Exception $e) {
            $this->logger->error('Create Drive user error: ' . $e->getMessage());
            return null;
        }
    }

    private function modifySubscriptionStatus(string $userId, Package $package, string $actionType): bool {
        try {
            // check if user had subscription status
            try {
                $subscriptionStatus = $this->subscriptionStatusMapper->findByUserId($userId);
                // check if subscription is still active
                if ($subscriptionStatus->getStatus() != 'active') {
                    // calculating duration
                    if ($package->getUnit() == 'day') {
                        $durationInSeconds = $package->getDuration() * 86400;
                    } elseif ($package->getUnit() == 'month') {
                        $durationInSeconds = $package->getDuration() * 2592000;
                    } elseif ($package->getUnit() == 'year') {
                        $durationInSeconds = $package->getDuration() * 31536000;
                    } else {
                        throw new \Exception("Invalid package unit: " . $package->getUnit());
                    }
                    $startAt = time();
                    $endAt = $startAt + $durationInSeconds;
                    $subscriptionStatus->setPackageId($package->getId());
                    $subscriptionStatus->setStartAt($startAt);
                    $subscriptionStatus->setEndAt($endAt);
                    $subscriptionStatus->setStatus('active');
                } else {
                    // check if subscription has package same as new one
                    if ($subscriptionStatus->getPackageId() == $package->getId()) {
                        // extend package duration
                        $actionType = 'extend';
                        // calculating duration
                        if ($package->getUnit() == 'day') {
                            $durationInSeconds = $package->getDuration() * 86400;
                        } elseif ($package->getUnit() == 'month') {
                            $durationInSeconds = $package->getDuration() * 2592000;
                        } elseif ($package->getUnit() == 'year') {
                            $durationInSeconds = $package->getDuration() * 31536000;
                        } else {
                            throw new \Exception("Invalid package unit: " . $package->getUnit());
                        }
                        $endAt = $subscriptionStatus->getEndAt() + $durationInSeconds;
                        $subscriptionStatus->setEndAt($endAt);
                    } else {
                        // change to new package
                        // check if user's used space is greater than this package quota
                        $usedSpace = $this->getUserUsedSpace($userId);
                        if ($usedSpace === null) {
                            $this->logger->error("Failed to get used space for user $ssoId");
                            throw new \Exception("Failed to get user used space");
                        }
                        $packageQuotaBytes = \OCP\Util::computerFileSize($package->getQuota());
                        if ($usedSpace > $packageQuotaBytes) {
                            $this->logger->error("User $userId used space $usedSpace exceeds package quota $packageQuotaBytes");
                            // has to notify user
                            //
                            return false;
                        }
                        $actionType = 'change';
                        // calculating duration
                        if ($package->getUnit() == 'day') {
                            $durationInSeconds = $package->getDuration() * 86400;
                        } elseif ($package->getUnit() == 'month') {
                            $durationInSeconds = $package->getDuration() * 2592000;
                        } elseif ($package->getUnit() == 'year') {
                            $durationInSeconds = $package->getDuration() * 31536000;
                        } else {
                            throw new \Exception("Invalid package unit: " . $package->getUnit());
                        }
                        $startAt = time();
                        $endAt = $startAt + $durationInSeconds;
                        $subscriptionStatus->setPackageId($package->getId());
                        $subscriptionStatus->setStartAt($startAt);
                        $subscriptionStatus->setEndAt($endAt);
                        $subscriptionStatus->setStatus('active');
                    }
                }
                $this->subscriptionStatusMapper->update($subscriptionStatus);
            } catch (DoesNotExistException $e) {
                $this->logger->debug("User $userId has no existing subscription");
                // calculating duration
                if ($package->getUnit() == 'day') {
                    $durationInSeconds = $package->getDuration() * 86400;
                } elseif ($package->getUnit() == 'month') {
                    $durationInSeconds = $package->getDuration() * 2592000;
                } elseif ($package->getUnit() == 'year') {
                    $durationInSeconds = $package->getDuration() * 31536000;
                } else {
                    throw new \Exception("Invalid package unit: " . $package->getUnit());
                }
                $startAt = time();
                $endAt = $startAt + $durationInSeconds;
                $subscriptionStatus = new SubscriptionStatus(
                    $userId,
                    $package->getId(),
                    $startAt,
                    $endAt,
                    'active'
                );
                $this->subscriptionStatusMapper->insert($subscriptionStatus);
                // get back the inserted record
                $subscriptionStatus = $this->subscriptionStatusMapper->findByUserId($userId);
            }

            // add subscription history
            $subscriptionHistory = new SubscriptionHistory(
                $subscriptionStatus->getId(),
                $userId,
                $package->getId(),
                $actionType,
                '',
                null,
                $package->getName(),
                $package->getCode(),
                $package->getQuota(),
                $package->getDuration(),
                $package->getUnit(),
                $package->getPrice(),
                $subscriptionStatus->getEndAt()
            );
            if ($actionType == 'subscribe') {
                $subscriptionHistory->setDescription("User $userId subscribed new package " . $package->getCode());
            } elseif ($actionType == 'change') {
                $subscriptionHistory->setDescription("User $userId changed to package " . $package->getCode());
            } elseif ($actionType == 'extend') {
                $subscriptionHistory->setDescription("User $userId extended package " . $package->getCode() . " for " . $package->getDuration() . " " . $package->getUnit());
            } elseif ($actionType == 'cancel') {
                $subscriptionHistory->setDescription("User $userId cancelled package " . $package->getCode());
            } elseif ($actionType == 'auto_expired') {
                $subscriptionHistory->setDescription("System cancelled user $userId 's subscribed package " . $package->getCode() . " due to expiration");
            } else {
                $subscriptionHistory->setDescription("User $userId performed unknown action on package " . $package->getCode());
            }
            $this->subscriptionHistoryMapper->insert($subscriptionHistory);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error("Modify subscription_status error: " . $e->getMessage());
            return false;
        }
    }

    private function getUserUsedSpace(string $userId): ?float {
        try {
            if (!$userId) {
                // if there is no userId, query will return the total used space of all users
                return null;
            }
            $sql = 'SELECT SUM(fc.size) as total_size'.
                    ' FROM `*PREFIX*filecache` as fc '.
                    ' JOIN `*PREFIX*storages` as st ON fc.storage = st.numeric_id '.
                    ' WHERE st.id LIKE ? '.
                    ' AND fc.path LIKE ? '.
                    ' AND fc.mimetype != (SELECT id FROM `*PREFIX*mimetypes` WHERE mimetype = ?)';
            $likeUserId = '%'.$userId;
            $likePath = 'files/%';
            $mimetype = 'httpd/unix-directory';
            $dbConnection = \OC::$server->getDatabaseConnection();
            $query = $dbConnection->prepare($sql);
            $query->bindParam(1, $likeUserId, \PDO::PARAM_STR);
            $query->bindParam(2, $likePath, \PDO::PARAM_STR);
            $query->bindParam(3, $mimetype, \PDO::PARAM_STR);
            $query->execute();
            $row = $query->fetch();
            $query->closeCursor();
            if ($row) {
                return (float)reset($row);
            }
            return 0.0;
        } catch (\Throwable $e) {
            $this->logger->error("Get user used space error for user $userId: " . $e->getMessage());
            return null;
        }
    }

    private function validateToken(string $timeStamp, string $customerId, string $customerName, string $token): bool {
        $rawString = $timeStamp . $customerId . $customerName . $this->cbsHashSecretKey;
        $expectedBinary = hash('sha256', $rawString, true);
        $tokenBinary = hex2bin($token);

        if (!$expectedBinary || !$tokenBinary) {
            $this->logger->debug("Validate token: Hash/Convert string to bin failed!");
            return false;
        }

        if (strlen($expectedBinary) != strlen($tokenBinary)) {
            $this->logger->debug("Validate token: strlen does not equal!");
            return false;
        }

        return hash_equals($expectedBinary, $tokenBinary);
    }

    private function getToken(string $username = null, string $password = null): ?string {
        try {
            $ssoUrl = $this->config->getAppValue('sso_auth', 'sso_url', '');
            $realmName = $this->config->getAppValue('sso_auth', 'realm', '');
            $clientId = $this->config->getAppValue('sso_auth', 'client_id', '');
            $clientSecret = $this->config->getAppValue('sso_auth', 'client_secret', '');
            $username = $username ?? $this->config->getAppValue('sso_auth', 'admin_user', '');
            $password = $password ?? $this->config->getAppValue('sso_auth', 'admin_password', '');
            $client = $this->http->newClient();
            $url = rtrim($ssoUrl, '/') . '/login';
            $response = $client->post($url, [
                'body' => json_encode([
                    'username' => $username,
                    'password' => $password,
                    'realmName' => $realmName,
                    'clientId' => $clientId,
                    'clientSecret' => $clientSecret
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
            // borrow from sso auth app
            $ssoUrl = $this->config->getAppValue('sso_auth', 'sso_url', '');
            $clientId = $this->config->getAppValue('sso_auth', 'client_id', '');
            $realmName = $this->config->getAppValue('sso_auth', 'realm', '');
            $client = $this->http->newClient();
            $url = rtrim($ssoUrl, '/') . '/user/public/check-email-phone-none-exist';
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
                $this->logger->error("SSO check account response: " . $body);
                throw new \Exception("There is no success field in response");
            }
            if (!(bool)$data["success"] && isset($data["result"]["ssoId"])) {
                $this->logger->error("SSO check account indicates existence: " . $data["result"]["ssoId"]);
                return $data["result"]["ssoId"];
            }
            $this->logger->error("SSO check account indicates non-existence: " . $body);
            return null;
        } catch (\Throwable $e) {
            $this->logger->error("SSO check account error: " . $e->getMessage());
            return null;
        }
    }

    private function createSSOAccount(string $email = null, string $phoneNumber = null, string $password): ?string {
        try {
            // borrow from sso auth app
            $ssoUrl        = $this->config->getAppValue('sso_auth', 'sso_url', '');
            $clientId      = $this->config->getAppValue('sso_auth', 'client_id', '');
            $client = $this->http->newClient();
            $url = rtrim($ssoUrl, '/') . '/partner/create?clientId=' . urlencode($clientId);
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
                'tenantCode' => $clientId . "-TENANT",
                'domain' => 'https://drive.mobifone.vn',
                'registerType' => 0,
                'email' => $email,
                'phoneNumber' => $phoneNumber,
            ];
            $response = $client->post($url, [
                'body' => json_encode($body),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $this->logger->error("SSO create account with body: " . json_encode($body));
            $body = (string) $response->getBody();
            $data = json_decode($body, true);
            if (!$data["success"]) {
                throw new \Exception("Response indicates failure");
            }

            // add this user to drive tenant on sso side
            // 200 means ok, else there will be exception
            $ssoUserId = $data["result"]["userId"];
            $url = rtrim($ssoUrl, '/') . '/partner/create-tenant?userId=' . urlencode($ssoUserId);
            $body = [
                'clientId' => $clientId,
                'tenantCode' => $clientId . "-TENANT",
                'domain' => 'https://drive.mobifone.vn',
            ];
            $response = $client->post($url, [
                'body' => json_encode($body),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $this->logger->debug("User $ssoUserId is in tenant " . $clientId . "-TENANT");
            return $ssoUserId;
        } catch (\Throwable $e) {
            $this->logger->error("SSO create account error: " . $e->getMessage());
            return null;
        }
    }
}