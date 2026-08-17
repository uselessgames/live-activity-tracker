<?php
header('Content-Type: application/json');

$pdo = new PDO('pgsql:host=db;dbname=tracking', 'tracking', 'tracking');

$input = json_decode(file_get_contents('php://input'), true);

$trackerId = $input['tracker_id'] ?? null;
$name = $input['name'] ?? null;
$startTime = $input['start_time'] ?? null;
$endTime = $input['end_time'] ?? null;
$waypoints = $input['waypoints'] ?? null;
$duration = $input['duration'] ?? null;

if ($trackerId === null || $name === null || $startTime === null || $endTime === null || $waypoints === null || $duration === null) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

$sanitized_name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

$distance = 0;
$speeds = [];

for ($i = 1; $i < count($waypoints); $i++) {
    $prev = $waypoints[$i - 1];
    $curr = $waypoints[$i];
    
    $lat1 = $prev['lat'];
    $lon1 = $prev['lon'];
    $lat2 = $curr['lat'];
    $lon2 = $curr['lon'];
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $segmentDistance = 6371 * $c;
    
    $segmentTime = $curr['time_recorded'] - $prev['time_recorded'];
    
    if ($segmentTime > 0) {
        $segmentSpeed = $segmentDistance / $segmentTime * 3.6;
        $speeds[] = $segmentSpeed;
    }
    
    $distance += $segmentDistance;
}

$speedAvg = count($speeds) > 0 ? array_sum($speeds) / count($speeds) : 0;
$speedMax = count($speeds) > 0 ? max($speeds) : 0;

$stmt = $pdo->prepare('
    INSERT INTO activities (tracker_id, name, start_time, end_time, duration, waypoints, distance, speed_avg, speed_max)
    VALUES (:tracker_id, :name, :start_time, :end_time, :duration, :waypoints, :distance, :speed_avg, :speed_max)
    RETURNING id
');

$stmt->execute([
    'tracker_id' => $trackerId,
    'name' => $sanitized_name,
    'start_time' => $startTime,
    'end_time' => $endTime,
    'duration' => $duration,
    'waypoints' => json_encode($waypoints),
    'distance' => $distance,
    'speed_avg' => $speedAvg,
    'speed_max' => $speedMax
]);

echo json_encode(['success' => true]);
