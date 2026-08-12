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

// fetch the most recent previous point for this tracker to calculate speed
$stmt = $pdo->prepare('SELECT lat, lon, time_recorded FROM positions WHERE tracker_id = ? ORDER BY time_recorded DESC LIMIT 1');
$stmt->execute([$tracker['id']]);
$previous = $stmt->fetch();

$speedCalculated = null;

if ($previous) {
    $timeDelta = $data['time_recorded'] - $previous['time_recorded'];

    if ($timeDelta > 0) {
        $earthRadius = 6371000; // metres
        $dLat = deg2rad($data['lat'] - $previous['lat']);
        $dLon = deg2rad($data['lon'] - $previous['lon']);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($previous['lat'])) * cos(deg2rad($data['lat'])) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distanceMetres = $earthRadius * $c;

        $speedCalculated = ($distanceMetres / $timeDelta) * 3.6; // km/h
    }
}

$stmt = $pdo->prepare('INSERT INTO positions (tracker_id, lat, lon, time_recorded, time_received, speed_calculated) VALUES (?, ?, ?, ?, ?, ?) RETURNING id, time_recorded, time_received, speed_calculated');
$stmt->execute([$tracker['id'], $data['lat'], $data['lon'], $data['time_recorded'], $timeReceived, $speedCalculated]);
$position = $stmt->fetch();

$payload = json_encode([
    'tracker_id' => $tracker['id'],
    'lat' => $data['lat'],
    'lon' => $data['lon'],
    'time_recorded' => (int)$position['time_recorded'],
    'time_received' => (int)$position['time_received'],
    'speed_calculated' => $position['speed_calculated'] !== null ? (float)$position['speed_calculated'] : null,

]);

$sock = @fsockopen('ws', 8082, $errno, $errstr, 1);
if ($sock) {
    fwrite($sock, $payload . "\n");
    fclose($sock);
}

echo json_encode(['status' => 'ok']);