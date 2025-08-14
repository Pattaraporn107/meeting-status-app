<?php
require __DIR__ . '/../app/db.php';
$pdo = db();
$rooms = $pdo->query("SELECT id, name, location FROM rooms ORDER BY display_order ASC, id ASC")->fetchAll();
function h($s)
{
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="th">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>สถานะห้องประชุม</title>
  <link rel="stylesheet" href="/public/assets/app.css">
</head>

<body class="wb">

  <!-- Header -->
  <header class="wb-header">
    <!-- โลโก้ -->
    <div class="wb-logos flex items-center gap-4 mb-4">
      <img src="/public/assets/picture/A.png" alt="โลโก้ 1" class="wb-logo h-20 w-auto" onerror="this.style.display='none'">
      <img src="/public/assets/picture/MOPH202503.png" alt="โลโก้ 2" class="wb-logo h-20 w-auto" onerror="this.style.display='none'">
    </div>

    <!-- แบนเนอร์
  <img src="/public/assets/picture/B.png" 
     alt="แบนเนอร์ประชุม" 
     class="w-full max-h-[400px] object-cover rounded-lg shadow-md"> -->

    <!-- ชื่อหัวข้อ -->
    <div class="wb-title text-center">
      <h1 class="text-3xl font-bold">งานประชุมวิชาการกระทรวงสาธารณสุข ประจำปี 2568</h1>
      <p class="wb-sub">ยกระดับการสาธารณสุขไทย
        สุขภาพแข็งแรงทุกวัย เศรษฐกิจสุขภาพไทยมั่นคง </p>
    </div>
  </header>

  <!-- Content -->
  <main class="container">
    <div id="rooms" class="grid">
      <?php foreach ($rooms as $r): ?>
        <section class="card" data-room-id="<?= h($r['id']) ?>">
          <div class="card-head">
            <i class="pill" aria-hidden="true"></i>
            <h2 class="room-name">
              <?= h($r['name']) ?>
              <?php if (!empty($r['location'])): ?>
                <span class="room-loc">(<?= h($r['location']) ?>)</span>
              <?php endif; ?>
            </h2>
          </div>

          <div class="card-body">
            <!-- JS เดิมจะเติมสถานะลงตรงนี้ -->
            <div class="current skeleton">กำลังโหลด…</div>
          </div>

          <!-- <footer class="card-foot">
            <a class="link" href="/public/room.php?id=<?= h($r['id']) ?>">ดูตารางกำหนดการ</a>
          </footer> -->

          <!-- ส่วนปุ่มดูกำหนดการ -->
          <footer class="card-foot mt-3">
            <a
              href="/public/room.php?id=<?= h($r['id']) ?>"
              class="inline-flex items-center justify-center gap-2 px-5 py-2.5
           rounded-xl bg-[#275937] text-white font-semibold shadow
           hover:bg-white hover:text-[#275937] border border-[#275937]
           transition duration-200 w-full sm:w-auto text-center">

              <!-- ไอคอนปฏิทินเล็ก ๆ -->
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z" />
              </svg>
              ดูตารางกำหนดการ
            </a>
          </footer>


        </section>
      <?php endforeach; ?>
    </div>
  </main>

  <script src="/public/assets/app.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    window.MeetingApp.initPublic();
  </script>
</body>

</html>