<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

requirePost();
requireHttps();

$state = $_SESSION['registration'] ?? null;
if (!$state) {
    jsonResponse(['success' => false, 'message' => 'انتهت جلسة التسجيل. أعد المحاولة.'], 400);
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

try {
    $clientDataJSON = base64_decode((string)($input['clientDataJSON'] ?? ''), true);
    $attestationObject = base64_decode((string)($input['attestationObject'] ?? ''), true);
    $challenge = base64_decode((string)$state['challenge'], true);

    if ($clientDataJSON === false || $attestationObject === false || $challenge === false) {
        throw new RuntimeException('بيانات التسجيل غير صالحة.');
    }

    $webAuthn = new \lbuchs\WebAuthn\WebAuthn(APP_NAME, rpId());

    $data = $webAuthn->processCreate(
        $clientDataJSON,
        $attestationObject,
        $challenge,
        true,
        true,
        false,
        false
    );

    $stmt = db()->prepare(
        'INSERT INTO credentials
         (user_id, credential_id, credential_public_key, signature_counter)
         VALUES (?, ?, ?, ?)'
    );

    $stmt->execute([
        (int)$state['user_id'],
        $data->credentialId,
        $data->credentialPublicKey,
        (int)($data->signatureCounter ?? 0)
    ]);

    unset($_SESSION['registration']);

    jsonResponse(['success' => true, 'message' => 'تم تسجيل البصمة بنجاح.']);
} catch (Throwable $e) {
    jsonResponse([
        'success' => false,
        'message' => 'فشل التحقق من البصمة. تأكد من استخدام نفس الموقع HTTPS وحاول مرة أخرى.'
    ], 400);
}
