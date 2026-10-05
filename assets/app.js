function base64ToBuffer(base64) {
    // يدعم Base64 و Base64URL
    base64 = base64.replace(/-/g, '+').replace(/_/g, '/');

    while (base64.length % 4) {
        base64 += '=';
    }

    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);

    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }

    return bytes.buffer;
}

function bufferToBase64(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';

    for (let i = 0; i < bytes.length; i++) {
        binary += String.fromCharCode(bytes[i]);
    }

    return btoa(binary)
        .replace(/\+/g, '-')
        .replace(/\//g, '_')
        .replace(/=+$/g, '');
}

function preparePublicKey(options) {

    // challenge
    if (typeof options.challenge === 'string') {
        options.challenge = base64ToBuffer(options.challenge);
    }

    // user.id في التسجيل
    if (options.user && typeof options.user.id === 'string') {
        options.user.id = base64ToBuffer(options.user.id);
    }

    // excludeCredentials
    if (Array.isArray(options.excludeCredentials)) {
        options.excludeCredentials =
            options.excludeCredentials.map(credential => ({
                ...credential,
                type: 'public-key',
                id: typeof credential.id === 'string'
                    ? base64ToBuffer(credential.id)
                    : credential.id
            }));
    }

    // allowCredentials
    if (Array.isArray(options.allowCredentials)) {
        options.allowCredentials =
            options.allowCredentials.map(credential => ({
                ...credential,
                type: 'public-key',
                id: typeof credential.id === 'string'
                    ? base64ToBuffer(credential.id)
                    : credential.id
            }));
    }

    return options;
}

async function postJson(url, data) {

    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify(data)
    });

    const result = await response.json();

    if (!response.ok) {
        throw new Error(result.message || 'حدث خطأ في الخادم.');
    }

    return result;
}

function friendlyError(error) {

    console.error(error);

    if (error.name === 'NotAllowedError') {
        return 'تم إلغاء البصمة أو لم يتم التحقق منها.';
    }

    if (error.name === 'SecurityError') {
        return 'يجب فتح الموقع عبر HTTPS.';
    }

    if (error.name === 'InvalidStateError') {
        return 'البصمة مسجلة مسبقًا على هذا الجهاز.';
    }

    if (error.name === 'NotSupportedError') {
        return 'هذا الجهاز أو المتصفح لا يدعم تسجيل الدخول بالبصمة.';
    }

    return error.message || 'حدث خطأ أثناء التحقق من البصمة.';
}