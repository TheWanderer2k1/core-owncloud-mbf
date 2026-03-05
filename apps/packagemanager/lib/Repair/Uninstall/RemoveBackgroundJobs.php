<?php

namespace OCA\PackageManager\Repair\Uninstall;

use OCP\Migration\IRepairStep;
use OCP\Migration\IOutput;

class RemoveBackgroundJobs implements IRepairStep {
    public function getName() {
        return 'Remove background jobs for Package Manager';
    }

    public function run(IOutput $output) {
        try {
            // Because of disable, background jobs will not be register as service, so we need to refer class name directly
            \OC::$server->getJobList()->remove('OCA\PackageManager\BackgroundJob\AutoCancelExpiredPackage');
        } catch (\Exception $e) {
            \OC::$server->getLogger()->error('Failed to remove background jobs for Package Manager: ' . $e->getMessage());
            $output->info('No job list available, skipping background job removal');
        }
    }
}