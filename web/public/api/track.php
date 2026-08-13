<?php
header('Content-Type: application/json');

$pdo = new PDO('pgsql:host=db;dbname=tracking', 'tracking', 'tracking');

$stmt = $pdo->query('
   SELECT tracker_id, lat, lon, time_recorded, time_received,
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
       SELECT tracker_id, lat, lon, time_recorded, time_received,
           LAG(lat) OVER w AS prev_lat,
           LAG(lon) OVER w AS prev_lon,
           LAG(time_recorded) OVER w AS prev_time
       FROM positions
       WINDOW w AS (PARTITION BY tracker_id ORDER BY time_recorded)
   ) sub
   ORDER BY tracker_id, time_recorded
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
