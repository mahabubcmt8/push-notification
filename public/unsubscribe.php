<?php
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$endpoint = $data['endpoint'] ?? '';

if ($endpoint === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Missing endpoint']);
    exit;
}

db()->prepare('DELETE FROM push_subscriptions WHERE endpoint = ?')->execute([$endpoint]);
echo json_encode(['ok' => true]);