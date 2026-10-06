<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إضافة شخص</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="card">
    <div class="finger">🆕</div>
    <h1>إضافة شخص جديد</h1>

    <label for="name">اسم الشخص</label>
    <input id="name" type="text" maxlength="150" placeholder="مثال: أحمد محمد" autocomplete="name">

    <button id="registerBtn" class="primary">تسجيل بصمة الإصبع أو Face ID</button>
    <div id="message" class="message"></div>

    <a class="link" href="index.php">العودة لتسجيل الدخول</a>
</main>

<script src="assets/app.js"></script>
<script>
document.getElementById('registerBtn').addEventListener('click', async () => {
    const name = document.getElementById('name').value.trim();
    const message = document.getElementById('message');
    const button = document.getElementById('registerBtn');

    if (!name) {
        message.textContent = 'اكتب اسم الشخص أولاً.';
        return;
    }

    try {
        button.disabled = true;
        await ensureBiometricSupport();
        message.textContent = 'اتبع تعليمات الهاتف وسجّل البصمة...';

        const options = await postJson('api/register-options.php', { name });
        const publicKey = preparePublicKey(options.publicKey);

        const credential = await navigator.credentials.create({
            publicKey: publicKey
        });

        const payload = {
            clientDataJSON: bufferToBase64(credential.response.clientDataJSON),
            attestationObject: bufferToBase64(credential.response.attestationObject)
        };

        const result = await postJson('api/register-verify.php', payload);

        if (!result.success) throw new Error(result.message || 'فشل التسجيل');

        message.textContent = 'تم تسجيل الشخص والبصمة بنجاح ✅';
        document.getElementById('name').value = '';
    } catch (e) {
        message.textContent = friendlyError(e);
    } finally {
        button.disabled = false;
    }
});
</script>
</body>
</html>
