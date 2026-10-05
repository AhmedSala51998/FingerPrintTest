function bufferToBase64(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (const byte of bytes) binary += String.fromCharCode(byte);
    return btoa(binary);
}

function base64ToBuffer(value) {
    const binary = atob(value);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return bytes.buffer;
}

function recursiveBase64ToArrayBuffer(obj) {
    if (!obj || typeof obj !== 'object') return;

    for (const key of Object.keys(obj)) {
        const value = obj[key];

        if (typeof value === 'string') {
            // lbuchs WebAuthn serializes binary values as base64 strings.
            // Only convert values that are clearly binary WebAuthn fields.
            if (['challenge', 'id', 'userHandle'].includes(key)) {
                try { obj[key] = base64ToBuffer(value); } catch (_) {}
            }
        } else if (value && typeof value === 'object') {
            recursiveBase64ToArrayBuffer(value);
        }
    }
}

function preparePublicKey(publicKey) {
    recursiveBase64ToArrayBuffer(publicKey);
    return publicKey;
}

async function postJson(url, data) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        credentials: 'same-origin',
        body: JSON.stringify(data)
    });

    const json = await response.json().catch(() => ({
        success: false,
        message: 'استجابة غير صالحة من الخادم.'
    }));

    if (!response.ok && json.success !== false) {
        throw new Error('حدث خطأ في الخادم.');
    }

    return json;
}

function friendlyError(error) {
    const msg = error?.message || String(error);

    if (!window.PublicKeyCredential || !navigator.credentials) {
        return 'هذا المتصفح لا يدعم البصمة عبر WebAuthn.';
    }

    if (msg.includes('NotAllowedError')) {
        return 'تم إلغاء البصمة أو لم تكتمل عملية التحقق.';
    }

    if (msg.includes('SecurityError')) {
        return 'الموقع يحتاج HTTPS صحيح، وتأكد أن الدومين مضبوط في إعدادات المشروع.';
    }

    return msg;
}
