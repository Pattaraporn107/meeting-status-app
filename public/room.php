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
<link rel="icon" type="image/png" href="/public/assets/picture/MOPH202503.png">
<link rel="stylesheet" href="/public/assets/app.css">
<style>
/* ====== แต่งหน้า room ให้ดูหรูขึ้น ====== */
.page-wrap{max-width:1100px;margin-inline:auto;padding:16px}
.breadcrumb{
  display:inline-flex;align-items:center;gap:8px;
  padding:8px 12px;border-radius:999px;border:1px solid #E6EBF0;
  background:#fff;text-decoration:none;color:#1C4724;font-weight:700
}
.room-title{display:flex;align-items:end;justify-content:space-between;gap:12px;margin:12px 0 14px}
.room-title h1{margin:0;font-size:26px;font-weight:800;color:#0f1f1a}
.room-title small{color:#64748b;font-weight:600}
.badge{display:inline-flex;align-items:center;border-radius:999px;padding:2px 10px;font-size:12px;font-weight:800}
.badge-live{background:#fee2e2;color:#b91c1c}
.badge-upcoming{background:#ecfeff;color:#0369a1}
.badge-done{background:#eef2ff;color:#3730a3}

.table-wrap{background:#fff;border:1px solid #E6EBF0;border-radius:14px;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,.06)}
.table{width:100%;border-collapse:separate;border-spacing:0}
.table thead th{
  background:#F8FAFC;color:#0f172a;text-align:left;padding:12px 14px;font-weight:800;position:sticky;top:0;z-index:1
}
.table tbody td{padding:12px 14px;border-top:1px solid #F1F5F9;vertical-align:top}
.table tbody tr:hover{background:#fafcff}

.btn-icon{
  appearance:none;border:1px solid #E6EBF0;background:#fff;border-radius:10px;
  width:36px;height:36px;display:grid;place-items:center;cursor:pointer;
  transition:transform .12s, background .12s
}
.btn-icon:hover{background:#f5f7fb}
.chev{transition:transform .15s}
tr.is-open .chev{transform:rotate(180deg)}
/* แถวรายละเอียดที่พับเก็บ */
.row-detail td{background:#FBFEFF;border-top:1px dashed #dbe7f3}
.detail-box{display:grid;gap:6px}
.detail-label{font-size:12px;color:#64748b}
.detail-note{white-space:pre-wrap}

/* การ์ดหัวเรื่อง */
.head-card{display:flex;gap:12px;align-items:center;margin:10px 0 16px}
.head-pill{width:10px;height:10px;border-radius:999px;background:#1C4724}
.head-meta{color:#64748b;font-weight:600}
</style>
</head>
<body>
  <div class="page-wrap">
    <a class="breadcrumb" href="/public/index.php" aria-label="กลับหน้ารวม">← กลับหน้ารวม</a>

    <div class="room-title">
      <h1>
        <?= htmlspecialchars($room['name']) ?>
        <small>(<?= htmlspecialchars($room['location'] ?? '-') ?>)</small>
      </h1>
      <span id="liveBadge" class="badge" style="display:none"></span>
    </div>

    <div class="head-card">
      <i class="head-pill" aria-hidden="true"></i>
      <div class="head-meta">ตารางกำหนดการวันนี้ · อัปเดตอัตโนมัติทุก 10 วินาที</div>
    </div>

    <div class="table-wrap">
      <table id="session-table" class="table">
        <thead>
          <tr>
            <th style="width:64px"></th>
            <th style="width:190px">เวลา</th>
            <th>หัวข้อ</th>
            <th style="width:240px">ผู้นำเสนอ</th>
            <th style="width:140px">สถานะ</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

<script src="/public/assets/app.js?v=<?= time() ?>"></script>
<script>
/**
 * เราจะ override renderer ฝั่งหน้ารายห้องให้มี "แถวพับ/กางรายละเอียด"
 * ถ้า MeetingApp มี loadRoomSessions อยู่แล้ว เราจับผลลัพธ์และ render ใหม่
 */
(function(){
  const tbody = document.querySelector('#session-table tbody');
  const liveBadge = document.getElementById('liveBadge');

  function fmtTimeRange(s){
    // s.start_time / s.end_time ควรเป็น 'YYYY-MM-DD HH:MM:SS'
    const st = new Date(s.start_time.replace(' ', 'T'));
    const en = new Date(s.end_time.replace(' ', 'T'));
    const f = n => String(n).padStart(2,'0');
    const tm = t => `${f(t.getHours())}:${f(t.getMinutes())}`;
    return `${tm(st)} - ${tm(en)}`;
  }
  function badge(status, is_current){
    if (String(is_current) === '1') return '<span class="badge badge-live">LIVE</span>';
    if (status === 'done') return '<span class="badge badge-done">Done</span>';
    return '<span class="badge badge-upcoming">Upcoming</span>';
  }
  function escapeHtml(str){
    return (str ?? '').toString()
      .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
  }

  // ตัว render หลัก: วาดแถว + แถวรายละเอียดต่อท้าย
  function renderRoomSessions(list){
    tbody.innerHTML = '';
    let hasLive = false;

    list.forEach(s => {
      const tr = document.createElement('tr');
      tr.className = 'row';
      tr.dataset.id = s.id;

      tr.innerHTML = `
        <td>
          <button class="btn-icon js-expand" aria-label="แสดงรายละเอียด" title="รายละเอียด">
            <svg class="chev" width="18" height="18" viewBox="0 0 24 24" fill="none">
              <path d="M6 9l6 6 6-6" stroke="#334155" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </td>
        <td><strong>${fmtTimeRange(s)}</strong></td>
        <td>${escapeHtml(s.topic)}</td>
        <td>${escapeHtml(s.speaker) || '-'}</td>
        <td>${badge(s.status, s.is_current)}</td>
      `;

      const trDetail = document.createElement('tr');
      trDetail.className = 'row-detail';
      trDetail.style.display = 'none';
      trDetail.innerHTML = `
        <td colspan="5">
          <div class="detail-box">
            <div><span class="detail-label">หัวข้อ</span><div><strong>${escapeHtml(s.topic)}</strong></div></div>
            <div><span class="detail-label">ผู้นำเสนอ</span><div>${escapeHtml(s.speaker) || '-'}</div></div>
            <div><span class="detail-label">เวลา</span><div>${fmtTimeRange(s)}</div></div>
            ${s.notes ? `<div><span class="detail-label">หมายเหตุ</span><div class="detail-note">${escapeHtml(s.notes)}</div></div>` : ``}
          </div>
        </td>
      `;

      tbody.appendChild(tr);
      tbody.appendChild(trDetail);

      if (String(s.is_current) === '1') hasLive = true;
    });

    if (hasLive){
      liveBadge.textContent = 'กำลังถ่ายทอดสด';
      liveBadge.className = 'badge badge-live';
      liveBadge.style.display = '';
    } else {
      liveBadge.textContent = 'ไม่มีไลฟ์ขณะนี้';
      liveBadge.className = 'badge badge-upcoming';
      liveBadge.style.display = '';
    }
  }
  // toggle รายละเอียด (event delegation)
  tbody.addEventListener('click', (e)=>{
    const btn = e.target.closest('.js-expand');
    if (!btn) return;
    const row = btn.closest('tr');
    const next = row.nextElementSibling;
    const open = next && next.classList.contains('row-detail') && next.style.display !== 'none';
    if (!next || !next.classList.contains('row-detail')) return;

    Array.from(tbody.querySelectorAll('.row-detail')).forEach(r=>{ r.style.display='none'; r.previousElementSibling?.classList.remove('is-open'); });
    if (!open){
      next.style.display = '';
      row.classList.add('is-open');
    }
  });

  // ---- hook MeetingApp.loadRoomSessions ให้ใช้ renderer นี้ ----
  const original = window.MeetingApp?.loadRoomSessions;
  window.MeetingApp = window.MeetingApp || {};

  window.MeetingApp.loadRoomSessions = async function(roomId){
    // ถ้ามีของเดิมให้เรียกใช้เพื่อดึงข้อมูล JSON (คาดว่า return เป็น array)
    try{
      if (typeof original === 'function'){
        const list = await original.call(window.MeetingApp, roomId, { returnOnly: true });
        if (Array.isArray(list)) {
          renderRoomSessions(list);
          return list;
        }
      }
      // ถ้า original ไม่รองรับ return ให้ fallback ไปเรียก API ตรง ๆ
      const res = await fetch(`/public/api/room_sessions.php?room_id=${roomId}`, {headers:{'X-Requested-With':'fetch'}});
      const data = await res.json();
      renderRoomSessions(data.sessions || data || []);
      return data.sessions || data || [];
    }catch(err){
      console.error(err);
    }
  };

  // เรียกครั้งแรก + ตั้ง interval
  const ROOM_ID = <?= (int)$room['id'] ?>;
  window.MeetingApp.loadRoomSessions(ROOM_ID);
  setInterval(()=>window.MeetingApp.loadRoomSessions(ROOM_ID), 10000);
})();
</script>
</body>
</html>
