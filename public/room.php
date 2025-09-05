<?php
require __DIR__.'/../app/db.php';
$pdo = db();
$room_id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id=?");
$stmt->execute([$room_id]);
$room = $stmt->fetch();
if (!$room) { http_response_code(404); echo "ไม่พบห้อง"; exit; }

function h($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($room['name']) ?> - ตารางกำหนดการ</title>
<link rel="icon" type="image/png" href="/public/assets/picture/MOPH202503.png">
<link rel="stylesheet" href="/public/assets/app.css">

<style>
:root{
  --bg:#F6F8FB;
  --card:#fff;
  --ink:#0f1f1a;
  --ink-2:#334155;
  --muted:#64748b;
  --line:#E6EBF0;
  --line-2:#F1F5F9;
  --brand:#1C4724;
  --live-bg:#fee2e2; --live-ink:#b91c1c;
  --upc-bg:#ecfeff; --upc-ink:#0369a1;
  --done-bg:#eef2ff; --done-ink:#3730a3;
  --radius:14px; --radius-pill:999px;
  --pad:16px;
}
*{box-sizing:border-box}
html,body{height:100%}
body{margin:0;background:var(--bg);color:var(--ink);font-family:system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Thai", "Noto Sans", Arial, sans-serif;line-height:1.5}

.page-wrap{max-width:1100px;margin-inline:auto;padding:24px 14px}
.breadcrumb{
  display:inline-flex;align-items:center;gap:8px;
  padding:8px 12px;border-radius:var(--radius-pill);border:1px solid var(--line);
  background:var(--card);text-decoration:none;color:var(--brand);font-weight:700;
  transition:background .12s, transform .06s;
}
.breadcrumb:focus-visible{outline:3px solid #c7f0d2;outline-offset:2px}
.breadcrumb:hover{background:#f5f7fb}

.header{
  display:flex;align-items:flex-end;justify-content:space-between;gap:12px;margin:14px 0 16px
}
.header__title{margin:0;font-size:28px;font-weight:800;color:var(--ink)}
.header__sub{color:var(--muted);font-weight:600}
.header__badge{display:inline-flex;align-items:center;border-radius:var(--radius-pill);padding:4px 12px;font-size:12px;font-weight:800}

.head-card{display:flex;gap:12px;align-items:center;margin:8px 0 18px}
.head-pill{width:10px;height:10px;border-radius:var(--radius-pill);background:var(--brand)}
.head-meta{color:var(--muted);font-weight:600}

.card{
  background:var(--card);border:1px solid var(--line);border-radius:var(--radius);
  box-shadow:0 8px 24px rgba(0,0,0,.06);overflow:hidden
}

.table{width:100%;border-collapse:separate;border-spacing:0}
.table thead th{
  background:#F8FAFC;color:#0f172a;text-align:left;padding:12px 14px;font-weight:800;position:sticky;top:0;z-index:2
}
.table tbody td{padding:12px 14px;border-top:1px solid var(--line-2);vertical-align:top}
.table tbody tr:hover{background:#fafcff}

.btn-icon{
  appearance:none;border:1px solid var(--line);background:var(--card);border-radius:10px;
  width:38px;height:38px;display:grid;place-items:center;cursor:pointer;
  transition:transform .12s, background .12s;
}
.btn-icon:hover{background:#f5f7fb}
.btn-icon:focus-visible{outline:3px solid #c7f0d2;outline-offset:2px}
.chev{transition:transform .15s}
tr.is-open .chev{transform:rotate(180deg)}

.badge{display:inline-flex;align-items:center;border-radius:var(--radius-pill);padding:2px 10px;font-size:12px;font-weight:800}
.badge--live{background:var(--live-bg);color:var(--live-ink)}
.badge--upc{background:var(--upc-bg);color:var(--upc-ink)}
.badge--done{background:var(--done-bg);color:var(--done-ink)}

.row-detail td{background:#FBFEFF;border-top:1px dashed #dbe7f3}
.detail-box{display:grid;gap:8px}
.detail-label{font-size:12px;color:var(--muted)}
.detail-note{white-space:pre-wrap}

.topic{
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
  max-width:100%;
}

.empty{
  padding:28px; text-align:center; color:var(--muted)
}
.empty__title{font-weight:800;color:var(--ink);margin:0 0 6px}
.empty__hint{margin:0}

.skel-row td{padding:12px 14px;border-top:1px solid var(--line-2)}
.skel{display:block;height:14px;border-radius:8px;background:linear-gradient(90deg,#eef2f7, #f6f8fb 40%, #eef2f7);animation:sh 1.2s linear infinite;background-size:200% 100%}
@keyframes sh{0%{background-position:200% 0}100%{background-position:-200% 0}}

@media (max-width: 720px){
  .header{flex-direction:column;align-items:flex-start}
  .header__title{font-size:22px}
  .table thead th:nth-child(3){min-width:220px}
  td:nth-child(4), th:nth-child(4){display:none} /* ซ่อนคอลัมน์ผู้นำเสนอในจอเล็ก */
}

@media (prefers-reduced-motion: reduce){
  .chev, .btn-icon, .breadcrumb{transition:none}
}
</style>
</head>
<body>
  <div class="page-wrap">
    <a class="breadcrumb" href="/public/index.php" aria-label="กลับหน้ารวม">← กลับหน้ารวม</a>

    <header class="header" aria-live="polite">
      <h1 class="header__title">
        <?= h($room['name']) ?> <span class="header__sub">(<?= h($room['location'] ?? '-') ?>)</span>
      </h1>
      <span id="liveBadge" class="header__badge" style="display:none"></span>
    </header>

    <div class="head-card" role="note" aria-label="ตารางกำหนดการวันนี้">
      <i class="head-pill" aria-hidden="true"></i>
      <div class="head-meta">ตารางกำหนดการวันนี้ · อัปเดตอัตโนมัติทุก 10 วินาที</div>
    </div>

    <div class="card" role="region" aria-label="ตารางกำหนดการห้อง">
      <table id="session-table" class="table">
        <thead>
          <tr>
            <th scope="col" style="width:64px">รายละเอียด</th>
            <th scope="col" style="width:190px">เวลา</th>
            <th scope="col">หัวข้อ</th>
            <th scope="col" style="width:240px">ผู้นำเสนอ</th>
            <th scope="col" style="width:140px">สถานะ</th>
          </tr>
        </thead>
        <tbody>
          <!-- skeleton เริ่มต้น -->
          <?php for($i=0;$i<3;$i++): ?>
          <tr class="skel-row">
            <td><span class="skel" style="width:28px;height:28px"></span></td>
            <td><span class="skel" style="width:120px"></span></td>
            <td><span class="skel" style="width:80%"></span></td>
            <td><span class="skel" style="width:70%"></span></td>
            <td><span class="skel" style="width:84px"></span></td>
          </tr>
          <?php endfor; ?>
        </tbody>
      </table>
      <div id="emptyState" class="empty" style="display:none">
        <p class="empty__title">วันนี้ยังไม่มีรายการ</p>
        <p class="empty__hint">กำหนดการจะปรากฏที่นี่เมื่อมีการบันทึก</p>
      </div>
    </div>
  </div>

<script src="/public/assets/app.js?v=<?= time() ?>"></script>
<script>
(function(){
  const tbody = document.querySelector('#session-table tbody');
  const emptyState = document.getElementById('emptyState');
  const liveBadge = document.getElementById('liveBadge');

  const ROOM_ID = <?= (int)$room['id'] ?>;

  /** Utils **/
  const pad = n => String(n).padStart(2,'0');
  function fmtTimeRange(s){
    const st = new Date((s.start_time||'').replace(' ', 'T'));
    const en = new Date((s.end_time||'').replace(' ', 'T'));
    if (isNaN(st) || isNaN(en)) return '-';
    const tm = t => `${pad(t.getHours())}:${pad(t.getMinutes())}`;
    return `${tm(st)} - ${tm(en)}`;
  }
  function esc(str){
    return (str ?? '').toString()
      .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
  }
  function badge(status, is_current){
    if (String(is_current) === '1') return '<span class="badge badge--live">LIVE</span>';
    if (status === 'done') return '<span class="badge badge--done">Done</span>';
    return '<span class="badge badge--upc">Upcoming</span>';
  }

  /** Core render **/
  function render(list){
    tbody.innerHTML = '';
    let hasLive = false;
    let liveRowEl = null;

    if (!Array.isArray(list) || list.length === 0){
      emptyState.style.display = '';
      liveBadge.textContent = 'ไม่มีไลฟ์ขณะนี้';
      liveBadge.className = 'header__badge badge badge--upc';
      liveBadge.style.display = '';
      return;
    }
    emptyState.style.display = 'none';

    list.forEach((s, idx) => {
      const tr = document.createElement('tr');
      tr.className = 'row';
      tr.dataset.id = s.id;

      // ปุ่ม expand — รองรับคีย์บอร์ด & screen reader
      const btnHtml = `
        <button class="btn-icon js-expand"
                aria-label="แสดงรายละเอียดหัวข้อ"
                aria-expanded="false"
                aria-controls="detail-${s.id}">
          <svg class="chev" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M6 9l6 6 6-6" stroke="#334155" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>`;

      tr.innerHTML = `
        <td>${btnHtml}</td>
        <td><strong>${fmtTimeRange(s)}</strong></td>
        <td><div class="topic" title="${esc(s.topic)}">${esc(s.topic)}</div></td>
        <td>${esc(s.speaker) || '-'}</td>
        <td>${badge(s.status, s.is_current)}</td>
      `;

      const trDetail = document.createElement('tr');
      trDetail.className = 'row-detail';
      trDetail.id = `detail-${s.id}`;
      trDetail.style.display = 'none';
      trDetail.innerHTML = `
        <td colspan="5">
          <div class="detail-box">
            <div><span class="detail-label">หัวข้อ</span><div><strong>${esc(s.topic)}</strong></div></div>
            <div><span class="detail-label">ผู้นำเสนอ</span><div>${esc(s.speaker) || '-'}</div></div>
            <div><span class="detail-label">เวลา</span><div>${fmtTimeRange(s)}</div></div>
            ${s.notes ? `<div><span class="detail-label">หมายเหตุ</span><div class="detail-note">${esc(s.notes)}</div></div>` : ``}
          </div>
        </td>
      `;

      tbody.appendChild(tr);
      tbody.appendChild(trDetail);

      if (String(s.is_current) === '1'){
        hasLive = true;
        liveRowEl = tr;
      }
    });

    // อัปเดต badge มุมขวา
    if (hasLive){
      liveBadge.textContent = 'กำลังถ่ายทอดสด';
      liveBadge.className = 'header__badge badge badge--live';
      liveBadge.style.display = '';
      // auto-scroll ให้เห็นหัวข้อ LIVE
      setTimeout(()=>{
        liveRowEl?.scrollIntoView({behavior:'smooth', block:'center'});
      }, 0);
    } else {
      liveBadge.textContent = 'ไม่มีไลฟ์ขณะนี้';
      liveBadge.className = 'header__badge badge badge--upc';
      liveBadge.style.display = '';
    }
  }

  /** Interactions: expand/collapse (event delegation) **/
  tbody.addEventListener('click', (e)=>{
    const btn = e.target.closest('.js-expand');
    if (!btn) return;
    toggleRow(btn);
  });
  tbody.addEventListener('keydown', (e)=>{
    const btn = e.target.closest('.js-expand');
    if (!btn) return;
    if (e.key === 'Enter' || e.key === ' '){
      e.preventDefault();
      toggleRow(btn);
    }
  });
  function toggleRow(btn){
    const row = btn.closest('tr');
    const next = row?.nextElementSibling;
    if (!next || !next.classList.contains('row-detail')) return;

    const open = next.style.display !== 'none';

    // ปิดทุกอันก่อน
    Array.from(tbody.querySelectorAll('.row-detail')).forEach(r=>{
      r.style.display='none';
      const prev = r.previousElementSibling;
      prev?.classList.remove('is-open');
      prev?.querySelector('.js-expand')?.setAttribute('aria-expanded','false');
    });

    if (!open){
      next.style.display = '';
      row.classList.add('is-open');
      btn.setAttribute('aria-expanded','true');
    }
  }

  /** Data loader (hook ของ MeetingApp) **/
  const original = window.MeetingApp?.loadRoomSessions;
  window.MeetingApp = window.MeetingApp || {};

  window.MeetingApp.loadRoomSessions = async function(roomId){
    try{
      if (typeof original === 'function'){
        const list = await original.call(window.MeetingApp, roomId, { returnOnly: true });
        if (Array.isArray(list)) {
          render(list);
          return list;
        }
      }
      const res = await fetch(`/public/api/room_sessions.php?room_id=${roomId}`, {headers:{'X-Requested-With':'fetch'}});
      const data = await res.json();
      const list = data.sessions || data || [];
      render(list);
      return list;
    }catch(err){
      console.error(err);
      tbody.innerHTML = '';
      emptyState.style.display = '';
      liveBadge.textContent = 'ข้อผิดพลาดในการโหลด';
      liveBadge.className = 'header__badge badge badge--upc';
      liveBadge.style.display = '';
    }
  };

  // เรียกครั้งแรก + interval
  window.MeetingApp.loadRoomSessions(ROOM_ID);
  setInterval(()=>window.MeetingApp.loadRoomSessions(ROOM_ID), 10000);
})();
</script>
</body>
</html>
