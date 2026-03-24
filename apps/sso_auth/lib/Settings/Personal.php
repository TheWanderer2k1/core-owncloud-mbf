<?php

namespace OCA\SsoAuth\Settings;

use OCP\Settings\ISettings;
use OCP\Template;

class Personal implements ISettings {

    public function getPanel(): Template {
        $tmpl = new Template('sso_auth', 'personal');
        return $tmpl;
    }

    public function getSectionID(): string {
        return 'sso_auth_personal';
    }

    public function getPriority(): int {
        return 60;
    }

}