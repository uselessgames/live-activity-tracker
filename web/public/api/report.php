<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['api_key'], $data['lat'], $data['lon'])) {
    http_response_code(400);
    echo json_encode(['error' => 'missing fields']);
    exit;
}

$pdo = new PDO('pgsql:host=db;dbname=tracking', 'tracking', 'tracking');

$stmt = $pdo->prepare('SELECT id FROM trackers WHERE api_key = ?');
$stmt->execute([$data['api_key']]);
$tracker = $stmt->fetch();

if (!$tracker) {
    http_response_code(401);
    echo json_encode(['error' => 'invalid api_key']);
    exit;
}

$stmt = $pdo->prepare('INSERT INTO positions (tracker_id, lat, lon) VALUES (?, ?, ?) RETURNING id, reported_at');
$stmt->execute([$tracker['id'], $data['lat'], $data['lon']]);
$position = $stmt->fetch();

$payload = json_encode([
    'tracker_id' => $tracker['id'],
    'lat' => $data['lat'],
    'lon' => $data['lon'],
    'reported_at' => $position['reported_at'],
]);

$sock = @fsockopen('ws', 8082, $errno, $errstr, 1);
if ($sock) {
    fwrite($sock, $payload . "\n");
    fclose($sock);
}

echo json_encode(['status' => 'ok']);
