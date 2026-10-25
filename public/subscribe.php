<?php
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST only']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$endpoint = $data['endpoint'] ?? '';
$p256dh   = $data['keys']['p256dh'] ?? '';
$auth     = $data['keys']['auth'] ?? '';
$encoding = $data['contentEncoding'] ?? 'aesgcm';

if (!filter_var($endpoint, FILTER_VALIDATE_URL)
    || !str_starts_with($endpoint, 'https://')
    || $p256dh === '' || $auth === ''
    || !in_array($encoding, ['aesgcm', 'aes128gcm'], true)) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid subscription']);
    exit;
}

// If you have logins, store the current user's id here instead of null
$userId = null;

$stmt = db()->prepare("
    INSERT INTO push_subscriptions (endpoint, p256dh, auth, content_encoding, user_id)
    VALUES (:endpoint, :p256dh, :auth, :enc, :uid)
    ON CONFLICT(endpoint) DO UPDATE SET
        p256dh = excluded.p256dh,
        auth = excluded.auth,
        content_encoding = excluded.content_encoding
");
$stmt->execute([
    ':endpoint' => $endpoint,
    ':p256dh'   => $p256dh,
    ':auth'     => $auth,
    ':enc'      => $encoding,
    ':uid'      => $userId,
]);

echo json_encode(['ok' => true]);