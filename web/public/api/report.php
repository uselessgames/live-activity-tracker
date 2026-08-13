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

// speed is derived from stored rows via a window function, not calculated during the write
$stmt = $pdo->prepare('
    SELECT speed_calculated FROM (
        SELECT time_recorded,
            CASE
                WHEN prev_time IS NOT NULL AND (time_recorded - prev_time) > 0 THEN
                    (6371000 * 2 * asin(sqrt(
                        power(sin(radians(lat - prev_lat) / 2), 2) +
                        cos(radians(prev_lat)) * cos(radians(lat)) *
                        power(sin(radians(lon - prev_lon) / 2), 2)
                    ))) / (time_recorded - prev_time) * 3.6
                ELSE NULL
            END AS speed_calculated
        FROM (
            SELECT lat, lon, time_recorded,
                LAG(lat) OVER w AS prev_lat,
                LAG(lon) OVER w AS prev_lon,
                LAG(time_recorded) OVER w AS prev_time
            FROM positions
            WHERE tracker_id = ?
            WINDOW w AS (ORDER BY time_recorded)
        ) sub
    ) speeds
    ORDER BY time_recorded DESC
    LIMIT 1
');
$stmt->execute([$tracker['id']]);
$speedRow = $stmt->fetch();

$payload = json_encode([
    'tracker_id' => $tracker['id'],
    'lat' => $data['lat'],
    'lon' => $data['lon'],
    'time_recorded' => (int)$position['time_recorded'],
    'time_received' => (int)$position['time_received'],
    'speed_calculated' => $speedRow && $speedRow['speed_calculated'] !== null ? (float)$speedRow['speed_calculated'] : null,
]);

$sock = @fsockopen('ws', 8082, $errno, $errstr, 1);
if ($sock) {
    fwrite($sock, $payload . "\n");
    fclose($sock);
}

echo json_encode(['status' => 'ok']);
