<?php
/** @var $_ array */
/** @var $l \OCP\IL10N */
script('sso_auth', 'register');
style('sso_auth', 'register');
?>
<div class="sso-auth-register-container">
    <h2 class="app-name"><?php p($l->t('SSO Authentication'));?></h2>
    <p>
        <em><?php p($l->t('Register a new account.')); ?></em>
    </p>
    <form id="sso-auth-register-form" action="<?php p(\OC::$server->getURLGenerator()->linkToRoute('sso_auth.register.register')); ?>" method="post" autocapitalize="none" novalidate>
        <input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>">
        <div class="form-group">
            <label for="email"><?php p($l->t('Email')); ?></label>
            <input type="text" name="email" id="email" placeholder="<?php p($l->t('Enter your email')); ?>"
                value="" autocomplete="off" autocorrect="off" autofocus />
            <label for="phoneNumber"><?php p($l->t('Phone Number')); ?></label>
            <input type="text" name="phoneNumber" id="phoneNumber" placeholder="<?php p($l->t('Enter your phone number')); ?>"
                value="" autocomplete="off" autocorrect="off" autofocus />
        </div>
        <div class="form-group">
            <label for="password"><?php p($l->t('Password')); ?></label>
            <input type="password" name="password" id="password" placeholder="<?php p($l->t('Enter your password')); ?>"
                value="" autocomplete="off" autocorrect="off" />
            <label for="confirmPassword"><?php p($l->t('Password')); ?></label>
            <input type="password" name="confirmPassword" id="confirmPassword" placeholder="<?php p($l->t('Confirm your password')); ?>"
                value="" autocomplete="off" autocorrect="off" />
        </div>
        <div class="form-group" style="margin-left: 10px;">
            <label></label>
            <button type="submit" id="sso-auth-register-submit">
                <span><?php p($l->t('Register')); ?></span>
            </button>
        </div>
    </form>
</div>