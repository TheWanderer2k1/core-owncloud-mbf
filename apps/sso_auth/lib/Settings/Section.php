<?php

namespace OCA\SsoAuth\Settings;

use OCP\Settings\ISection;

class Section implements ISection
{
    private $l;

    public function __construct(\OCP\IL10N $l = null) {
        $this->l = $l ?: \OC::$server->getL10N('sso_auth');
    }

    public function getID() {
        return 'sso_auth';
    }

    public function getName() {
        return $this->l->t('SSO Authentication');
    }

    public function getPriority() {
        return 50;
    }

    public function getIconName() {
        return 'settings';
    }
}