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
            <div class="password-input-wrapper">
                <input type="password" name="password" id="password" placeholder="<?php p($l->t('••••••••')); ?>"
                    value="" autocomplete="off" autocorrect="off" required />
                <button type="button" class="password-toggle-btn" id="password-toggle" aria-label="Toggle password visibility">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="20" height="20">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="form-group">
            <button type="submit" id="sso-auth-login-submit">
                <?php p($l->t('Đăng nhập')); ?>
            </button>
        </div>
    </form>
</div>