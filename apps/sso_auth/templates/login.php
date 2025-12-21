<?php
/** @var $_ array */
/** @var $l \OCP\IL10N */
script('sso_auth', 'login');
style('sso_auth', 'login');
?>
<div class="sso-auth-login-container">
    <h2 class="app-name"><?php p($l->t('SSO Authentication'));?></h2>
    <p>
        <em><?php p($l->t('Please log in using your Single Sign-On (SSO) credentials.')); ?></em>
    </p>
    <form id="sso-auth-login-form" action="<?php p(\OC::$server->getURLGenerator()->linkToRoute('sso_auth.register.login')); ?>" method="post" autocapitalize="none" novalidate>
        <input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>">
        <div class="form-group">
            <label for="ssoIdentifier"><?php p($l->t('Username or Phone Number')); ?></label>
            <input type="text" name="ssoIdentifier" id="ssoIdentifier" placeholder="<?php p($l->t('Enter your username or phone number')); ?>"
                value="" autocomplete="off" autocorrect="off" autofocus />
        </div>
        <div class="form-group">
            <label for="password"><?php p($l->t('Password')); ?></label>
            <input type="password" name="password" id="password" placeholder="<?php p($l->t('Enter your password')); ?>"
                value="" autocomplete="off" autocorrect="off" />
        </div>
        <div class="form-group" style="margin-left: 10px;">
            <label></label>
            <button type="submit" id="sso-auth-login-submit">
                <span><?php p($l->t('Log In')); ?></span>
            </button>
        </div>
    </form>
</div>