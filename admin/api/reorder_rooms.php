<?php
require __DIR__.'/../../app/db.php';
require __DIR__.'/../../app/auth.php';
require_login();
header('Content-Type: application/json; charset=utf-8');

$ids = trim($_POST['ids'] ?? '');
if ($ids === '') { echo json_encode(['ok'=>false,'message'=>'ไม่มีรายการ']); exit; }

$pdo = db();
$pdo->beginTransaction();
try{
  $arr = array_filter(array_map('intval', explode(',', $ids)));
  $order = 1;
  $stmt = $pdo->prepare("UPDATE rooms SET display_order=? WHERE id=?");
  foreach($arr as $id){
    $stmt->execute([$order++, $id]);
  }
  $pdo->commit();
  echo json_encode(['ok'=>true,'message'=>'บันทึกการจัดลำดับแล้ว']);
}catch(Throwable $e){
  $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['ok'=>false,'message'=>'เกิดข้อผิดพลาด']);
}
