<?php

namespace OCA\News\Settings;

use OCP\Settings\ISettings;
use OCP\Template;
use OCP\IConfig;

class Admin implements ISettings {

    /** @var IConfig */
    private $config;

    public function __construct(IConfig $config) {
        $this->config = $config;
    }

    public function getPanel(): Template {
        $tmpl = new Template('news', 'admin');
        $tmpl->assign('intro', $this->config->getAppValue('news', 'intro', 'Giới thiệu về MobiFone Drive'));
        $tmpl->assign('terms', $this->config->getAppValue('news', 'terms', 'Điều khoản'));
        $tmpl->assign('policy', $this->config->getAppValue('news', 'policy', 'Chính sách bảo mật'));
        return $tmpl;
    }

    public function getSectionID(): string {
        return 'news';
    }

    public function getPriority(): int {
        return 50;
    }

}