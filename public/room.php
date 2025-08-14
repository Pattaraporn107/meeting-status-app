<?php
require __DIR__.'/../app/db.php';
$pdo = db();
$room_id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id=?");
$stmt->execute([$room_id]);
$room = $stmt->fetch();
if (!$room) { http_response_code(404); echo "ไม่พบห้อง"; exit; }
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($room['name']) ?> - ตารางกำหนดการ</title>
<link rel="stylesheet" href="/public/assets/app.css">
</head>
<body>
  <div class="container">
    <h1><?= htmlspecialchars($room['name']) ?> <small>(<?= htmlspecialchars($room['location'] ?? '-') ?>)</small></h1>
    <p><a href="/public/index.php">← กลับหน้ารวม</a></p>

    <h2>ตารางกำหนดการวันนี้</h2>
    <table id="session-table" class="table">
      <thead>
        <tr><th>เวลา</th><th>หัวข้อ</th><th>วิทยากร</th><th>สถานะ</th></tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
<script src="/public/assets/app.js"></script>
<script>
  window.MeetingApp.loadRoomSessions(<?= (int)$room['id'] ?>);
  setInterval(() => window.MeetingApp.loadRoomSessions(<?= (int)$room['id'] ?>), 10000);
</script>
</body>
</html>
