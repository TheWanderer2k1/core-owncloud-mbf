<?php
/** @var $_ array */
/** @var $l \OCP\IL10N */
script('news', 'admin');
style('news', 'admin');
?>
<form class="section" id="news-form" action="<?php p(\OC::$server->getURLGenerator()->linkToRoute('news.config.save')); ?>" method="post" autocapitalize="none" novalidate>
    <h2 class="app-name"><?php p($l->t('Quản lý tin tức'));?></h2>
    <input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>">

    <h3>1. Giới thiệu về MobiFone Drive</h3>
    <div class="form-group">
        <div class="wysiwyg-toolbar" data-editor="intro-editor">
            <button type="button" class="wysiwyg-btn" data-cmd="bold">B</button>
            <button type="button" class="wysiwyg-btn" data-cmd="italic"><em>I</em></button>
            <button type="button" class="wysiwyg-btn" data-cmd="underline"><u>U</u></button>
            <button type="button" class="wysiwyg-btn" data-cmd="createLink">Link</button>
        </div>
        <div id="intro-editor" class="wysiwyg-editor" contenteditable="true"><?php echo $_['intro']; ?></div>
        <textarea name="intro" id="intro-value" class="wysiwyg-source"><?php echo htmlspecialchars($_['intro'], ENT_QUOTES, 'UTF-8'); ?></textarea>
    </div>

    <h3>2. Điều khoản</h3>
    <div class="form-group">
        <div class="wysiwyg-toolbar" data-editor="terms-editor">
            <button type="button" class="wysiwyg-btn" data-cmd="bold">B</button>
            <button type="button" class="wysiwyg-btn" data-cmd="italic"><em>I</em></button>
            <button type="button" class="wysiwyg-btn" data-cmd="underline"><u>U</u></button>
            <button type="button" class="wysiwyg-btn" data-cmd="createLink">Link</button>
        </div>
        <div id="terms-editor" class="wysiwyg-editor" contenteditable="true"><?php echo $_['terms']; ?></div>
        <textarea name="terms" id="terms-value" class="wysiwyg-source"><?php echo htmlspecialchars($_['terms'], ENT_QUOTES, 'UTF-8'); ?></textarea>
    </div>

    <h3>3. Chính sách bảo mật</h3>
    <div class="form-group">
        <div class="wysiwyg-toolbar" data-editor="policy-editor">
            <button type="button" class="wysiwyg-btn" data-cmd="bold">B</button>
            <button type="button" class="wysiwyg-btn" data-cmd="italic"><em>I</em></button>
            <button type="button" class="wysiwyg-btn" data-cmd="underline"><u>U</u></button>
            <button type="button" class="wysiwyg-btn" data-cmd="createLink">Link</button>
        </div>
        <div id="policy-editor" class="wysiwyg-editor" contenteditable="true"><?php echo $_['policy']; ?></div>
        <textarea name="policy" id="policy-value" class="wysiwyg-source"><?php echo htmlspecialchars($_['policy'], ENT_QUOTES, 'UTF-8'); ?></textarea>
    </div>

    <div class="form-group">
        <button type="submit" id="news-save">
            <span><?php p($l->t('Lưu')); ?></span>
        </button>
    </div>
</form>
