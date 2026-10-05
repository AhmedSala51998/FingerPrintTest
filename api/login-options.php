<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

requirePost();
requireHttps();

try {
    $webAuthn = new \lbuchs\WebAuthn\WebAuthn(APP_NAME, rpId());

    /*
     * لا نرسل allowCredentials.
     * الهاتف يبحث عن Passkey/بصمة مسجلة لهذا الدومين.
     */
    $args = $webAuthn->getGetArgs(
        [],
        120,
        false,
        false,
        false,
        true,
        true,
        true
    );

    $_SESSION['login_challenge'] = base64_encode($webAuthn->getChallenge());

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($args, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'message' => 'تعذر بدء تسجيل الدخول.'], 500);
}
