<?php
/**
 * Package Manager - Package Registration Controller
 */

namespace OCA\PackageManager\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\Http\Client\IClientService;
use OCP\IRequest;
use OCA\PackageManager\Service\LogService;
use OCP\IConfig;
use OCP\IUserManager;
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
    private IClientService $http;
    private IUserManager $userManager;
    private IConfig $config;
    // SSO configuration for communication with SSO server
    private string $ssoUrl;
    private string $realmName;
    private string $clientId;
    private string $clientSecret;
    private string $adminUser;
    private string $adminPassword;
    // database mappers
    private PackageMapper $packageMapper;


    public function __construct($appName, IRequest $request, IConfig $config, IClientService $http, IUserManager $userManager, 
                                PackageMapper $packageMapper, SubscriptionStatusMapper $subscriptionStatusMapper, 
                                SubscriptionHistoryMapper $subscriptionHistoryMapper, LogService $logger) {
        parent::__construct($appName, $request);
        $this->logger = $logger;
        $this->userManager = $userManager;
        $this->http = $http;
        $this->packageMapper = $packageMapper;
        $this->subscriptionStatusMapper = $subscriptionStatusMapper;
        $this->subscriptionHistoryMapper = $subscriptionHistoryMapper;
        $this->config = $config;
        // load SSO config
        $this->ssoUrl        = $config->getAppValue('sso_auth', 'sso_url', '');
        $this->realmName     = $config->getAppValue('sso_auth', 'realm', '');
        $this->clientId      = $config->getAppValue('sso_auth', 'client_id', '');
        $this->clientSecret  = $config->getAppValue('sso_auth', 'client_secret', '');
        $this->adminUser     = $config->getAppValue('sso_auth', 'admin_user', '');
        $this->adminPassword = $config->getAppValue('sso_auth', 'admin_password', '');
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     */
    public function register(string $phoneNumber, string $packageCode) {
        try {
            //validate input
            $phonePattern = '/^(?:\+84|0)(3|5|7|8|9)[0-9]{8}$/';
            if (empty($phoneNumber) || !preg_match($phonePattern, $phoneNumber)) {
                return new DataResponse(['status' => 'error', 'message' => 'Invalid phone number'], 400);
            }

            if (empty($packageCode)) {
                return new DataResponse(['status' => 'error', 'message' => 'Package code is required'], 400);
            }

            // get this package info from db
            try {
                $package = $this->packageMapper->findByCode($packageCode);
            } catch (DoesNotExistException $e) {
                $this->logger->debug("Package not found: " . $packageCode);
                return new DataResponse(['status' => 'error', 'message' => "Package $packageCode not found"], 404);
            }

            //Call api check sso account
            $ssoId = $this->checkSSOAccount(null, $phoneNumber);
            if (!$ssoId) {
                // create new SSO account
                $defaultPassword = 'MbfDrive@2026';
                $ssoId = $this->createSSOAccount(null, $phoneNumber, $defaultPassword);
                if (!$ssoId) {
                    $this->logger->error("Failed to create SSO account for phone number: " . $phoneNumber);
                    throw new \Exception("Failed to create SSO account");
                }
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
                // send sms success with account info (only if isset($defaultPassword) is true)
                $this->sendSMS($phoneNumber, "Đăng ký gói " . $package->getName() . " thành công", [
                    'account' => $phoneNumber,
                    'password' => isset($defaultPassword) ? $defaultPassword : ''
                ]);
                return new DataResponse(['status' => 'success', 'message' => 'Registration successful'], 200);
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
                    // send sms failure
                    $this->sendSMS($phoneNumber, "Không thể kích hoạt tài khoản: dung lượng đã sử dụng vượt quá hạn mức gói đăng ký");
                    return new DataResponse(['status' => 'error', 'message' => 'Cannot activate account: used space exceeds package quota'], 400);
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

                // send sms success
                $this->sendSMS($phoneNumber, "Đăng ký gói " . $package->getName() . " thành công", [
                    'account' => $phoneNumber
                ]);
                // return success
                return new DataResponse(['status' => 'success', 'message' => 'Registration successful'], 200);
            }

            // modify subscription status
            $result = $this->modifySubscriptionStatus($ssoId, $package, 'subscribe');
            if (!$result) {
                throw new \Exception("Failed to modify subscription status for existing user $ssoId");
            }

            // update user quota
            $driveUser->setQuota($package->getQuota());

            // send sms success
            $this->sendSMS($phoneNumber, "Đăng ký gói " . $package->getName() . " thành công", [
                'account' => $phoneNumber
            ]);
            return new DataResponse(['status' => 'success', 'message' => 'Registration successful'], 200);
        } catch (\Throwable $e) {
            $this->logger->error("SMS registration error: " . $e->getMessage());
            // send sms failure
            $this->sendSMS($phoneNumber, "Đăng ký gói thất bại, vui lòng thử lại sau");
            return new DataResponse(['status' => 'error', 'message' => 'An error occurred during registration'], 500);
        }
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     */
    public function cancel(string $phoneNumber, string $packageCode) {
        try {
            //validate input
            $phonePattern = '/^(?:\+84|0)(3|5|7|8|9)[0-9]{8}$/';
            if (empty($phoneNumber) || !preg_match($phonePattern, $phoneNumber)) {
                return new DataResponse(['status' => 'error', 'message' => 'Invalid phone number'], 400);
            }

            if (empty($packageCode)) {
                return new DataResponse(['status' => 'error', 'message' => 'Package code is required'], 400);
            }

            // get this package info from db
            try {
                $package = $this->packageMapper->findByCode($packageCode);
            } catch (DoesNotExistException $e) {
                $this->logger->debug("Package not found: " . $packageCode);
                return new DataResponse(['status' => 'error', 'message' => "Package $packageCode not found"], 404);
            }

            //Call api check sso account
            $ssoId = $this->checkSSOAccount(null, $phoneNumber);
            if (!$ssoId) {
                $this->logger->debug("SSO account not found for phone number: " . $phoneNumber);
                // send sms failure
                $this->sendSMS($phoneNumber, "Tài khoản tương ứng với số điện thoại $phoneNumber không tồn tại. Vui lòng kiểm tra lại.");
                return new DataResponse(['status' => 'error', 'message' => "Account not found"], 404);
            }

            // check if Drive user already exists
            $driveUser = $this->userManager->get($ssoId);
            if (!$driveUser) {
                $this->logger->debug("Drive user not found for SSO id: " . $ssoId);
                // send sms failure
                $this->sendSMS($phoneNumber, "Tài khoản tương ứng với số điện thoại $phoneNumber không tồn tại. Vui lòng kiểm tra lại.");
                return new DataResponse(['status' => 'error', 'message' => "Drive user not found"], 404);
            }

            // check if Drive user is active
            if (!$driveUser->isEnabled()) {
                $this->logger->debug("Drive user $ssoId is already disabled");
                // send sms failure
                $this->sendSMS($phoneNumber, "Tài khoản của bạn đã bị vô hiệu hóa.");
                return new DataResponse(['status' => 'error', 'message' => 'Account is already disabled'], 400);
            }

            // get subscription status
            try {
                $subscriptionStatus = $this->subscriptionStatusMapper->findByUserId($ssoId);
                // check if this subscription corresponds to the given package
                if ($subscriptionStatus->getPackageId() != $package->getId()) {
                    $this->logger->debug("Subscription package ID " . $subscriptionStatus->getPackageId() . " does not match given package ID " . $package->getId() . " for user $ssoId");
                    // send sms failure
                    $this->sendSMS($phoneNumber, "Gói hiện tại không khớp với gói bạn muốn hủy.");
                    return new DataResponse(['status' => 'error', 'message' => 'Subscription package does not match'], 400);
                }

                // check if subscription is still active
                if ($subscriptionStatus->getStatus() != 'active') {
                    $this->logger->debug("Subscription for user $ssoId is not active");
                    // send sms failure
                    $this->sendSMS($phoneNumber, "Gói hiện tại đã được hủy.");
                    return new DataResponse(['status' => 'success', 'message' => 'No active subscription to cancel'], 200);
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
                    // send sms about account disabled due to used space exceeds default quota
                    $this->sendSMS($phoneNumber, "Tài khoản của bạn đã bị vô hiệu hóa do dung lượng đã sử dụng vượt quá hạn mức gói đăng ký mặc định.");
                    return new DataResponse(['status' => 'success', 'message' => 'Cancellation successful, account disabled due to used space exceeds default quota'], 200);
                }

                // send sms success
                $this->sendSMS($phoneNumber, "Hủy gói " . $package->getName() . " thành công");
                return new DataResponse(['status' => 'success', 'message' => 'Cancellation successful'], 200);
            } catch (DoesNotExistException $e) {
                $this->logger->debug("No subscription status found for user $ssoId");
                // send sms failure
                $this->sendSMS($phoneNumber, "Bạn không đang sử dụng gói hiện tại.");
                return new DataResponse(['status' => 'error', 'message' => 'No subscription to cancel'], 400);
            }
        } catch (\Throwable $e) {
            $this->logger->error("SMS cancellation error: " . $e->getMessage());
            // send sms failure
            $this->sendSMS($phoneNumber, "Hủy gói thất bại, vui lòng thử lại sau");
            return new DataResponse(['status' => 'error', 'message' => 'An error occurred during cancellation'], 500);
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

    private function sendSMS(string $phoneNumber, string $message, array $params = []) {
        $this->logger->debug("Sending SMS to $phoneNumber: $message with params: " . json_encode($params));
        return true;
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
}