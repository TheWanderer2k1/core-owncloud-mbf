<?php

namespace OCA\News\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IConfig;

class ConfigController extends Controller {

    private $config;

    public function __construct($appName, IRequest $request, IConfig $config) {
        parent::__construct($appName, $request);
        $this->config = $config;
    }

    public function index() {
        $templateName = 'admin';
        $parameters = [
            'intro' => $this->config->getAppValue('news', 'intro', 'Giới thiệu về MobiFone Drive'),
            'terms' => $this->config->getAppValue('news', 'terms', 'Điều khoản'),
            'policy' => $this->config->getAppValue('news', 'policy', 'Chính sách bảo mật')
        ];
        return new TemplateResponse($this->appName, $templateName, $parameters);
    }

    /**
     * @AdminRequired
     */
    public function save(string $intro = '', string $terms = '', string $policy = ''): DataResponse {
        $this->config->setAppValue('news', 'intro', $intro);
        $this->config->setAppValue('news', 'terms', $terms);
        $this->config->setAppValue('news', 'policy', $policy);
        return new DataResponse(['status' => 'success']);
    }
}