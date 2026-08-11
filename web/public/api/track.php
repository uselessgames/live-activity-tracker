<?php
header('Content-Type: application/json');

$pdo = new PDO('pgsql:host=db;dbname=tracking', 'tracking', 'tracking');

$stmt = $pdo->query('
    SELECT tracker_id, lat, lon, reported_at
    FROM positions
    ORDER BY tracker_id, reported_at ASC
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
        'reported_at' => $row['reported_at']
    ];
}

echo json_encode($result);
