<?php
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $pdo = new PDO('pgsql:host=db;dbname=tracking', 'tracking', 'tracking');
    
    $stmt = $pdo->query('SELECT id, tracker_id, name, start_time, end_time, waypoints FROM activities ORDER BY start_time DESC');
    
    $results = [];
    while ($row = $stmt->fetch()) {
        $results[] = [
            'id' => (int)$row['id'],
            'tracker_id' => (int)$row['tracker_id'],
            'name' => $row['name'],
            'start_time' => (int)$row['start_time'],
            'end_time' => (int)$row['end_time'],
            'waypoints' => json_decode($row['waypoints'], true)
        ];
    }
    
    echo json_encode($results);
} elseif ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Missing activity ID']);
        exit;
    }
    
    $pdo = new PDO('pgsql:host=db;dbname=tracking', 'tracking', 'tracking');
    
    $stmt = $pdo->prepare('DELETE FROM activities WHERE id = ? RETURNING id');
    $stmt->execute([$id]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Activity not found']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
