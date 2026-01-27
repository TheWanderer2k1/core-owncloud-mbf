<?php
/** @var $_ array */
script('packagemanager', 'config');
style('packagemanager', 'config');
?>
<form id="packagemanager-config-form" action="<?php p(\OC::$server->getURLGenerator()->linkToRoute('packagemanager.config.save')); ?>" method="post">
    <input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>">
    <label for="cbs_admin_user">CBS_ADMIN_USER</label>
    <input type="text" name="cbs_admin_user" id="cbs_admin_user" placeholder="CBS ADMIN USERNAME" value="<?php p($_['cbs_admin_user']); ?>" autocomplete="off" autocorrect="off" autofocus /><br>
    <label for="cbs_admin_pass">CBS_ADMIN_PASS</label>
    <input type="text" name="cbs_admin_pass" id="cbs_admin_pass" placeholder="CBS ADMIN PASSWORD" value="<?php p($_['cbs_admin_pass']); ?>" autocomplete="off" autocorrect="off" autofocus /><br>
    <label for="cbs_api_base_url">CBS_API_BASE_URL</label>
    <input type="url" name="cbs_api_base_url" id="cbs_api_base_url" placeholder="CBS API URL" value="<?php p($_['cbs_api_base_url']); ?>" autocomplete="off" autocorrect="off" autofocus /><br>
    <label for="cbs_product_code">CBS_PRODUCT_CODE</label>
    <input type="text" name="cbs_product_code" id="cbs_product_code" placeholder="CBS PRODUCT CODE" value="<?php p($_['cbs_product_code']); ?>" autocomplete="off" autocorrect="off" autofocus /><br>
    <label for="cbs_hash_secret_key">CBS_HASH_SECRET_KEY</label>
    <input type="text" name="cbs_hash_secret_key" id="cbs_hash_secret_key" placeholder="CBS HASH SECRET KEY" value="<?php p($_['cbs_hash_secret_key']); ?>" autocomplete="off" autocorrect="off" autofocus /><br>
    <button type="submit" id="config-packagemanager-submit">
        Submit
    </button>
</form>