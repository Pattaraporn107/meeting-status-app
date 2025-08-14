<?php
require __DIR__.'/../../app/db.php';
require __DIR__.'/../../app/auth.php';
require_login();
header('Content-Type: application/json; charset=utf-8');

$pdo = db();
$id = (int)($_POST['id'] ?? 0);
if(!$id){ echo json_encode(['ok'=>false,'message'=>'ไม่มี id']); exit; }

try{
  $stmt = $pdo->prepare("DELETE FROM room_sessions WHERE id=?");
  $stmt->execute([$id]);
  echo json_encode(['ok'=>true,'message'=>'ลบสำเร็จ']);
}catch(Throwable $e){
  http_response_code(500);
  echo json_encode(['ok'=>false,'message'=>'ผิดพลาด']);
}
