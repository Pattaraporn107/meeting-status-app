<?php
require __DIR__.'/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$room_id = (int)($_GET['room_id'] ?? 0);
$pdo = db();
$stmt = $pdo->prepare("
  SELECT id, topic, speaker, start_time, end_time, status, is_current
  FROM room_sessions
  WHERE room_id = ?
  ORDER BY start_time ASC
");
$stmt->execute([$room_id]);
echo json_encode(['sessions' => $stmt->fetchAll()]);
