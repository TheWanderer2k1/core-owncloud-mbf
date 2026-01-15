<?php
/**
 * Package Manager - Main Template
 */

script('packagemanager', 'packages');
style('packagemanager', 'packages');
// Include core scripts for dialogs
vendor_script('handlebars/handlebars');
script('core', 'oc-dialogs');
script('core', 'jquery.ocdialog');

$l = $_['l'];
?>

<div id="app-navigation">
	<ul>
		<li class="active">
			<a href="#"><span class="icon icon-files"></span><?php p($l->t('All package')); ?></a>
		</li>
	</ul>
</div>

<div id="app-content">
	<div id="controls">
		<div id="breadcrumb" class="breadcrumb">
			<div class="crumb">
				<a href="#"><?php p($l->t('All package')); ?></a>
			</div>
		</div>
		<div class="actions creatable">
			<button class="button new-package">
				<span class="icon icon-add"></span>
				<span><?php p($l->t('New package')); ?></span>
			</button>
			<span class="selectedActions hidden">
				<button class="button delete-selected">
					<span class="icon icon-delete"></span>
					<span><?php p($l->t('Delete')); ?></span>
				</button>
			</span>
		</div>
	</div>

	<table id="filestable" class="list-container grid">
		<thead>
			<tr>
				<th id="headerName" class="column-name">
					<div id="headerName-container">
						<input type="checkbox" id="select_all_packages" class="select-all checkbox" />
						<label for="select_all_packages" class="hidden-visually"><?php p($l->t('Select all')); ?></label>
						<a class="name sort columntitle" data-sort="name"><span><?php p($l->t('Package name')); ?></span><span class="sort-indicator"></span></a>
					</div>
				</th>
				<th id="headerCode" class="column-code">
					<a class="code sort columntitle" data-sort="code"><span><?php p($l->t('Code')); ?></span><span class="sort-indicator"></span></a>
				</th>
				<th id="headerPrice" class="column-price">
					<a class="price sort columntitle" data-sort="price"><span><?php p($l->t('Price')); ?></span><span class="sort-indicator"></span></a>
				</th>
				<th id="headerDuration" class="column-duration">
					<a class="duration sort columntitle" data-sort="duration"><span><?php p($l->t('Duration')); ?></span><span class="sort-indicator"></span></a>
				</th>
				<th id="headerUnit" class="column-unit">
					<a id="modified" class="columntitle" data-sort="unit"><span><?php p($l->t('Unit')); ?></span><span class="sort-indicator"></span></a>
				</th>
				<th class="column-actions"></th>
			</tr>
		</thead>
		<tbody id="fileList">
			<tr class="loading">
				<td colspan="6" style="text-align: center; padding: 20px;"><?php p($l->t('Loading packages...')); ?></td>
			</tr>
		</tbody>
		<tfoot>
			<tr class="summary">
				<td colspan="6">
					<span class="info"></span>
				</td>
			</tr>
		</tfoot>
	</table>
</div>

<!-- Modal Form Template -->
<script id="package-form-template" type="text/template">
	<div id="package-form-dialog" title="<?php p($l->t('Create new package')); ?>">
		<form id="modal-package-form" class="package-form">
			<div class="form-group">
				<label for="modal-package-name"><?php p($l->t('Package name *')); ?></label>
				<input type="text" id="modal-package-name" name="name" required placeholder="<?php p($l->t('Enter package name')); ?>">
			</div>
			<div class="form-group">
				<label for="modal-package-code"><?php p($l->t('Package code *')); ?></label>
				<input type="text" id="modal-package-code" name="code" required placeholder="<?php p($l->t('Enter unique code')); ?>">
			</div>
			<div class="form-group">
				<label for="modal-package-price"><?php p($l->t('Price *')); ?></label>
				<input type="number" id="modal-package-price" name="price" step="1" required placeholder="0">
			</div>
			<div class="form-group">
				<label for="modal-package-duration"><?php p($l->t('Duration *')); ?></label>
				<input type="number" id="modal-package-duration" name="duration" required placeholder="0">
			</div>
			<div class="form-group">
				<label for="modal-package-unit"><?php p($l->t('Unit *')); ?></label>
				<select id="modal-package-unit" name="unit">
					<option value="day"><?php p($l->t('Day')); ?></option>
					<option value="month" selected><?php p($l->t('Month')); ?></option>
					<option value="year"><?php p($l->t('Year')); ?></option>
				</select>
			</div>
		</form>
	</div>
</script>
