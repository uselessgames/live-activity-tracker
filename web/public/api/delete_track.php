<?php
header('Content-Type: application/json');

$pdo = new PDO('pgsql:host=db;dbname=tracking', 'tracking', 'tracking');

$trackerId = $_GET['id'] ?? null;

if ($trackerId === null) {
    echo json_encode(['success' => false, 'error' => 'Missing tracker_id']);
    exit;
}

$stmt = $pdo->prepare('DELETE FROM tracker_positions WHERE tracker_id = :tracker_id');
$stmt->execute(['tracker_id' => $trackerId]);

echo json_encode(['success' => true]);
