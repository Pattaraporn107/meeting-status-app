<?php
require __DIR__.'/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db();
$sql = "
  SELECT r.id as room_id, r.name as room_name, r.location,
         s.id as session_id, s.topic, s.speaker, s.start_time, s.end_time, s.status, s.is_current
  FROM rooms r
  LEFT JOIN room_sessions s
    ON s.room_id = r.id AND s.is_current = 1
  ORDER BY r.display_order ASC, r.id ASC
";
$data = $pdo->query($sql)->fetchAll();
echo json_encode(['rooms' => $data]);
