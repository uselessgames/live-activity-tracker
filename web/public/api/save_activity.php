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

$stmt = $pdo->prepare('
    INSERT INTO activities (tracker_id, name, start_time, end_time, duration, waypoints)
    VALUES (:tracker_id, :name, :start_time, :end_time, :duration, :waypoints)
    RETURNING id
');

$stmt->execute([
    'tracker_id' => $trackerId,
    'name' => $sanitized_name,
    'start_time' => $startTime,
    'end_time' => $endTime,
    'duration' => $duration,
    'waypoints' => json_encode($waypoints)
]);

echo json_encode(['success' => true]);
