<?php
/** @var $_ array */
/** @var $l \OCP\IL10N */
/* script('current_package', 'admin'); */
style('current_package', 'styles');
$data = null;

if (count($_['data']) === 1) {
    $is_has_package = true;
    $data = $_['data'][0];
    $status = $data['status'];
    $start_at = date('d/m/Y', $data['start_at']);
    $end_at = date('d/m/Y', $data['end_at']);
    $package_name = $data['package_name'];
    $quota = $data['quota'];
    $duration = $data['duration'];
    $unit = $data['unit'];

    // Format price
    $price = $data['price'];
    $price = number_format($price, 0, '', ',');
    $price = $price . ' VNĐ';
};

?>

<div id="current_package" class="section">
    <h2><?php p($l->t('Current Data Package')); ?><?php if($is_has_package) { echo ' (' . $package_name . ')'; }; ?></h2>
<?php if ($is_has_package): ?>
    <p>
        <label for="current_package_status"><?php p($l->t('Status')); ?>:</label>
        <span id="current_package_status"><?php p($l->t(ucfirst($status))); ?><span>
    </p>
    <p>
        <label for="current_package_name"><?php p($l->t('Package Name')); ?>:</label>
        <span id="current_package_name"><?php p($package_name) ?><span>
    </p>
    <p>
        <label for="current_package_price"><?php p($l->t('Price')); ?>:</label>
        <span id="current_package_price"><?php p($price) ?><span>
    </p>
    <p>
        <label for="current_package_quota"><?php p($l->t('Quota')); ?>:</label>
        <span id="current_package_quota"><?php p($quota) ?><span>
    </p>
    <p>
        <label for="current_package_duration"><?php p($l->t('Duration')); ?>:</label>
        <span id="current_package_duration"><?php p($duration) ?> <?php p($l->t(strtolower($unit) . ($duration>1 ? 's':''))) ?> (<?php p($l->t('since registration date'))?>)<span>
    </p>
    <p>
        <label for="current_package_regdate"><?php p($l->t('Registration Date')); ?>:</label>
        <span id="current_package_regdate"><?php p($start_at) ?><span>
    </p>
    <p>
        <label for="current_package_expdate"><?php p($l->t('Expire Date')); ?>:</label>
        <span id="current_package_expdate"><?php p($end_at) ?><span>
    </p>
<?php else: ?>
    <p><?php p($l->t('You have not registered any package')); ?></p>
<?php endif; ?>
</div>
