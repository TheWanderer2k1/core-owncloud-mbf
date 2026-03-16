<?php

namespace OCA\PackageManager\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IConfig;
use OCA\PackageManager\Service\LogService;

class ConfigController extends Controller {
    private IConfig $config;
    private $logger;

    public function __construct($appName, IRequest $request, IConfig $config, LogService $logger) {
        parent::__construct($appName, $request);
        $this->config = $config;
        $this->appName = $appName;
        $this->logger = $logger;
    }

    /**
     * @NoCSRFRequired
     */
    public function index() {
        $templateName = 'config';
        $parameters = [
            'cbs_admin_user' => $this->config->getAppValue($this->appName, 'cbs_admin_user', ''),
            'cbs_admin_pass' => $this->config->getAppValue($this->appName, 'cbs_admin_pass', ''),
            'cbs_api_base_url' => $this->config->getAppValue($this->appName, 'cbs_api_base_url', ''),
            'cbs_product_code' => $this->config->getAppValue($this->appName, 'cbs_product_code', ''),
            'cbs_hash_secret_key' => $this->config->getAppValue($this->appName, 'cbs_hash_secret_key', ''),
            'vasp_brandname' => $this->config->getAppValue($this->appName, 'vasp_brandname', ''),
            'vasp_user' => $this->config->getAppValue($this->appName, 'vasp_user', ''),
            'vasp_password' => $this->config->getAppValue($this->appName, 'vasp_password', ''),
        ];
        return new TemplateResponse($this->appName, $templateName, $parameters);
    }

    /**
     * @AdminRequired
     */
    public function save(string $cbs_admin_user = '', string $cbs_admin_pass = '', string $cbs_api_base_url = '', string $cbs_product_code = '', string $cbs_hash_secret_key = '', 
                        string $vasp_brandname = '', 
                        string $vasp_user = '', 
                        string $vasp_password = ''): DataResponse {
        try {
            if (empty($cbs_admin_user)) {
                return new DataResponse(['status' => 'error', 'message' => 'CBS admin username is required'], 400);
            }

            if (empty($cbs_admin_pass)) {
                return new DataResponse(['status' => 'error', 'message' => 'CBS admin password is required'], 400);
            }

            if (empty($cbs_api_base_url)) {
                return new DataResponse(['status' => 'error', 'message' => 'CBS url is required'], 400);
            }

            if (empty($cbs_product_code)) {
                return new DataResponse(['status' => 'error', 'message' => 'CBS product code is required'], 400);
            }

            if (empty($cbs_hash_secret_key)) {
                return new DataResponse(['status' => 'error', 'message' => 'CBS hash secret key is required'], 400);
            }

            if (empty($vasp_brandname)) {
                return new DataResponse(['status' => 'error', 'message' => 'VASP brandname is required'], 400);
            }

            if (empty($vasp_user)) {
                return new DataResponse(['status' => 'error', 'message' => 'VASP username is required'], 400);
            }

            if (empty($vasp_password)) {
                return new DataResponse(['status' => 'error', 'message' => 'VASP password is required'], 400);
            }
            
            $this->config->setAppValue($this->appName, 'cbs_admin_user', $cbs_admin_user);
            $this->config->setAppValue($this->appName, 'cbs_admin_pass', $cbs_admin_pass);
            $this->config->setAppValue($this->appName, 'cbs_api_base_url', $cbs_api_base_url);
            $this->config->setAppValue($this->appName, 'cbs_product_code', $cbs_product_code);
            $this->config->setAppValue($this->appName, 'cbs_hash_secret_key', $cbs_hash_secret_key);
            $this->config->setAppValue($this->appName, 'vasp_brandname', $vasp_brandname);
            $this->config->setAppValue($this->appName, 'vasp_user', $vasp_user);
            $this->config->setAppValue($this->appName, 'vasp_password', $vasp_password);

            return new DataResponse(['status' => 'success'], 200);
        } catch (\Throwable $e) {
            $this->logger->debug("Err: " . $e->getMessage());
            return new DataResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}