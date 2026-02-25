<?php
/**
 * Package Manager - History Template
 */

script('packagemanager', 'history');
style('packagemanager', 'packages');

$l = $_['l'];
?>

<div id="app-navigation">
	<ul>
		<li>
			<a href="<?php p(\OCP\Util::linkToRoute('packagemanager.page.index')); ?>">
				<span class="icon icon-files"></span><?php p($l->t('All package')); ?>
			</a>
		</li>
		<li class="active">
			<a href="<?php p(\OCP\Util::linkToRoute('packagemanager.page.history')); ?>">
				<span class="icon icon-history"></span><?php p($l->t('History')); ?>
			</a>
		</li>
	</ul>
</div>

<div id="app-content">
	<div id="controls">
		<div id="breadcrumb" class="breadcrumb">
			<div class="crumb">
				<a href="#"><?php p($l->t('History')); ?></a>
			</div>
		</div>
		<div class="actions creatable">
			<div style="display:flex; align-items:center; gap:10px;">
				<input type="text" id="history-search"
					placeholder="<?php p($l->t('Search by user, package, action...')); ?>"
					style="min-width:280px;">
				<button id="history-search-btn" class="button">
					<span class="icon icon-search"></span>
					<span><?php p($l->t('Search')); ?></span>
				</button>
				<button id="history-reset-btn" class="button">
					<span><?php p($l->t('Reset')); ?></span>
				</button>
			</div>
		</div>
	</div>

	<table id="history-table" class="list-container grid">
		<thead>
			<tr>
				<th class="history-col"><?php p($l->t('User')); ?></th>
				<th class="history-col"><?php p($l->t('Action')); ?></th>
				<th class="history-col"><?php p($l->t('Package name')); ?></th>
				<th class="history-col"><?php p($l->t('Code')); ?></th>
				<th class="history-col"><?php p($l->t('Quota')); ?></th>
				<th class="history-col"><?php p($l->t('Price')); ?></th>
				<th class="history-col"><?php p($l->t('Duration')); ?></th>
				<th class="history-col"><?php p($l->t('Expiry date')); ?></th>
				<th class="history-col"><?php p($l->t('Date')); ?></th>
			</tr>
		</thead>
		<tbody id="history-list">
			<tr>
				<td colspan="9" style="text-align:center; padding:20px;"><?php p($l->t('Loading...')); ?></td>
			</tr>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="9">
					<div id="history-pagination" style="display:flex; align-items:center; justify-content:space-between; padding:12px 0;">
						<span id="history-total-info" style="color:#777; font-size:13px;"></span>
						<div style="display:flex; gap:6px; align-items:center;">
							<button id="history-prev" class="button" disabled><?php p($l->t('Previous')); ?></button>
							<span id="history-page-info" style="padding:0 12px; color:#333; font-size:13px;"></span>
							<button id="history-next" class="button" disabled><?php p($l->t('Next')); ?></button>
						</div>
					</div>
				</td>
			</tr>
		</tfoot>
	</table>
</div>
