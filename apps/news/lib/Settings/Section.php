<?php

namespace OCA\News\Settings;

use OCP\Settings\ISection;

class Section implements ISection
{
    private $l;

    public function __construct(\OCP\IL10N $l = null) {
        $this->l = $l ?: \OC::$server->getL10N('news');
    }

    public function getID() {
        return 'news';
    }

    public function getName() {
        return $this->l->t('Quản lý tin tức');
    }

    public function getPriority() {
        return 50;
    }

    public function getIconName() {
        return 'news';
    }
}
