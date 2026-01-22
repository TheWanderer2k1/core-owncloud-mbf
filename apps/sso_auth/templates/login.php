<?php
/** @var $_ array */
/** @var $l \OCP\IL10N */
script('sso_auth', 'login');
style('sso_auth', 'auth');
?>
<div class="sso-auth-login-container">
    <!-- Logo -->
    <div class="auth-logo">
        <img src="<?php p(image_path('core', 'logo-icon.svg')); ?>" alt="Logo">
        <h2 class="app-name"><?php p($l->t('MobiDrive'));?></h2>
    </div>

    <!-- Toggle between Login and Register -->
    <div class="auth-toggle">
        <span class="auth-toggle-btn active">
            <?php p($l->t('Đăng nhập')); ?>
        </span>
        <a href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('sso_auth.register.index')); ?>"
           class="auth-toggle-btn">
            <?php p($l->t('Đăng ký')); ?>
        </a>
    </div>

    <form id="sso-auth-login-form" action="<?php p(\OC::$server->getURLGenerator()->linkToRoute('sso_auth.register.login')); ?>" method="post" autocapitalize="none" novalidate>
        <input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>">

        <div class="form-group">
            <label for="ssoIdentifier"><?php p($l->t('Email hoặc Số điện thoại')); ?></label>
            <input type="text" name="ssoIdentifier" id="ssoIdentifier" placeholder="<?php p($l->t('your@email.com hoặc 0912345678')); ?>"
                value="<?php p($_['email']) ?>" autocomplete="off" autocorrect="off" autofocus required />
        </div>

        <div class="form-group">
            <label for="password"><?php p($l->t('Mật khẩu')); ?></label>
            <input type="password" name="password" id="password" placeholder="<?php p($l->t('••••••••')); ?>"
                value="" autocomplete="off" autocorrect="off" required />
        </div>

        <div class="form-group">
            <button type="submit" id="sso-auth-login-submit">
                <?php p($l->t('Đăng nhập')); ?>
            </button>
        </div>
    </form>
</div>