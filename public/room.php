<?php
declare(strict_types=1);

require __DIR__ . '/../app/db.php';
$pdo = db();
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$room_id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT id, name, location FROM rooms WHERE id=?");
$st->execute([$room_id]);
$room = $st->fetch();
if(!$room){ http_response_code(404); echo "ไม่พบห้อง"; exit; }

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
  --bg:#F6F8FB; --card:#fff; --ink:#0f1f1a; --ink-2:#334155; --muted:#64748b; --line:#E6EBF0; --line-2:#F1F5F9;
  --live-bg:#fee2e2; --live-ink:#b91c1c; --upc-bg:#ecfeff; --upc-ink:#0369a1; --done-bg:#eef2ff; --done-ink:#3730a3;
}
*{box-sizing:border-box} body{margin:0;background:var(--bg);color:var(--ink);font-family:system-ui,-apple-system,"Segoe UI",Roboto,"Noto Sans Thai","Noto Sans",Arial,sans-serif}
.page-wrap{max-width:1100px;margin:0 auto;padding:24px 14px}
.header{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;margin:14px 0 16px}
.header__title{margin:0;font-size:28px;font-weight:800}.header__sub{color:var(--muted);font-weight:600}
.header__badge{display:inline-flex;align-items:center;border-radius:999px;padding:4px 12px;font-size:12px;font-weight:800}
.card{background:var(--card);border:1px solid var(--line);border-radius:14px;box-shadow:0 8px 24px rgba(0,0,0,.06);overflow:hidden}
.table{width:100%;border-collapse:separate;border-spacing:0}
.table thead th{background:#F8FAFC;color:#0f172a;text-align:left;padding:12px 14px;font-weight:800;position:sticky;top:0;z-index:2}
.table tbody td{padding:12px 14px;border-top:1px solid var(--line-2);vertical-align:top}
.btn-icon{appearance:none;border:1px solid var(--line);background:var(--card);border-radius:10px;width:38px;height:38px;display:grid;place-items:center;cursor:pointer}
.chev{transition:transform .15s} tr.is-open .chev{transform:rotate(180deg)}
.badge{display:inline-flex;align-items:center;border-radius:999px;padding:2px 10px;font-size:12px;font-weight:800}
.badge--live{background:var(--live-bg);color:var(--live-ink)} .badge--upc{background:var(--upc-bg);color:var(--upc-ink)} .badge--done{background:var(--done-bg);color:var(--done-ink)}
.row-detail td{background:#FBFEFF;border-top:1px dashed #dbe7f3}.detail-abstract{white-space:pre-wrap;color:var(--ink-2);padding-top:6px}
.topic{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.empty{padding:28px;text-align:center;color:var(--muted)}
/* Now/Next */
.now-next{display:flex;gap:10px;align-items:center;margin:10px 0 16px}
.nn-badge{display:inline-flex;align-items:center;border-radius:999px;padding:3px 10px;font-size:12px;font-weight:800}
.nn-live{background:#fee2e2;color:#b91c1c}.nn-next{background:#e5f0ff;color:#1e40af}
.nn-clip{position:relative;max-width:100%;overflow:hidden;white-space:nowrap}.nn-title{display:inline-block;white-space:nowrap;font-weight:800;color:#0f172a}
.nn-title.next{color:#6b7280}
@keyframes nn-marquee{0%{transform:translateX(0)}100%{transform:translateX(-100%)}} .nn-marquee{animation:nn-marquee 14s linear infinite;will-change:transform}
@media (prefers-reduced-motion:reduce){.nn-marquee{animation:none!important;transform:none!important}}
</style>
</head>
<body>
  <div class="page-wrap">
    <a class="breadcrumb" href="/public/index.php" style="text-decoration:none;color:#1C4724;font-weight:800">← กลับหน้ารวม</a>

    <header class="header" aria-live="polite">
      <h1 class="header__title"><?= h($room['name']) ?> <span class="header__sub">(<?= h($room['location'] ?? '-') ?>)</span></h1>
      <span id="liveBadge" class="header__badge" style="display:none"></span>
    </header>

    <!-- Now/Next -->
    <div id="nnWrap" class="now-next" style="display:none">
      <span class="nn-badge nn-live">LIVE</span>
      <div class="nn-clip"><span id="nnLive" class="nn-title">—</span></div>
      <span style="width:1px;height:18px;background:#E6EBF0"></span>
      <span class="nn-badge nn-next">UPCOMING</span>
      <div class="nn-clip"><span id="nnNext" class="nn-title next">—</span></div>
    </div>

    <div class="card" role="region" aria-label="ตารางกำหนดการห้อง">
      <table id="session-table" class="table">
        <thead>
          <tr>
            <th style="width:64px">รายละเอียด</th>
            <th style="width:190px">เวลา</th>
            <th>หัวข้อ</th>
            <th style="width:240px">ผู้นำเสนอ</th>
            <th style="width:140px">สถานะ</th>
          </tr>
        </thead>
        <tbody>
          <tr class="skel-row"><td colspan="5" style="padding:16px;color:#64748b">กำลังโหลด…</td></tr>
        </tbody>
      </table>
      <div id="emptyState" class="empty" style="display:none">
        <p>วันนี้ยังไม่มีรายการ</p>
      </div>
    </div>
  </div>

<script>
(function(){
  const ROOM_ID = <?= (int)$room['id'] ?>;
  const tbody = document.querySelector('#session-table tbody');
  const emptyState = document.getElementById('emptyState');
  const liveBadge = document.getElementById('liveBadge');

  const pad = n => String(n).padStart(2,'0');
  const parseDT = s => new Date((s||'').replace(' ','T'));
  const esc = s => String(s??'').replace(/[&<>"]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m]));
  const tm = d => `${pad(d.getHours())}:${pad(d.getMinutes())}`;
  const fmtRange = s => { const st=parseDT(s.start_time), en=parseDT(s.end_time); return (isNaN(st)||isNaN(en))?'-':`${tm(st)} - ${tm(en)}`; };

  function computeStatus(x){
    if (String(x.is_current)==='1') return 'live';
    const now=new Date(), st=parseDT(x.start_time), en=parseDT(x.end_time);
    if(!isNaN(st)&&!isNaN(en)){ if(st<=now && en>=now) return 'live'; if(en<now) return 'done'; if(st>now) return 'upcoming'; }
    return 'upcoming';
  }
  const badge = st => st==='live' ? 'badge badge--live">LIVE' : st==='done' ? 'badge badge--done">Done' : 'badge badge--upc">Upcoming';

  function applyMarqueeEl(el){
    if(!el) return;
    el.classList.remove('nn-marquee');
    const clip = el.parentElement;
    if(clip && el.scrollWidth > clip.clientWidth + 2) el.classList.add('nn-marquee');
  }
  function hhmm2(s){ const d=parseDT(s); if(isNaN(d))return'--:--'; return tm(d); }

  async function loadNowNext(){
    try{
      const res = await fetch('/public/api/status.php', {cache:'no-store'});
      const json = await res.json();
      const room = (json.rooms||[]).find(r=>String(r.room_id)==='<?= (int)$room['id'] ?>');
      const wrap=document.getElementById('nnWrap'), L=document.getElementById('nnLive'), N=document.getElementById('nnNext');
      let any=false;
      if(room?.current){ any=true; L.textContent=`${room.current.topic||'—'} | ${hhmm2(room.current.start_time)}-${hhmm2(room.current.end_time)}`; applyMarqueeEl(L); }
      else { L.textContent='—'; }
      if(room?.next){ any=true; N.textContent=`${room.next.topic||'—'} | ${hhmm2(room.next.start_time)}-${hhmm2(room.next.end_time)}`; applyMarqueeEl(N); }
      else { N.textContent='—'; }
      wrap.style.display = any ? '' : 'none';
    }catch(_e){}
  }

  async function loadSessions(){
    tbody.innerHTML = '<tr><td colspan="5" style="padding:16px;color:#64748b">กำลังโหลด…</td></tr>';
    const res = await fetch(`/public/api/room_sessions.php?room_id=${ROOM_ID}`, {headers:{'X-Requested-With':'fetch'}});
    const data = await res.json();
    const list = (data.sessions||[]).slice().sort((a,b)=>parseDT(a.start_time)-parseDT(b.start_time));

    tbody.innerHTML='';
    if(list.length===0){ emptyState.style.display=''; liveBadge.textContent='วันนี้ยังไม่มีรายการ'; liveBadge.className='header__badge badge badge--upc'; liveBadge.style.display=''; return; }
    emptyState.style.display='none';

    let hasLive=false, liveRow=null;
    let nextUpcoming=null, currentLive=null; const now=new Date();

    list.forEach(s=>{
      const st=computeStatus(s);
      if(!currentLive && st==='live') currentLive=s;
      if(!nextUpcoming && st==='upcoming' && parseDT(s.start_time)>now) nextUpcoming=s;
      if(st==='live') hasLive=true;

      const tr=document.createElement('tr'); tr.className='row';
      tr.innerHTML = `
        <td><button class="btn-icon js-expand" aria-expanded="false" aria-controls="d-${s.id}">
          <svg class="chev" width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="#334155" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button></td>
        <td><strong>${fmtRange(s)}</strong></td>
        <td><div class="topic" title="${esc(s.topic)}">${esc(s.topic)}</div></td>
        <td>${esc(s.speaker)||'-'}</td>
        <td><span class="${badge(st)}</span></td>`;
      const tr2=document.createElement('tr'); tr2.className='row-detail'; tr2.id=`d-${s.id}`; tr2.style.display='none';
      const abstract = s.notes || '';
      tr2.innerHTML = `<td colspan="5"><div class="detail-abstract">${esc(abstract||'ไม่มีบทคัดย่อ')}</div></td>`;
      tbody.appendChild(tr); tbody.appendChild(tr2);
      if(st==='live' || String(s.is_current)==='1') liveRow=tr;
    });

    if(hasLive){ liveBadge.textContent='กำลังถ่ายทอดสด'; liveBadge.className='header__badge badge badge--live'; liveBadge.style.display=''; setTimeout(()=>liveRow?.scrollIntoView({behavior:'smooth',block:'center'}),0); }
    else{ liveBadge.textContent='ไม่มีไลฟ์ขณะนี้'; liveBadge.className='header__badge badge badge--upc'; liveBadge.style.display=''; }

    // Update Now/Next strip ด้วยข้อมูลจริงจาก API กลาง (ซิงก์กับหน้า index)
    await loadNowNext();
  }

  // expand/collapse
  document.querySelector('#session-table').addEventListener('click', e=>{
    const btn = e.target.closest('.js-expand'); if(!btn) return;
    const row = btn.closest('tr'); const det = row?.nextElementSibling;
    if(!det || !det.classList.contains('row-detail')) return;
    const open = det.style.display !== 'none';
    document.querySelectorAll('.row-detail').forEach(r=>{ r.style.display='none'; r.previousElementSibling?.classList.remove('is-open'); r.previousElementSibling?.querySelector('.js-expand')?.setAttribute('aria-expanded','false'); });
    if(!open){ det.style.display=''; row.classList.add('is-open'); btn.setAttribute('aria-expanded','true'); }
  });

  // run
  loadSessions();
  setInterval(loadSessions, 10000);
})();
</script>
</body>
</html>
