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

    <button id="loginBtn" class="primary" type="button" style="display:none">إعادة فتح Face ID أو بصمة الإصبع</button>
    <div id="message" class="message"></div>

    <a class="link" href="register.php">إضافة شخص جديد</a>
</main>

<script src="assets/app.js"></script>
<script>
const message = document.getElementById('message');
const button = document.getElementById('loginBtn');
let pending = false;
let loginOptions = null;
let optionsCreatedAt = 0;
async function startBiometricLogin() {
    if (pending) return;
    pending = true;
    button.disabled = true;
    button.style.display = 'none';
    try {
        await ensureBiometricSupport();
        message.textContent = 'جاري فتح Face ID أو بصمة الإصبع...';
        if (!loginOptions || Date.now() - optionsCreatedAt > 240000) {
            loginOptions = preparePublicKey((await postJson('api/login-options.php', {})).publicKey);
            optionsCreatedAt = Date.now();
        }
        const credential = await navigator.credentials.get({publicKey: loginOptions});
        if (!credential) throw new Error('لم يكتمل التحقق. أعد المحاولة.');
        const payload = {
            id: bufferToBase64(credential.rawId),
            clientDataJSON: bufferToBase64(credential.response.clientDataJSON),
            authenticatorData: bufferToBase64(credential.response.authenticatorData),
            signature: bufferToBase64(credential.response.signature),
            userHandle: credential.response.userHandle ? bufferToBase64(credential.response.userHandle) : null
        };
        loginOptions = null;
        const result = await postJson('api/login-verify.php', payload);
        if (!result.success) throw new Error(result.message || 'فشل تسجيل الدخول');
        message.textContent = 'تم التحقق بنجاح، جاري الدخول...';
        window.location.href = 'welcome.php';
    } catch (error) {
        message.textContent = error.name === 'NotAllowedError'
            ? 'لم يكتمل الطلب التلقائي أو تم إلغاؤه. اضغط إعادة فتح التحقق، وتأكد أنك سجلت مفتاح مرور لهذا الموقع من إضافة شخص جديد.'
            : friendlyError(error);
        button.style.display = '';
    } finally {
        pending = false;
        button.disabled = false;
    }
}
button.addEventListener('click', startBiometricLogin);
// One automatic request per page load; retry stays under the user's control.
startBiometricLogin();
</script>
</body>
</html>
