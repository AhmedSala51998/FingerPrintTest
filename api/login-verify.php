<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

requirePost();
requireHttps();

$challengeB64 = $_SESSION['login_challenge'] ?? null;
if (!$challengeB64) {
    jsonResponse(['success' => false, 'message' => 'انتهت جلسة الدخول. أعد المحاولة.'], 400);
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

try {
    $id = base64_decode((string)($input['id'] ?? ''), true);
    $clientDataJSON = base64_decode((string)($input['clientDataJSON'] ?? ''), true);
    $authenticatorData = base64_decode((string)($input['authenticatorData'] ?? ''), true);
    $signature = base64_decode((string)($input['signature'] ?? ''), true);
    $userHandle = !empty($input['userHandle'])
        ? base64_decode((string)$input['userHandle'], true)
        : null;

    if ($id === false || $clientDataJSON === false || $authenticatorData === false || $signature === false) {
        throw new RuntimeException('بيانات الدخول غير صالحة.');
    }

    $stmt = db()->prepare(
        'SELECT c.id AS credential_row_id, c.user_id, c.credential_public_key,
                c.signature_counter, u.user_handle, u.name
         FROM credentials c
         INNER JOIN users u ON u.id = c.user_id
         WHERE c.credential_id = ?
         LIMIT 1'
    );
    $stmt->execute([$id]);
    $credential = $stmt->fetch();

    if (!$credential) {
        throw new RuntimeException('هذه البصمة غير مسجلة على هذا الموقع.');
    }

    if ($userHandle !== null && !hash_equals((string)$credential['user_handle'], $userHandle)) {
        throw new RuntimeException('هوية المستخدم لا تتطابق.');
    }

    $webAuthn = new \lbuchs\WebAuthn\WebAuthn(APP_NAME, rpId());

    $ok = $webAuthn->processGet(
        $clientDataJSON,
        $authenticatorData,
        $signature,
        $credential['credential_public_key'],
        base64_decode($challengeB64, true),
        (int)$credential['signature_counter'],
        true,
        true
    );

    if (!$ok) {
        throw new RuntimeException('فشل التحقق.');
    }

    $newCounter = $webAuthn->getSignatureCounter();
    if ($newCounter !== null) {
        $update = db()->prepare(
            'UPDATE credentials
             SET signature_counter = ?, last_login_at = NOW()
             WHERE id = ?'
        );
        $update->execute([$newCounter, (int)$credential['credential_row_id']]);
    } else {
        $update = db()->prepare(
            'UPDATE credentials SET last_login_at = NOW() WHERE id = ?'
        );
        $update->execute([(int)$credential['credential_row_id']]);
    }

    $_SESSION['logged_in_user_id'] = (int)$credential['user_id'];
    unset($_SESSION['login_challenge']);

    jsonResponse(['success' => true, 'name' => $credential['name']]);
} catch (Throwable $e) {
    unset($_SESSION['login_challenge']);
    jsonResponse([
        'success' => false,
        'message' => 'البصمة غير مطابقة أو لم يتم تسجيلها على هذا الموقع.'
    ], 401);
}
