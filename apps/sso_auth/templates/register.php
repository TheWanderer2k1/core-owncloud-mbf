<?php
/** @var $_ array */
/** @var $l \OCP\IL10N */
script('sso_auth', 'register');
style('sso_auth', 'auth');
?>

<form id="sso-auth-register-form" action="<?php p(\OC::$server->getURLGenerator()->linkToRoute('sso_auth.register.register')); ?>" method="post" autocapitalize="none">
    <input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>">
    <div id="general-error" style="color: #ff6b6b; background: rgba(255, 107, 107, 0.1); border: 1px solid #ff6b6b; border-radius: 8px; padding: 10px; margin-bottom: 20px; display: none; text-align: center;"></div>

    <div id="input-box" class="grouptop" style="position: relative; margin-bottom: 25px;">
        <label for="email"><?php p($l->t('Email')); ?></label>
        <input style="border-radius: 8px" type="email" name="email" id="email"
            placeholder="<?php p($l->t('Nhập email')); ?>"
            value="" autocomplete="off" autocorrect="off" autofocus>
        <span class="error-message" id="email-error" style="color: #ff6b6b; font-size: 12px; display: none; position: absolute; left: 0; top: 100%; margin-top: 3px;"></span>
    </div>

    <div id="input-box" class="groupmiddle" style="position: relative; margin-bottom: 25px;">
        <label for="phoneNumber"><?php p($l->t('Phone number')); ?></label>
        <input style="border-radius: 8px" type="tel" name="phoneNumber" id="phoneNumber"
            placeholder="<?php p($l->t('0912345678')); ?>"
            value="" autocomplete="off" autocorrect="off">
        <span class="error-message" id="phone-error" style="color: #ff6b6b; font-size: 12px; display: none; position: absolute; left: 0; top: 100%; margin-top: 3px;"></span>
    </div>

    <div id="input-box" class="groupmiddle" style="position: relative; margin-bottom: 40px;">
        <label for="password"><?php p($l->t('Password')); ?></label>
        <input style="border-radius: 8px" type="password" name="password" id="password"
            placeholder="<?php p($l->t('••••••••')); ?>"
            value="" autocomplete="off" autocorrect="off">
        <span class="error-message" id="password-error" style="color: #ff6b6b; font-size: 12px; display: none; position: absolute; left: 0; top: 100%; margin-top: 3px"></span>
    </div>

    <div id="input-box" class="groupbottom" style="position: relative; margin-bottom: 25px;">
        <label for="confirmPassword"><?php p($l->t('Confirm password')); ?></label>
        <input style="border-radius: 8px" type="password" name="confirmPassword" id="confirmPassword"
            placeholder="<?php p($l->t('••••••••')); ?>"
            value="" autocomplete="off" autocorrect="off">
        <span class="error-message" id="confirm-password-error" style="color: #ff6b6b; font-size: 12px; display: none; position: absolute; left: 0; top: 100%; margin-top: 3px;"></span>
    </div>

    <div class="submit-wrap">
        <button style="border: none;
                        outline: none;
                        border-radius: 8px;
                        background: #fa709a;"
                type="submit" id="sso-auth-register-submit">
            <span><?php p($l->t('Đăng ký')); ?></span>
            <div class="loading-spinner"><div></div><div></div><div></div><div></div></div>
        </button>
    </div>

    <div class="remember-login-container">
        <p style="color: #fff; margin-top: 20px;">
            <?php p($l->t('Already have an account?')); ?>
            <a href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('core.login.showLoginForm')); ?>"
               style="color: #fff; text-decoration: underline; font-weight: bold;">
                <?php p($l->t('Login now')); ?>
            </a>
        </p>
    </div>
</form>