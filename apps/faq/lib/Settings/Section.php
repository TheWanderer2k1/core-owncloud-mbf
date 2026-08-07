<?php

namespace OCA\FAQ\Settings;

use OCP\Settings\ISection;

class Section implements ISection
{
    private $l;

    public function __construct(\OCP\IL10N $l = null) {
        $this->l = $l ?: \OC::$server->getL10N('faq');
    }

    public function getID() {
        return 'faq';
    }

    public function getName() {
        return $this->l->t('FAQ Management');
    }

    public function getPriority() {
        return 50;
    }

    public function getIconName() {
        return 'faq';
    }
}
