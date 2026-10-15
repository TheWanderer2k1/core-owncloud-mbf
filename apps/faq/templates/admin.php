<?php
/** @var $_ array */
/** @var $l \OCP\IL10N */
script('faq', 'admin');
style('faq', 'admin');
?>
<div class="section" id="faq-section">
    <div class="faq-header-bar">
        <h2 class="faq-title"><?php p($l->t('FAQ Management')); ?></h2>
    </div>

    <!-- Filters and Action bar -->
    <div class="faq-actions-bar">
        <div class="faq-filters">
            <div class="faq-search-wrapper">
                <input type="text" id="faq-search" placeholder="<?php p($l->t('Search by question title')); ?>" />
            </div>
            <div class="faq-status-wrapper">
                <select id="faq-status-filter">
                    <option value="all"><?php p($l->t('Status')); ?></option>
                    <option value="1"><?php p($l->t('Show')); ?></option>
                    <option value="0"><?php p($l->t('Hide')); ?></option>
                </select>
            </div>
        </div>
        <button id="faq-btn-create" class="faq-btn faq-btn-primary">
            <span><?php p($l->t('Create')); ?></span>
        </button>
    </div>

    <!-- FAQ List Table -->
    <div class="faq-table-container">
        <table class="faq-table" id="faq-table-element">
            <thead>
                <tr>
                    <th class="col-stt">STT</th>
                    <th class="col-question"><?php p($l->t('Question name')); ?></th>
                    <th class="col-status"><?php p($l->t('Status')); ?></th>
                    <th class="col-author"><?php p($l->t('Updated by')); ?></th>
                    <th class="col-date"><?php p($l->t('Updated at')); ?></th>
                    <th class="col-actions"><?php p($l->t('Actions')); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($_['faqs'])): ?>
                    <tr class="faq-no-data">
                        <td colspan="6"><?php p($l->t('No data found')); ?></td>
                    </tr>
                <?php else: ?>
                    <?php $index = 1; foreach ($_['faqs'] as $faq): ?>
                        <tr class="faq-row" 
                            data-id="<?php p($faq->getId()); ?>" 
                            data-question="<?php p($faq->getQuestion()); ?>" 
                            data-answer="<?php p($faq->getAnswer()); ?>" 
                            data-status="<?php p($faq->getStatus()); ?>">
                            <td class="col-stt faq-row-index"><?php p($index++); ?></td>
                            <td class="col-question faq-row-question"><?php p($faq->getQuestion()); ?></td>
                            <td class="col-status">
                                <?php if ($faq->getStatus() === 1): ?>
                                    <span class="faq-badge badge-active"><?php p($l->t('Show')); ?></span>
                                <?php else: ?>
                                    <span class="faq-badge badge-inactive"><?php p($l->t('Hide')); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="col-author"><?php p($faq->getUpdatedBy()); ?></td>
                            <td class="col-date"><?php p(date('d/m/Y', $faq->getUpdatedDate())); ?></td>
                            <td class="col-actions">
                                <button class="faq-action-btn faq-edit-btn" title="<?php p($l->t('Edit')); ?>">
                                    <span class="icon icon-rename"></span>
                                </button>
                                <button class="faq-action-btn faq-delete-btn" title="<?php p($l->t('Delete')); ?>">
                                    <span class="icon icon-delete"></span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Dialog Popup Overlay -->
<div id="faq-modal" class="faq-modal-overlay faq-hidden">
    <div class="faq-modal-dialog">
        <div class="faq-modal-header">
            <h3 id="faq-modal-title"><?php p($l->t('Thêm mới trợ giúp')); ?></h3>
            <button class="faq-modal-close" id="faq-modal-btn-close">&times;</button>
        </div>
        <form id="faq-modal-form" novalidate>
            <input type="hidden" id="faq-field-id" value="" />
            
            <div class="faq-modal-body">
                <div class="faq-form-group">
                    <label for="faq-field-question" class="faq-label required"><?php p($l->t('Question')); ?></label>
                    <textarea id="faq-field-question" rows="3" placeholder="<?php p($l->t('Enter question')); ?>" required></textarea>
                </div>
                
                <div class="faq-form-group">
                    <label for="faq-field-answer" class="faq-label required"><?php p($l->t('Answer')); ?></label>
                    <textarea id="faq-field-answer" rows="5" placeholder="<?php p($l->t('Enter answer')); ?>" required></textarea>
                </div>

                <div class="faq-form-group">
                    <label for="faq-field-status" class="faq-label"><?php p($l->t('Status')); ?></label>
                    <select id="faq-field-status">
                        <option value="1"><?php p($l->t('Show')); ?></option>
                        <option value="0"><?php p($l->t('Hide')); ?></option>
                    </select>
                </div>
            </div>
            
            <div class="faq-modal-footer">
                <button type="button" class="faq-btn faq-btn-secondary" id="faq-modal-btn-cancel"><?php p($l->t('Cancel')); ?></button>
                <button type="submit" class="faq-btn faq-btn-primary" id="faq-modal-btn-save">
                    <span><?php p($l->t('Save')); ?></span>
                </button>
            </div>
        </form>
    </div>
</div>
