<?php
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $pdo = new PDO('pgsql:host=db;dbname=tracking', 'tracking', 'tracking');
    
    $stmt = $pdo->query('SELECT id, tracker_id, name, start_time, end_time, distance, speed_avg, speed_max FROM activities ORDER BY start_time DESC');
    
    $results = [];
    while ($row = $stmt->fetch()) {
        $results[] = [
            'id' => (int)$row['id'],
            'tracker_id' => (int)$row['tracker_id'],
            'name' => $row['name'],
            'start_time' => (int)$row['start_time'],
            'end_time' => (int)$row['end_time'],
            'distance' => (float)$row['distance'],
            'speed_avg' => (float)$row['speed_avg'],
            'speed_max' => (float)$row['speed_max']
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
} elseif ($method === 'PUT') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Missing activity ID']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $name = $input['name'] ?? null;
    
    if ($name === null) {
        echo json_encode(['success' => false, 'error' => 'Missing name']);
        exit;
    }
    
    $sanitized_name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    
    $pdo = new PDO('pgsql:host=db;dbname=tracking', 'tracking', 'tracking');
    
    $stmt = $pdo->prepare('UPDATE activities SET name = ? WHERE id = ?');
    $stmt->execute([$sanitized_name, $id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Activity not found']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
