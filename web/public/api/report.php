<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['api_key'], $data['lat'], $data['lon'], $data['time_recorded'])) {
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

$timeReceived = time();

$stmt = $pdo->prepare('INSERT INTO positions (tracker_id, lat, lon, time_recorded, time_received) VALUES (?, ?, ?, ?, ?) RETURNING id, time_recorded, time_received');
$stmt->execute([$tracker['id'], $data['lat'], $data['lon'], $data['time_recorded'], $timeReceived]);
$position = $stmt->fetch();

$payload = json_encode([
    'tracker_id' => $tracker['id'],
    'lat' => $data['lat'],
    'lon' => $data['lon'],
    'time_recorded' => (int)$position['time_recorded'],
    'time_received' => (int)$position['time_received'],
]);

$sock = @fsockopen('ws', 8082, $errno, $errstr, 1);
if ($sock) {
    fwrite($sock, $payload . "\n");
    fclose($sock);
}

echo json_encode(['status' => 'ok']);