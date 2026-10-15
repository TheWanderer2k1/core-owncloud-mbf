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

        // Parse the <ol> and <li> HTML to an array whose elements that are
        // strutured to match landing page expectation
        $terms = $this->htmllistToArray($terms);
        $policy = $this->htmllistToArray($policy);

        // Conflict in naming: what is 'policy' on owncloud is 'privacy' in the
        // landing page. Moveover, to the landing page, the term 'policy'
        // consist of both 'terms' and 'privacy'. For now, owncloud yield
        $parameters = [
            'intro' => $intro,
            'terms' => $terms,
            'privacy' => $policy // I yield
        ];
        return new DataResponse($parameters);
    }

    private function makeUrlsAbsolute(string $html, string $baseUrl): string {
        $html = str_replace('src="/apps/news/uploads/', 'src="' . $baseUrl . '/apps/news/uploads/', $html);
        $html = str_replace("src='/apps/news/uploads/", "src='" . $baseUrl . "/apps/news/uploads/", $html);
        return $html;
    }


    private function htmllistToArray(string $html): array {
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $items = [];
        $liElements = $dom->getElementsByTagName('li');

        foreach ($liElements as $li) {
            $text = trim($li->nodeValue);

            $parts = explode(':', $text, 2);
            
            if (count($parts) === 2) {
                $items[] = [
                    'title' => trim($parts[0]),
                    'content' => trim($parts[1])
                ];
            } else {
                $items[] = [
                    'title' => '',
                    'content' => trim($text)
                ];
            }
        }

        return $items;
    }
}
