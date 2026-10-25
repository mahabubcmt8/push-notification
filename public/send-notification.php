<?php
// Windows fix: if PHP's OpenSSL can't find its config file, key creation fails
// ("Private key length must be at least 384 bits, configured to 0").
if (PHP_OS_FAMILY === 'Windows' && !getenv('OPENSSL_CONF')) {
    foreach ([
        'C:\\xampp\\apache\\conf\\openssl.cnf',
        'C:\\xampp\\apache\\bin\\openssl.cnf',
        'C:\\xampp\\php\\extras\\ssl\\openssl.cnf',
        'C:\\php\\extras\\ssl\\openssl.cnf',
    ] as $cnf) {
        if (is_file($cnf)) {
            putenv("OPENSSL_CONF=$cnf");
            break;
        }
    }
}

header('Content-Type: application/json');

// This file exists both at the project root and inside public/ (which mirrors
// the root but does NOT have its own vendor/db.php/config.php — those live
// one level up, outside the web root, and public/ endpoints reach them via "..").
$root = is_dir(__DIR__ . '/vendor') ? __DIR__ : dirname(__DIR__);

require $root . '/vendor/autoload.php';
require $root . '/db.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$title = trim($input['title'] ?? '') ?: 'Hello';
$body  = trim($input['body'] ?? '') ?: 'This is a push notification from the button';
$url   = trim($input['url'] ?? '') ?: '/';

$payload = json_encode([
    'title' => $title,
    'body'  => $body,
    'url'   => $url,
]);

$config = require $root . '/config.php';
$webPush = new WebPush(['VAPID' => $config['vapid']]);

$rows = db()->query('SELECT * FROM push_subscriptions')->fetchAll();
if (!$rows) {
    echo json_encode(['sent' => 0, 'failed' => 0, 'removed' => 0, 'message' => 'No subscriptions yet.']);
    exit;
}

foreach ($rows as $row) {
    $webPush->queueNotification(
        Subscription::create([
            'endpoint'        => $row['endpoint'],
            'publicKey'       => $row['p256dh'],
            'authToken'       => $row['auth'],
            'contentEncoding' => $row['content_encoding'],
        ]),
        $payload
    );
}

$delete = db()->prepare('DELETE FROM push_subscriptions WHERE endpoint = ?');
$sent = $failed = $removed = 0;
$errors = [];

foreach ($webPush->flush() as $report) {
    if ($report->isSuccess()) {
        $sent++;
        continue;
    }

    $failed++;
    $errors[] = substr($report->getEndpoint(), 0, 60) . '... ' . $report->getReason();

    // 404/410 means the subscription is gone for good: clean it up
    if ($report->isSubscriptionExpired()) {
        $delete->execute([$report->getEndpoint()]);
        $removed++;
    }
}

echo json_encode([
    'sent'    => $sent,
    'failed'  => $failed,
    'removed' => $removed,
    'errors'  => $errors,
]);