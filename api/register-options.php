<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

requirePost();
requireHttps();

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$name = trim((string)($input['name'] ?? ''));

if ($name === '' || mb_strlen($name) > 150) {
    jsonResponse(['success' => false, 'message' => 'الاسم غير صحيح.'], 422);
}

$userHandle = random_bytes(32);
$userHandleB64 = base64_encode($userHandle);

$stmt = db()->prepare('INSERT INTO users (name, user_handle) VALUES (?, ?)');
$stmt->execute([$name, $userHandle]);
$userId = (int)db()->lastInsertId();

try {
    $webAuthn = new \lbuchs\WebAuthn\WebAuthn(APP_NAME, rpId());

    $args = $webAuthn->getCreateArgs(
        $userHandle,
        $name,
        $name,
        120,
        true,   // resident/discoverable credential
        true,   // require user verification (fingerprint/Face ID/PIN)
        false
    );

    $_SESSION['registration'] = [
        'user_id' => $userId,
        'user_handle' => $userHandleB64,
        'challenge' => base64_encode($webAuthn->getChallenge()),
    ];

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($args, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    db()->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
    jsonResponse(['success' => false, 'message' => 'تعذر بدء تسجيل البصمة.'], 500);
}
