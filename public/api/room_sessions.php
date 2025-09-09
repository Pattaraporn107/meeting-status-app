<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../../app/db.php';
$pdo = db();
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$roomId = (int)($_GET['room_id'] ?? 0);
if ($roomId <= 0) {
  echo json_encode(['ok'=>false,'error'=>'room_id is required']); exit;
}

/* ส่งรายการของห้องนั้น ๆ พร้อมฟิลด์ที่จำเป็นต่อหน้า room/admin */
$sql = "SELECT id, room_id, topic, speaker, start_time, end_time, status, is_current, notes
        FROM room_sessions
        WHERE room_id = :rid
        ORDER BY start_time ASC, id ASC";
$st = $pdo->prepare($sql);
$st->execute(['rid'=>$roomId]);

$items = array_map(static function($r){
  return [
    'id'          => (int)$r['id'],
    'room_id'     => (int)$r['room_id'],
    'topic'       => $r['topic'],
    'speaker'     => $r['speaker'],
    'start_time'  => str_replace(' ', 'T', (string)$r['start_time']),
    'end_time'    => str_replace(' ', 'T', (string)$r['end_time']),
    'status'      => $r['status'],
    'is_current'  => (int)$r['is_current'],
    'notes'       => $r['notes'],
  ];
}, $st->fetchAll());

echo json_encode(['ok'=>true,'sessions'=>$items], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
