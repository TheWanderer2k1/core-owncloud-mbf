<?php

namespace OCA\SsoAuth\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IUserSession;
use OCP\IRequest;
use OCP\IConfig;
use OCP\IGroupManager;
use OCA\SsoAuth\Service\LogService;

class AccountController extends Controller {

    private $config;
    private $userSession;
    private $groupManager;
    private $logger;

    public function __construct($appName, 
                                IRequest $request, 
                                IConfig $config, 
                                IUserSession $userSession,
                                IGroupManager $groupManager,
                                LogService $logger) {
        parent::__construct($appName, $request);
        $this->config = $config;
        $this->userSession = $userSession;
        $this->groupManager = $groupManager;
        $this->logger = $logger;
    }

    /**
     * @NoAdminRequired
     */
    public function delete() {
        try {
            $user = $this->userSession->getUser();
            if ($user) {
                $userId = $user->getUID();
                if ($this->groupManager->isAdmin($userId)) {
                    return new DataResponse(
                        [
                            'status' => 'error',
                            'data' => [
                                'message' => 'Admin users cannot be deleted through this interface.'
                            ]
                        ],
                        403
                    );
                }
                if ($user->delete()) {
                    return new DataResponse(
                        [
                            'status' => 'success',
                            'data' => []
                        ],
                        200
                    );
                }
            }
            return new DataResponse(
                [
                    'status' => 'error',
                    'data' => [
                        'message' => (string)$this->l10n->t('Unable to delete user.')
                    ]
                ],
                400
            );
        } catch (\Exception $e) {
            $this->logger->error("Error deleting user: " . $e->getMessage());
            return new DataResponse(
                [
                    'status' => 'error',
                    'data' => [
                        'message' => (string)$this->l10n->t('An error occurred while deleting the user.')
                    ]
                ],
                500
            );
        }
    }
}