<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="card">
    <div class="finger">👆</div>
    <h1>تسجيل الدخول</h1>
    <p class="muted">استخدم بصمة الإصبع أو Face ID أو قفل الجهاز.</p>

    <button id="loginBtn" class="primary">الدخول ببصمة الإصبع أو Face ID</button>
    <div id="message" class="message"></div>

    <a class="link" href="register.php">إضافة شخص جديد</a>
</main>

<script src="assets/app.js"></script>
<script>
document.getElementById('loginBtn').addEventListener('click', async () => {
    const message = document.getElementById('message');
    const button = document.getElementById('loginBtn');

    try {
        button.disabled = true;
        await ensureBiometricSupport();
        message.textContent = 'جاري التحقق من البصمة...';

        const options = await postJson('api/login-options.php', {});
        const publicKey = preparePublicKey(options.publicKey);

        const credential = await navigator.credentials.get({
            publicKey: publicKey
        });

        const payload = {
            id: bufferToBase64(credential.rawId),
            clientDataJSON: bufferToBase64(credential.response.clientDataJSON),
            authenticatorData: bufferToBase64(credential.response.authenticatorData),
            signature: bufferToBase64(credential.response.signature),
            userHandle: credential.response.userHandle
                ? bufferToBase64(credential.response.userHandle)
                : null
        };

        const result = await postJson('api/login-verify.php', payload);

        if (!result.success) throw new Error(result.message || 'فشل تسجيل الدخول');

        window.location.href = 'welcome.php';
    } catch (e) {
        message.textContent = friendlyError(e);
        button.disabled = false;
    }
});
</script>
</body>
</html>
