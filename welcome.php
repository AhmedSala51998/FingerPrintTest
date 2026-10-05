<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

if (empty($_SESSION['logged_in_user_id'])) {
    header('Location: index.php');
    exit;
}

$stmt = db()->prepare('SELECT name FROM users WHERE id = ? LIMIT 1');
$stmt->execute([(int)$_SESSION['logged_in_user_id']]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION = [];
    header('Location: index.php');
    exit;
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>مرحبًا</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="card">
    <div class="success">✓</div>
    <h1>مرحبًا، <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?> 👋</h1>
    <p class="muted">تم التحقق من هويتك بالبصمة بنجاح.</p>
    <a class="link" href="logout.php">تسجيل الخروج</a>
</main>
</body>
</html>
