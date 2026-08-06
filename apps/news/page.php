<?php
// Simple news management page
// Path: apps/news/page.php

// Storage file for content
$dataDir = __DIR__ . '/data';
$dataFile = $dataDir . '/news.json';

if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0755, true);
}

$defaults = [
    'intro' => "Giới thiệu về MobiFone Drive",
    'policy' => "Điều khoản & chính sách bảo mật",
];

if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode($defaults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$data = json_decode(file_get_contents($dataFile), true) ?: $defaults;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key = isset($_POST['key']) ? $_POST['key'] : '';
    $value = isset($_POST['content']) ? $_POST['content'] : '';
    if (in_array($key, ['intro', 'policy'])) {
        $data[$key] = $value;
        file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $saved = true;
    }
}

?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>News management</title>
    <style>
        body{font-family: "Helvetica Neue", Arial, sans-serif; margin:20px; color:#333}
        .panel{border:1px solid #e6e6e6;padding:18px;border-radius:4px;background:#fff}
        .row{display:flex;align-items:flex-start;padding:12px 0;border-bottom:1px solid #f0f0f0}
        .row:last-child{border-bottom:none}
        .index{width:40px;color:#999}
        .title{width:260px;font-weight:600}
        .editor{flex:1}
        textarea{width:100%;min-height:120px;padding:8px;border:1px solid #ddd;border-radius:3px}
        .actions{margin-top:8px}
        .btn{display:inline-block;padding:6px 12px;border-radius:3px;background:#0a66c2;color:#fff;text-decoration:none;border:none;cursor:pointer}
        .note{color:green;margin-left:12px}
    </style>
</head>
<body>
    <h2>News management</h2>
    <div class="panel">
        <form method="post">
            <div class="row">
                <div class="index">1</div>
                <div class="title">Giới thiệu về MobiFone Drive</div>
                <div class="editor">
                    <textarea name="content"><?php echo htmlspecialchars($data['intro'], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?></textarea>
                    <div class="actions">
                        <input type="hidden" name="key" value="intro">
                        <button class="btn" type="submit">Cập nhật</button>
                        <?php if (!empty($saved) && isset($key) && $key === 'intro'): ?>
                            <span class="note">Đã lưu</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>

        <form method="post">
            <div class="row">
                <div class="index">2</div>
                <div class="title">Điều khoản & chính sách bảo mật</div>
                <div class="editor">
                    <textarea name="content"><?php echo htmlspecialchars($data['policy'], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?></textarea>
                    <div class="actions">
                        <input type="hidden" name="key" value="policy">
                        <button class="btn" type="submit">Cập nhật</button>
                        <?php if (!empty($saved) && isset($key) && $key === 'policy'): ?>
                            <span class="note">Đã lưu</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>
    </div>

</body>
</html>
