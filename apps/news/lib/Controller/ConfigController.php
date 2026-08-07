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

    /**
     * @AdminRequired
     * @NoCSRFRequired
     */
    public function upload(): DataResponse {
        $file = $this->request->getUploadedFile('upload');
        if (!$file) {
            return new DataResponse(['error' => ['message' => 'No file uploaded']], 400);
        }

        $uploadsDir = __DIR__ . '/../../uploads';
        if (!file_exists($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('img_', true) . '.' . $extension;
        $targetPath = $uploadsDir . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $url = \OC::$server->getURLGenerator()->linkTo('news', 'uploads/' . $filename);
            $absoluteUrl = \OC::$server->getURLGenerator()->getAbsoluteURL($url);
            return new DataResponse(['url' => $absoluteUrl]);
        }

        return new DataResponse(['error' => ['message' => 'Failed to save uploaded file']], 500);
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     */
    public function publish(): DataResponse {
        $intro = $this->config->getAppValue('news', 'intro', 'Giới thiệu về MobiFone Drive');
        $terms = $this->config->getAppValue('news', 'terms', 'Điều khoản');
        $policy = $this->config->getAppValue('news', 'policy', 'Chính sách bảo mật');

        $baseUrl = \OC::$server->getURLGenerator()->getAbsoluteURL('/');
        $baseUrl = rtrim($baseUrl, '/');

        $intro = $this->makeUrlsAbsolute($intro, $baseUrl);
        $terms = $this->makeUrlsAbsolute($terms, $baseUrl);
        $policy = $this->makeUrlsAbsolute($policy, $baseUrl);

        $parameters = [
            'intro' => $intro,
            'terms' => $terms,
            'policy' => $policy
        ];
        return new DataResponse($parameters);
    }

    private function makeUrlsAbsolute(string $html, string $baseUrl): string {
        $html = str_replace('src="/apps/news/uploads/', 'src="' . $baseUrl . '/apps/news/uploads/', $html);
        $html = str_replace("src='/apps/news/uploads/", "src='" . $baseUrl . "/apps/news/uploads/", $html);
        return $html;
    }
}