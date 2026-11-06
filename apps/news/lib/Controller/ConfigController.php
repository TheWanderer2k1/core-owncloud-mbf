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

        // Parse HTML to an array whose elements are strutured to match landing
        // page's expectation
        $terms = $this->htmlToArray($terms);
        $policy = $this->htmlToArray($policy);

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

    private function removeEmptyElements(\DOMDocument $dom): void {
        $xpath = new \DOMXPath($dom);
        
        // Target common block & inline tags that might be empty
        $elements = $xpath->query('//p | //div | //span | //li');

        // Iterate backwards so removing a child doesn't break traversal
        for ($i = $elements->length - 1; $i >= 0; $i--) {
            $node = $elements->item($i);
            
            // Check if the node contains images, iframes, etc. that shouldn't be deleted
            if ($xpath->query('.//img | .//iframe | .//video', $node)->length > 0) {
                continue;
            }

            // Normalize text content by stripping standard spaces and non-breaking spaces (\u{00A0})
            $cleanText = str_replace(["\xc2\xa0", '&nbsp;'], '', $node->textContent);
            $cleanText = trim($cleanText);

            if ($cleanText === '') {
                $node->parentNode->removeChild($node);
            }
        }
    }

    private function htmlToArray(string $html, bool $preserveHtmlContent = true): array {
        $html = trim($html);
        if ($html === '') {
            return [];
        }

        $dom = new \DOMDocument();
        $internalErrors = libxml_use_internal_errors(true);

        // Wrap the snippet in a root <div> so we have a single container of siblings
        $dom->loadHTML(
            '<?xml encoding="utf-8" ?><div>' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $this->removeEmptyElements($dom);

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        $root = $dom->getElementsByTagName('div')->item(0);
        if (!$root) {
            return [];
        }

        $items = [];
        $currentItem = null;

        // Iterate through each child element/node in order
        foreach ($root->childNodes as $node) {
            // Check if the node is an <h1> tag
            if ($node->nodeType === XML_ELEMENT_NODE && strtolower($node->nodeName) === 'h2') {
                // If we were already collecting an item, save it before starting the next
                if ($currentItem !== null) {
                    $currentItem['content'] = trim($currentItem['content']);
                    $items[] = $currentItem;
                }

                // Start a brand-new section
                $currentItem = [
                    'title' => trim($node->textContent),
                    'content' => ''
                ];
            } elseif ($currentItem !== null) {
                // Append everything between <h1> tags to the current section
                if ($preserveHtmlContent) {
                    // Preserves formatting (<p>, <ul>, <b>, line breaks, etc.)
                    $currentItem['content'] .= $dom->saveHTML($node);
                } else {
                    // Plain text only
                    $text = trim($node->textContent);
                    if ($text !== '') {
                        $currentItem['content'] .= ($currentItem['content'] !== '' ? ' ' : '') . $text;
                    }
                }
            }
        }

        // Push the final section after reaching the end of the HTML
        if ($currentItem !== null) {
            $currentItem['content'] = trim($currentItem['content']);
            $items[] = $currentItem;
        }

        return $items;
    }
}
