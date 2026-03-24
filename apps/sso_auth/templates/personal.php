<?php
/** @var $_ array */
/** @var $l \OCP\IL10N */
script('sso_auth', 'personal');
style('sso_auth', 'personal');
?>
<form id="delete-account-form" action="<?php p(\OC::$server->getURLGenerator()->linkToRoute('sso_auth.account.delete')); ?>" method="post" autocapitalize="none" novalidate>
    <h2><?php p($l->t('Delete Account')); ?></h2>
    <p>
        <?php p($l->t('Deleting your account will remove all your data from the system. This action cannot be undone.')); ?>
    </p>
    <p>
        <?php p($l->t('If you want to proceed, please click the button below to delete your account.')); ?>
    </p>
    <input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>">
    <button type="submit" id="delete-account-button">
        <span><?php p($l->t('Delete Account')); ?></span>
    </button>
</form>