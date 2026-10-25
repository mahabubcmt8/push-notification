<?php
// Windows fix: if PHP's OpenSSL can't find its config file, key creation fails
// ("Private key length must be at least 384 bits, configured to 0").
if (PHP_OS_FAMILY === 'Windows' && !getenv('OPENSSL_CONF')) {
    foreach ([
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

// Usage: php send.php "Title" "Body text" "/optional/url"
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/db.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

$config = require __DIR__ . '/config.php';

$title = $argv[1] ?? 'Hello';
$body  = $argv[2] ?? 'This is a push notification from raw PHP';
$url   = $argv[3] ?? '/';

$payload = json_encode([
    'title' => $title,
    'body'  => $body,
    'url'   => $url,
]);

$webPush = new WebPush(['VAPID' => $config['vapid']]);

$rows = db()->query('SELECT * FROM push_subscriptions')->fetchAll();
if (!$rows) {
    echo "No subscriptions yet.\n";
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

foreach ($webPush->flush() as $report) {
    if ($report->isSuccess()) {
        $sent++;
        continue;
    }

    $failed++;
    echo "Failed: " . substr($report->getEndpoint(), 0, 60) . "... {$report->getReason()}\n";

    // 404/410 means the subscription is gone for good: clean it up
    if ($report->isSubscriptionExpired()) {
        $delete->execute([$report->getEndpoint()]);
        $removed++;
    }
}

echo "Sent: $sent, failed: $failed, removed expired: $removed\n";