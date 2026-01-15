<?php
/**
 * Package Manager - Main Template
 */

script('packagemanager', 'packages');
style('packagemanager', 'packages');

$l = $_['l'];
?>

<div id="app-navigation">
	<ul>
		<li><a href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('packagemanager.page.index')); ?>"><?php p($l->t('Packages')); ?></a></li>
	</ul>
</div>

<div id="app-content">
	<div id="app-content-wrapper">
		<div id="controls">
			<div class="actions creatable">
				<a href="#" class="button new">
					<span class="icon icon-add"></span>
					<span><?php p($l->t('New Package')); ?></span>
				</a>
			</div>
		</div>
		
		<div id="new-package-form" class="section" style="display: none;">
			<h2><?php p($l->t('Create New Package')); ?></h2>
			<form id="package-form">
				<div class="form-group">
					<label for="package-name"><?php p($l->t('Package Name')); ?> *</label>
					<input type="text" id="package-name" name="name" required placeholder="<?php p($l->t('Enter package name')); ?>">
				</div>
				
				<div class="form-group">
					<label for="package-code"><?php p($l->t('Package Code')); ?> *</label>
					<input type="text" id="package-code" name="code" required placeholder="<?php p($l->t('Enter unique package code')); ?>">
				</div>
				
				<div class="form-group">
					<label for="package-price"><?php p($l->t('Price')); ?> *</label>
					<input type="number" id="package-price" name="price" step="0.01" required placeholder="<?php p($l->t('0.00')); ?>">
				</div>
				
				<div class="form-group">
					<label for="package-duration"><?php p($l->t('Duration')); ?> *</label>
					<input type="number" id="package-duration" name="duration" required placeholder="<?php p($l->t('Enter duration')); ?>">
				</div>
				
				<div class="form-group">
					<label for="package-unit"><?php p($l->t('Unit')); ?> *</label>
					<select id="package-unit" name="unit" required>
						<option value="day"><?php p($l->t('Day')); ?></option>
						<option value="month" selected><?php p($l->t('Month')); ?></option>
						<option value="year"><?php p($l->t('Year')); ?></option>
					</select>
				</div>
				
				<div class="form-actions">
					<button type="submit" class="button primary"><?php p($l->t('Create Package')); ?></button>
					<button type="button" class="button cancel"><?php p($l->t('Cancel')); ?></button>
				</div>
			</form>
		</div>
		
		<table id="packages-table" class="grid">
			<thead>
				<tr>
					<th><?php p($l->t('Name')); ?></th>
					<th><?php p($l->t('Code')); ?></th>
					<th><?php p($l->t('Price')); ?></th>
					<th><?php p($l->t('Duration')); ?></th>
					<th><?php p($l->t('Unit')); ?></th>
					<th><?php p($l->t('Actions')); ?></th>
				</tr>
			</thead>
			<tbody id="packages-list">
				<tr class="loading">
					<td colspan="6"><?php p($l->t('Loading packages...')); ?></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>
