<?php
require __DIR__ . '/bootstrap.php';
$config = require __DIR__ . '/../config.php';
header('Content-Type: application/json');
echo json_encode(['publicKey' => $config['vapid']['publicKey']]);