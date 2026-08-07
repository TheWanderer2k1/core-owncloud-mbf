<?php
/** @var $_ array */
/** @var $l \OCP\IL10N */
script('news', 'admin');
style('news', 'admin');
script('news', 'ckeditor');
?>
<form class="section" id="news-form"
    action="<?php p(\OC::$server->getURLGenerator()->linkToRoute('news.config.save')); ?>" method="post"
    autocapitalize="none" novalidate>
    <div class="form-group">
        <h2 class="app-name" style="font-weight: bold;"><?php p($l->t('News management'));?></h2>
        <button type="submit" id="news-save">
            <span><?php p($l->t('Save')); ?></span>
        </button> 
    </div>
    <input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>">
    <div class="content">
        <h4 style="font-weight: bold;"><?php p($l->t('1. Introduction about MobiFone Drive'));?></h4>
        <div class="form-group">
            <textarea id="intro-editor"
                name="intro"><?php echo htmlspecialchars($_['intro'], ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
        <h4 style="font-weight: bold;"><?php p($l->t('2.Terms and Conditions'));?></h4>
        <div class="form-group">
            <textarea id="terms-editor"
                name="terms"><?php echo htmlspecialchars($_['terms'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
        <h4 style="font-weight: bold;"><?php p($l->t('3. Privacy Policy'));?></h4>
        <div class="form-group">
            <textarea id="policy-editor"
                name="policy"><?php echo htmlspecialchars($_['policy'], ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
    </div>    
</form>