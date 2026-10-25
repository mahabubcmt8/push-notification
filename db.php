<?php
function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';
    $dir = dirname($config['db_path']);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $pdo = new PDO('sqlite:' . $config['db_path']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS push_subscriptions (
            id               INTEGER PRIMARY KEY AUTOINCREMENT,
            endpoint         TEXT NOT NULL UNIQUE,
            p256dh           TEXT NOT NULL,
            auth             TEXT NOT NULL,
            content_encoding TEXT NOT NULL DEFAULT 'aesgcm',
            user_id          INTEGER NULL,
            created_at       TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    return $pdo;
}
