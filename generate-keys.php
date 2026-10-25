<?php
// Run once: php generate-keys.php
require __DIR__ . '/vendor/autoload.php';

use Minishlink\WebPush\VAPID;

$keys = VAPID::createVapidKeys();

echo "Public key:  {$keys['publicKey']}\n";
echo "Private key: {$keys['privateKey']}\n\n";
echo "Paste these into config.php (or set VAPID_PUBLIC_KEY / VAPID_PRIVATE_KEY env vars).\n";
echo "Keep the private key secret. Do not regenerate later, or existing subscriptions will break.\n";
