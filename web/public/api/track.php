<?php
header('Content-Type: application/json');

$pdo = new PDO('pgsql:host=db;dbname=tracking', 'tracking', 'tracking');

$stmt = $pdo->query('
    SELECT tracker_id, lat, lon, time_recorded, time_received
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
        'time_received' => (int)$row['time_received']
    ];
}

echo json_encode($result);