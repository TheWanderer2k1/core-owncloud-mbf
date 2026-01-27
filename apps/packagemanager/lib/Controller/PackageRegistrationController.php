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
    public function register(int $type = 0, string $timeStamp = '', string $customerId = '', string $customerName = '', 
                            string $token = '', array $listPackage = [], string $ssoCustomerId = '', 
                            string $tenantCode = '', string $contractNo = ''): DataResponse {
        try {
            // validate input
            if (empty($type) || empty($timeStamp) || empty($customerId) || empty($customerName) ||
            empty($token) || empty($listPackage) || empty($ssoCustomerId) || empty($tenantCode) ||
            empty($contractNo)) {
                return new DataResponse([
                    'status' => 2,
                    'message' => 'Invalid input'
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
                // call complete create contract cbs api
                $client = $this->http->newClient();
                $url = rtrim($this->cbsApiBaseUrl, '/') . '/customer/completeCreateContract';
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
                        'Authorization' => 'Basic base64encodedcredentialshere'
                    ]
                ]);
                return new DataResponse([
                    'status' => 1, 
                    'message' => 'Complete create contract processed'
                ], 200);
            } elseif ($type == 6) {
                $packageInfo = $listPackage[0];
                $ssoId = $ssoCustomerId;
                $packageCode = $packageInfo['packageCode'];
                if ($packageInfo['action'] == 1) {
                    // register
                    if (empty($ssoId)) {
                        return new DataResponse([
                            'status' => 2, 
                            'message' => 'SSO ID is required'
                        ], 400);
                    }

                    if (empty($packageCode)) {
                        return new DataResponse([
                            'status' => 2, 
                            'message' => 'Package code is required'
                        ], 400);
                    }

                    // get this package info from db
                    try {
                        $package = $this->packageMapper->findByCode($packageCode);
                    } catch (DoesNotExistException $e) {
                        $this->logger->debug("Package not found: " . $packageCode);
                        return new DataResponse([
                            'status' => 2, 
                            'message' => "Package $packageCode not found"
                        ], 404);
                    }

                    // check if Drive user already exists
                    $driveUser = $this->userManager->get($ssoId);
                    if (!$driveUser) {
                        // create Drive account from sso id
                        $driveUser = $this->createDriveUserFromSSOId($ssoId, null);
                        if (!$driveUser) {
                            $this->logger->error("Failed to create Drive user for SSO id: " . $ssoId);
                            throw new \Exception("Failed to create Drive user");
                        }
                        // modify subscription status
                        $result = $this->modifySubscriptionStatus($ssoId, $package, 'subscribe');
                        if (!$result) {
                            throw new \Exception("Failed to modify subscription status for new user $ssoId");
                        }
                        // update user quota
                        $driveUser->setQuota($package->getQuota());
                        return new DataResponse([
                            'status' => 1, 
                            'message' => 'Registration successful'
                        ], 200);
                    }

                    // check if Drive user is active
                    if (!$driveUser->isEnabled()) {
                        // check if user's used space is greater than this package quota
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

                        // (re)activate user
                        $driveUser->setEnabled(true);

                        // add new/update record to subscription_status
                        $result = $this->modifySubscriptionStatus($ssoId, $package, 'subscribe');
                        if (!$result) {
                            throw new \Exception("Failed to modify subscription status for re-activated user $ssoId");
                        }

                        // update user quota
                        $driveUser->setQuota($package->getQuota());

                        // return success
                        return new DataResponse([
                            'status' => 1, 
                            'message' => 'Registration successful'
                        ], 200);
                    }

                    // modify subscription status
                    $result = $this->modifySubscriptionStatus($ssoId, $package, 'subscribe');
                    if (!$result) {
                        throw new \Exception("Failed to modify subscription status for existing user $ssoId");
                    }

                    // update user quota
                    $driveUser->setQuota($package->getQuota());

                    return new DataResponse([
                        'status' => 1,
                        'message' => 'Registration successful'
                    ], 200);
                } elseif ($packageInfo['action'] == 3) {
                    // cancel
                    return $this->cancel($ssoId, $packageCode);
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
            $this->logger->error("SMS registration error: " . $e->getMessage());
            return new DataResponse([
                'status' => 2, 
                'message' => 'An error occurred during registration'
            ], 500);
        }
    }

    private function cancel(string $ssoId, string $packageCode): DataResponse {
        try {
            //validate input
            if (empty($ssoId)) {
                return new DataResponse([
                    'status' => 2, 
                    'message' => 'SSO ID is required'
                ], 400);
            }

            if (empty($packageCode)) {
                return new DataResponse([
                    'status' => 2, 
                    'message' => 'Package code is required'
                ], 400);
            }

            // get this package info from db
            try {
                $package = $this->packageMapper->findByCode($packageCode);
            } catch (DoesNotExistException $e) {
                $this->logger->debug("Package not found: " . $packageCode);
                return new DataResponse([
                    'status' => 2, 
                    'message' => "Package $packageCode not found"
                ], 404);
            }

            // check if Drive user already exists
            $driveUser = $this->userManager->get($ssoId);
            if (!$driveUser) {
                $this->logger->debug("Drive user not found for SSO id: " . $ssoId);
                return new DataResponse([
                    'status' => 2, 
                    'message' => "Drive user not found"
                ], 404);
            }

            // check if Drive user is active
            if (!$driveUser->isEnabled()) {
                $this->logger->debug("Drive user $ssoId is already disabled");
                return new DataResponse([
                    'status' => 2, 
                    'message' => 'Account is already disabled'
                ], 400);
            }

            // get subscription status
            try {
                $subscriptionStatus = $this->subscriptionStatusMapper->findByUserId($ssoId);
            } catch (DoesNotExistException $e) {
                $this->logger->debug("No subscription status found for user $ssoId");
                return new DataResponse([
                    'status' => 2, 
                    'message' => 'No subscription to cancel'
                ], 400);
            }
            
            // check if this subscription corresponds to the given package
            if ($subscriptionStatus->getPackageId() != $package->getId()) {
                $this->logger->debug("Subscription package ID " . $subscriptionStatus->getPackageId() . " does not match given package ID " . $package->getId() . " for user $ssoId");
                return new DataResponse([
                    'status' => 2, 
                    'message' => 'Subscription package does not match'
                ], 400);
            }

            // check if subscription is still active
            if ($subscriptionStatus->getStatus() != 'active') {
                $this->logger->debug("Subscription for user $ssoId is not active");
                return new DataResponse([
                    'status' => 1, 
                    'message' => 'No active subscription to cancel'
                ], 200);
            }

            // set subscription status to cancelled
            $subscriptionStatus->setStatus('cancelled');
            $this->subscriptionStatusMapper->update($subscriptionStatus);

            // add subscription history
            $subscriptionHistory = new SubscriptionHistory(
                $subscriptionStatus->getId(),
                $ssoId,
                $package->getId(),
                'cancel',
                "User $userId cancelled package " . $package->getCode()
            );
            $this->subscriptionHistoryMapper->insert($subscriptionHistory);

            // set user quota to default
            $driveUser->setQuota($this->config->getSystemValue('default_user_quota', '5 GB'));

            // check if user's used space is greater than default quota
            $usedSpace = $this->getUserUsedSpace($ssoId);
            $defaultQuotaBytes = \OCP\Util::computerFileSize($this->config->getSystemValue('default_user_quota', '5 GB'));
            if ($usedSpace === null || $usedSpace > $defaultQuotaBytes) {
                $this->logger->debug("User $ssoId used space $usedSpace exceeds default quota $defaultQuotaBytes");
                // disable user account
                $driveUser->setEnabled(false);
                return new DataResponse([
                    'status' => 1, 
                    'message' => 'Cancellation successful, account disabled due to used space exceeds default quota'
                ], 200);
            }

            return new DataResponse([
                'status' => 1, 
                'message' => 'Cancellation successful'
            ], 200);
        } catch (\Throwable $e) {
            $this->logger->error("SMS cancellation error: " . $e->getMessage());
            return new DataResponse([
                'status' => 2, 
                'message' => 'An error occurred during cancellation'
            ], 500);
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
            $defaultGroup->addUser($newUser);
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
                $actionType
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
}