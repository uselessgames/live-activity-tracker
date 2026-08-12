<?php
header('Content-Type: application/json');

$pdo = new PDO('pgsql:host=db;dbname=tracking', 'tracking', 'tracking');

$stmt = $pdo->query('
    SELECT tracker_id, lat, lon, time_recorded, time_received, speed_calculated
    FROM positions
    ORDER BY tracker_id, time_recorded ASC
');

$result = [];
while ($row = $stmt->fetch()) {
    $tid = $row['tracker_id'];
    if (!isset($result[$tid])) {
        $result[$tid] = [];
    }
    $result[$tid][] = [
        'lat' => (float)$row['lat'],
        'lon' => (float)$row['lon'],
        'time_recorded' => (int)$row['time_recorded'],
        'time_received' => (int)$row['time_received'],
        'speed_calculated' => $row['speed_calculated'] !== null ? (float)$row['speed_calculated'] : null
    ];
}

echo json_encode($result);