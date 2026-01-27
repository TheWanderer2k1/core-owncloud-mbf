<?php

namespace OCA\PackageManager\Settings;

use OCP\Settings\ISettings;
use OCP\Template;
use OCP\IConfig;

class Admin implements ISettings {
    private IConfig $config;
    private string $appName;

    public function __construct(IConfig $config) {
        $this->config = $config;
        $this->appName = 'packagemanager';
    }

    public function getPanel(): Template {
        $tmpl = new Template($this->appName, 'config');
        $tmpl->assign('cbs_admin_user', $this->config->getAppValue($this->appName, 'cbs_admin_user', ''));
        $tmpl->assign('cbs_admin_pass', $this->config->getAppValue($this->appName, 'cbs_admin_pass', ''));
        $tmpl->assign('cbs_api_base_url', $this->config->getAppValue($this->appName, 'cbs_api_base_url', ''));
        $tmpl->assign('cbs_product_code', $this->config->getAppValue($this->appName, 'cbs_product_code', ''));
        $tmpl->assign('cbs_hash_secret_key', $this->config->getAppValue($this->appName, 'cbs_hash_secret_key', ''));
        return $tmpl;
    }

    public function getSectionID(): string {
        return $this->appName . '-config';
    }

    public function getPriority(): int {
        return 40;
    }
}