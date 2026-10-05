<?php
declare(strict_types=1);

/*
 * عدّل بيانات قاعدة البيانات فقط.
 * RP_ID يفضل تركه فارغًا ليتم أخذه تلقائيًا من الدومين.
 * مثال: example.com
 */

const DB_HOST = 'localhost';
const DB_NAME = 'u552468652_fingerprint12';
const DB_USER = 'u552468652_fingerprint12';
const DB_PASS = 'Fingerprinttest123';

const APP_NAME = 'نظام الدخول بالبصمة';

/*
 * اتركه فارغًا = يتم اكتشاف الدومين تلقائيًا.
 * إذا كان موقعك https://example.com اكتب: example.com
 */
const RP_ID = '';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    return $pdo;
}

function rpId(): string
{
    if (RP_ID !== '') {
        return strtolower(trim(RP_ID));
    }

    $host = $_SERVER['HTTP_HOST'] ?? '';
    $host = preg_replace('/:\d+$/', '', $host);
    return strtolower($host ?: 'localhost');
}

function jsonResponse(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function requirePost(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'طريقة الطلب غير صحيحة.'], 405);
    }
}

function requireHttps(): void
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    if (!$https && rpId() !== 'localhost') {
        jsonResponse([
            'success' => false,
            'message' => 'يجب تشغيل الموقع عبر HTTPS حتى تعمل البصمة.'
        ], 400);
    }
}
