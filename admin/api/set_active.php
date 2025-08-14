<?php
require __DIR__.'/../../app/db.php';
require __DIR__.'/../../app/auth.php';
require_login();
header('Content-Type: application/json; charset=utf-8');

$pdo = db();
$id = (int)($_POST['id'] ?? 0);
$room_id = (int)($_POST['room_id'] ?? 0);
if(!$id || !$room_id){ echo json_encode(['ok'=>false,'message'=>'ข้อมูลไม่ครบ']); exit; }

$pdo->beginTransaction();
try{
  // ปิด current เดิมของห้อง
  $stmt = $pdo->prepare("UPDATE room_sessions SET is_current=0 WHERE room_id=?");
  $stmt->execute([$room_id]);
  // ตั้งตัวที่เลือกเป็น current + สถานะเป็น live
  $stmt = $pdo->prepare("UPDATE room_sessions SET is_current=1, status='live' WHERE id=?");
  $stmt->execute([$id]);

  $pdo->commit();
  echo json_encode(['ok'=>true,'message'=>'ตั้งเป็นกำลังบรรยายแล้ว']);
}catch(Throwable $e){
  $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['ok'=>false,'message'=>'ผิดพลาด']);
}
