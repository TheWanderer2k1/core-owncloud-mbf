<?php
/** @var $_ array */
script('packagemanager', 'config');
style('packagemanager', 'config');
?>
<form class="section" id="packagemanager-config-form" action="<?php p(\OC::$server->getURLGenerator()->linkToRoute('packagemanager.config.save')); ?>" method="post" autocapitalize="none" novalidate>
    <h2 class="app-name"><?php p($l->t('CBS Configuration'));?></h2>
    <p>
		<em><?php p($l->t('This section requires a CBS app to be installed in ownCloud')); ?></em>
	</p>
    <h3><?php p($l->t('CBS configuration is required.')); ?></h3>
	<br/>
    <input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>">
    <fieldset id="admin-packagemanager-config-fieldset">
        <div class="form-group">
            <label for="cbs_admin_user"><?php p($l->t('Admin user')); ?></label>
            <input type="text" name="cbs_admin_user" id="cbs_admin_user" placeholder="Enter admin user"
                value="<?php p($_['cbs_admin_user']); ?>" autocomplete="off" autocorrect="off" autofocus />
        </div>
        <div class="form-group">
            <label for="cbs_admin_pass"><?php p($l->t('Admin password')); ?></label>
            <input type="text" name="cbs_admin_pass" id="cbs_admin_pass"
                placeholder="Enter admin password" value="<?php p($_['cbs_admin_pass']); ?>" autocomplete="off" autocorrect="off" />
        </div>
        <div class="form-group">
            <label for="cbs_api_base_url"><?php p($l->t('Base URL')); ?></label>
            <input type="url" name="cbs_api_base_url" id="cbs_api_base_url" placeholder="Enter base URL"
                value="<?php p($_['cbs_api_base_url']); ?>" autocomplete="off" autocorrect="off" autofocus />
        </div>
        <div class="form-group">
            <label for="cbs_product_code"><?php p($l->t('Product code')); ?></label>
            <input type="text" name="cbs_product_code" id="cbs_product_code" placeholder="Enter product code"
                value="<?php p($_['cbs_product_code']); ?>" autocomplete="off" autocorrect="off" autofocus />
        </div>
        <div class="form-group">
            <label for="cbs_hash_secret_key"><?php p($l->t('Hash secret key')); ?></label>
            <input type="text" name="cbs_hash_secret_key" id="cbs_hash_secret_key" placeholder="Enter hash secret key"
                value="<?php p($_['cbs_hash_secret_key']); ?>" autocomplete="off" autocorrect="off" autofocus />
        </div>
        <div class="form-group" style="margin-left: 10px;">
            <label></label>
            <button type="submit" id="config-packagemanager-submit">
                <span><?php p($l->t('Save')); ?></span>
            </button>
        </div>
    </fieldset> 
</form>