<?php
require __DIR__.'/../../app/db.php';
require __DIR__.'/../../app/auth.php';
require_login();
header('Content-Type: application/json; charset=utf-8');

$pdo = db();
$id = (int)($_POST['id'] ?? 0);
$room_id = (int)($_POST['room_id'] ?? 0);
$topic = trim($_POST['topic'] ?? '');
$speaker = trim($_POST['speaker'] ?? '');
$start = $_POST['start_time'] ?? '';
$end = $_POST['end_time'] ?? '';
$status = $_POST['status'] ?? 'upcoming';

if(!$room_id || !$topic || !$start || !$end){
  echo json_encode(['ok'=>false,'message'=>'ข้อมูลไม่ครบ']); exit;
}

try{
  if($id){
    $stmt = $pdo->prepare("UPDATE room_sessions SET topic=?, speaker=?, start_time=?, end_time=?, status=? WHERE id=?");
    $stmt->execute([$topic, $speaker, $start, $end, $status, $id]);
  } else {
    $stmt = $pdo->prepare("INSERT INTO room_sessions (room_id, topic, speaker, start_time, end_time, status) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$room_id, $topic, $speaker, $start, $end, $status]);
  }
  echo json_encode(['ok'=>true,'message'=>'บันทึกสำเร็จ']);
}catch(Throwable $e){
  http_response_code(500);
  echo json_encode(['ok'=>false,'message'=>'ผิดพลาด']);
}
