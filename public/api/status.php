<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../../app/db.php';
$pdo = db();
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

/*
 สถานะ (ใช้ร่วมกันทุกหน้า):
 - live     : is_current = 1 หรือ NOW() อยู่ในช่วง start_time..end_time
 - upcoming : start_time > NOW()
 - done     : end_time < NOW()
*/
$sql = <<<SQL
SELECT
  r.id            AS room_id,
  r.name          AS room_name,
  r.location      AS room_location,
  r.display_order AS display_order,

  /* LIVE */
  (
    SELECT JSON_OBJECT(
      'id',         rs1.id,
      'topic',      rs1.topic,
      'speaker',    rs1.speaker,
      'start_time', DATE_FORMAT(rs1.start_time, '%Y-%m-%d %H:%i:%s'),
      'end_time',   DATE_FORMAT(rs1.end_time,   '%Y-%m-%d %H:%i:%s'),
      'status',     'live'
    )
    FROM room_sessions rs1
    WHERE rs1.room_id = r.id
      AND (rs1.is_current = 1 OR (NOW() BETWEEN rs1.start_time AND rs1.end_time))
    ORDER BY rs1.is_current DESC, rs1.start_time ASC
    LIMIT 1
  ) AS current_json,

  /* NEXT */
  (
    SELECT JSON_OBJECT(
      'id',         rs2.id,
      'topic',      rs2.topic,
      'speaker',    rs2.speaker,
      'start_time', DATE_FORMAT(rs2.start_time, '%Y-%m-%d %H:%i:%s'),
      'end_time',   DATE_FORMAT(rs2.end_time,   '%Y-%m-%d %H:%i:%s'),
      'status',     'upcoming'
    )
    FROM room_sessions rs2
    WHERE rs2.room_id = r.id
      AND rs2.start_time > NOW()
    ORDER BY rs2.start_time ASC
    LIMIT 1
  ) AS next_json

FROM rooms r
ORDER BY r.display_order ASC, r.id ASC;
SQL;

$rows = $pdo->query($sql)->fetchAll();

$rooms = array_map(static function($r){
  return [
    'room_id'        => (int)$r['room_id'],
    'room_name'      => $r['room_name'],
    'room_location'  => $r['room_location'],
    'current'        => $r['current_json'] ? json_decode($r['current_json'], true) : null,
    'next'           => $r['next_json']    ? json_decode($r['next_json'], true)    : null,
  ];
}, $rows);

echo json_encode([
  'ok'    => true,
  'now'   => date('c'),
  'rooms' => $rooms,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
