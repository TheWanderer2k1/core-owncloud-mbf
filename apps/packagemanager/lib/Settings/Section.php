<?php

namespace OCA\PackageManager\Settings;

use OCP\Settings\ISection;

class Section implements ISection {
    public function getID() {
        return 'packagemanager-config';
    }

    public function getName() {
        return 'Package Manager';
    }

    public function getPriority() {
        return 40;
    }

    public function getIconName() {
        return 'settings';
    }
}