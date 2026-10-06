<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

requirePost();
requireHttps();

$state = $_SESSION['registration'] ?? null;
unset($_SESSION['registration']);
if (!$state || time() - (int)($state['issued_at'] ?? 0) > 300) {
    jsonResponse(['success' => false, 'message' => 'انتهت جلسة التسجيل. أعد المحاولة.'], 400);
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

$pdo = null;
try {
    $clientDataJSON = base64_decode((string)($input['clientDataJSON'] ?? ''), true);
    $attestationObject = base64_decode((string)($input['attestationObject'] ?? ''), true);
    $challenge = base64_decode((string)$state['challenge'], true);

    if ($clientDataJSON === false || $attestationObject === false || $challenge === false) {
        throw new RuntimeException('بيانات التسجيل غير صالحة.');
    }

    validateClientOrigin($clientDataJSON);
    $webAuthn = new \lbuchs\WebAuthn\WebAuthn(APP_NAME, rpId(), null, true);

    $data = $webAuthn->processCreate(
        $clientDataJSON,
        $attestationObject,
        $challenge,
        true,
        true,
        false,
        false
    );

    $pdo = db();
    $pdo->beginTransaction();
    $userHandle = base64_decode((string)$state['user_handle'], true);
    if ($userHandle === false) throw new RuntimeException('هوية التسجيل غير صالحة');
    db()->prepare('INSERT INTO users (name, user_handle) VALUES (?, ?)')->execute([$state['name'], $userHandle]);
    $userId = (int)db()->lastInsertId();
    $stmt = db()->prepare(
        'INSERT INTO credentials
         (user_id, credential_id, credential_public_key, signature_counter)
         VALUES (?, ?, ?, ?)'
    );

    $stmt->execute([
        $userId,
        $data->credentialId,
        $data->credentialPublicKey,
        (int)($data->signatureCounter ?? 0)
    ]);

    db()->commit();

    jsonResponse(['success' => true, 'message' => 'تم تسجيل البصمة بنجاح.']);
} catch (Throwable $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    jsonResponse([
        'success' => false,
        'message' => 'فشل التحقق من البصمة. تأكد من استخدام نفس الموقع HTTPS وحاول مرة أخرى.'
    ], 400);
}
