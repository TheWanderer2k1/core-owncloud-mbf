<?php

namespace OCA\FAQ\Settings;

use OCP\Settings\ISettings;
use OCP\Template;
use OCA\FAQ\Db\FaqMapper;

class Admin implements ISettings {

    private $mapper;

    public function __construct(FaqMapper $mapper) {
        $this->mapper = $mapper;
    }

    public function getPanel(): Template {
        $tmpl = new Template('faq', 'admin');
        $tmpl->assign('faqs', $this->mapper->findAll());
        return $tmpl;
    }

    public function getSectionID(): string {
        return 'faq';
    }

    public function getPriority(): int {
        return 50;
    }
}
