<?php
declare(strict_types=1);

// ---- Bootstrap / DB ----
require __DIR__ . '/../app/db.php';
$pdo = db();
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// ดึงรายการห้อง
$rooms = $pdo->query(
  "SELECT id, name, location, display_order FROM rooms ORDER BY display_order ASC, id ASC"
)->fetchAll();

// ---- Security Headers ----
$nonce = base64_encode(random_bytes(16));
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header('Cross-Origin-Opener-Policy: same-origin');
header('Cross-Origin-Resource-Policy: same-origin');
header(
  "Content-Security-Policy: " .
    "default-src 'self'; " .
    "img-src 'self' data:; " .
    "style-src 'self' https://cdn.tailwindcss.com 'unsafe-inline'; " .
    "script-src 'self' https://cdn.tailwindcss.com 'nonce-{$nonce}'; " .
    "connect-src 'self'; font-src 'self'; " .
    "base-uri 'self'; form-action 'self'; frame-ancestors 'self';"
);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="th" dir="ltr">
<head>
  <meta charset="utf-8">
  <link rel="icon" type="image/png" href="/public/assets/picture/MOPH202503.png">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>สถานะห้องประชุม</title>

  <link rel="stylesheet" href="/public/assets/app.css">
  <script src="https://cdn.tailwindcss.com/3.4.10" defer></script>

  <style nonce="<?= $nonce ?>">
    .container{max-width:1200px;margin-inline:auto;padding:1rem}
    .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem}
    .card{border:1px solid #e5e7eb;border-radius:.75rem;padding:1rem;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.04)}
    .pill{width:.75rem;height:.75rem;border-radius:999px;background:#16a34a;box-shadow:0 0 0 3px rgba(22,163,74,.15)}
    .sheet{background:linear-gradient(180deg,#eaf2ff 0%,#eaf2ff 60%,#e7f0ff 100%);border-radius:.95rem;padding:.75rem 1rem;user-select:none;transition:box-shadow .15s ease}
    .sheet:hover{ box-shadow:0 8px 24px rgba(37,99,235,.10) }
    .sheet[aria-expanded="true"] .chev{ transform:rotate(180deg) }
    .sheet-head{ display:flex; align-items:center; gap:.6rem }
    .sheet-next{ display:flex; align-items:center; gap:.6rem; margin-top:.45rem }
    .badge{ display:inline-flex; align-items:center; justify-content:center; padding:.22rem .62rem; border-radius:999px; font-weight:700; font-size:.75rem }
    .badge-live{ background:#ef4444; color:#fff }
    .badge-next{ background:#1d4ed8; color:#fff }
    .badge-done{ background:#6b7280; color:#fff }
    .title-clip{ position:relative; max-width:100%; overflow:hidden; white-space:nowrap }
    .title-track{ display:inline-block; white-space:nowrap; font-weight:700; color:#0f172a }
    .next-title{ color:#6b7280 } /* upcoming สีเทา */
    .chev{ width:1.1rem; height:1.1rem; margin-left:auto; transition:transform .18s ease; opacity:.9 }
    .sheet-sep{ height:1px; background:linear-gradient(90deg,transparent,rgba(15,23,42,.10),transparent); margin:.7rem 0 .4rem }
    .sheet-body{ overflow:hidden; transition:max-height .22s ease }
    .sheet-body[hidden]{ display:block; max-height:0 !important; padding-top:0 !important }
    .sheet-content{ padding:.4rem 0 .2rem }
    .current .title{ font-weight:800; font-size:1.05rem; line-height:1.35; color:#0f172a; margin-bottom:.35rem }
    .pretty-select-btn{border-color:#bae6fd;box-shadow:0 4px 10px rgba(14,165,233,.15);background:#fff;border:1px solid #bae6fd;border-radius:1rem}
    .pretty-select-btn:hover{box-shadow:0 8px 18px rgba(14,165,233,.2)}
    #clearRoomSelect{border:1px solid #e5e7eb}
    @keyframes marquee { 0%{transform:translateX(0)} 100%{transform:translateX(-100%)} }
    .marquee { animation:marquee 14s linear infinite; will-change:transform }
    .pause:hover .marquee { animation-play-state:paused }
    @media (prefers-reduced-motion:reduce){ .marquee{ animation:none !important; transform:none !important } }
  </style>
</head>

<body class="min-h-dvh bg-gray-50 text-gray-900">
  <!-- Back To Top -->
  <button id="backToTop"
    class="fixed bottom-6 right-6 bg-green-600 text-white px-4 py-2 rounded-full shadow-lg hover:bg-green-700 transition duration-300 hidden"
    type="button" aria-label="กลับขึ้นบนสุด">↑ Top</button>

  <header class="px-4 py-4 bg-white border-b border-gray-200">
    <div class="container">
      <div class="flex flex-col md:flex-row items-center gap-4">
        <div class="flex items-center gap-3">
          <img src="/public/assets/picture/A.png" class="h-14 w-auto" alt="" loading="lazy" onerror="this.hidden=true">
          <img src="/public/assets/picture/MOPH202503.png" class="h-14 w-auto" alt="" loading="lazy" onerror="this.hidden=true">
        </div>

        <div class="flex-1 text-center hidden md:block">
          <h1 class="text-2xl font-bold">งานประชุมวิชาการกระทรวงสาธารณสุข ประจำปี 2568</h1>
          <p class="text-gray-700">ยกระดับการสาธารณสุขไทยสุขภาพแข็งแรงทุกวัย เศรษฐกิจสุขภาพไทยมั่นคง</p>
        </div>

        <!-- Dropdown เลือกห้อง -->
        <div class="search-wrap ml-auto">
          <div class="flex items-center gap-2">
            <div class="relative">
              <button type="button"
                class="pretty-select-btn inline-flex w-full min-w-[12rem] items-center justify-between gap-2 px-4 py-2.5 font-semibold">
                <span id="roomSelectLabel" class="truncate">เลือกห้อง</span>
                <svg class="h-5 w-5 text-sky-500 pointer-events-none" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.24a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08z" clip-rule="evenodd"/></svg>
              </button>
              <select id="roomSelect" class="absolute inset-0 h-full w-full opacity-0 cursor-pointer" aria-label="เลือกห้องประชุม">
                <option value="">— แสดงทุกห้อง —</option>
                <?php foreach ($rooms as $r): ?>
                  <option value="<?= h((string)$r['id']) ?>"><?= h($r['name']) ?><?= !empty($r['location']) ? ' ('.h($r['location']).')' : '' ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <button id="clearRoomSelect" type="button" class="inline-flex items-center rounded-xl bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-200 transition hidden">ล้าง</button>
          </div>
        </div>
      </div>
    </div>
  </header>

  <main class="container">
    <div id="rooms" class="grid" role="list">
      <?php foreach ($rooms as $r): ?>
        <section class="card flex-col h-full" role="listitem" data-room-id="<?= h($r['id']) ?>">
          <div class="room-head">
            <i class="pill" aria-hidden="true"></i>
            <h2 class="font-bold text-lg">
              <?= h($r['name']) ?> <?php if(!empty($r['location'])): ?><span class="text-slate-600">(<?= h($r['location']) ?>)</span><?php endif; ?>
            </h2>
          </div>

          <div id="sheet-<?= h($r['id']) ?>" class="sheet" tabindex="0" role="button" aria-expanded="false" aria-controls="body-<?= h($r['id']) ?>">
            <div class="sheet-head pause">
              <span class="badge badge-live">live</span>
              <span class="title-clip flex-1"><span class="live-title title-track">—</span></span>
              <svg viewBox="0 0 20 20" fill="currentColor" class="chev text-slate-700" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.24a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08z" clip-rule="evenodd"/></svg>
            </div>
            <div class="sheet-next pause">
              <span class="badge badge-next">upcoming</span>
              <span class="title-clip flex-1"><span class="next-title title-track">—</span></span>
            </div>
            <div class="sheet-sep" aria-hidden="true"></div>
            <div id="body-<?= h($r['id']) ?>" class="sheet-body" hidden>
              <div class="sheet-content"><div class="current">—</div></div>
            </div>
          </div>

          <footer class="mt-3">
            <a href="/public/room.php?<?= http_build_query(['id'=>(string)$r['id']]) ?>"
               class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-[#275937] text-white font-semibold shadow hover:bg-white hover:text-[#275937] border border-[#275937] transition w-full sm:w-auto">
              ดูตารางกำหนดการ
            </a>
          </footer>
        </section>
      <?php endforeach; ?>
    </div>
  </main>

  <!-- Inline JS: รวมทั้งหมดในไฟล์นี้ -->
  <script nonce="<?= $nonce ?>">
  // ===== Helpers =====
  function applyMarquee(el){
    if(!el) return;
    el.classList.remove('marquee');
    const clip = el.parentElement;
    if(clip && el.scrollWidth > clip.clientWidth + 2) el.classList.add('marquee');
  }
  function hhmm(dtStr){
    if(!dtStr) return '--:--';
    const d = new Date(String(dtStr).replace(' ','T'));
    if (isNaN(d)) return '--:--';
    const pad = n => String(n).padStart(2,'0');
    return pad(d.getHours())+':'+pad(d.getMinutes());
  }
  function setBadge(el, status){
    if(!el) return;
    el.classList.remove('badge-live','badge-next','badge-done');
    if(status==='live') el.classList.add('badge-live');
    else if(status==='upcoming') el.classList.add('badge-next');
    else el.classList.add('badge-done');
  }
  function esc(s){ return String(s??'').replace(/[&<>"]/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m])); }

  // ===== MeetingApp (ย่อส่วน เท่าที่หน้า index ใช้) =====
  window.MeetingApp = (function(){
    async function fetchJSON(url, opts){
      const res = await fetch(url, {cache:'no-store', ...opts});
      if(!res.ok) throw new Error('Network error');
      return res.json();
    }
    async function updatePublicCards(){
      const data = await fetchJSON('/public/api/status.php');
      const byId = new Map();
      (data.rooms||[]).forEach(r=>byId.set(Number(r.room_id), r));

      document.querySelectorAll('[data-room-id]').forEach(card=>{
        const id = Number(card.getAttribute('data-room-id'));
        const r = byId.get(id);

        const liveBadge = card.querySelector('.sheet-head .badge');
        const nextBadge = card.querySelector('.sheet-next .badge');
        const liveTrack = card.querySelector('.live-title');
        const nextTrack = card.querySelector('.next-title');
        const body = card.querySelector('.sheet-body .sheet-content');

        // reset
        if(liveTrack){ liveTrack.textContent = '—'; liveTrack.classList.remove('marquee'); }
        if(nextTrack){ nextTrack.textContent = '—'; nextTrack.classList.remove('marquee'); }

        // LIVE
        if(r && r.current){
          setBadge(liveBadge,'live');
          if(liveTrack){
            liveTrack.textContent = r.current.topic || '—';
            applyMarquee(liveTrack);
          }
          if(body){
            body.innerHTML = `
              <div class="current">
                <div class="title">${esc(r.current.topic||'')}</div>
                <p class="text-slate-600">${r.current.speaker ? 'วิทยากร: '+esc(r.current.speaker) : ''}</p>
                <p class="text-slate-500">เวลา: ${hhmm(r.current.start_time)} - ${hhmm(r.current.end_time)}</p>
              </div>`;
          }
        }else{
          setBadge(liveBadge,'done');
          if(body){
            body.innerHTML = `<div class="current text-slate-500">ยังไม่เริ่ม หรือไม่มีหัวข้อที่กำลังบรรยาย</div>`;
          }
        }

        // UPCOMING (สีเทา + marquee)
        if(r && r.next){
          setBadge(nextBadge,'upcoming');
          if(nextTrack){
            nextTrack.textContent = `${r.next.topic || '—'} | ${hhmm(r.next.start_time)}-${hhmm(r.next.end_time)}`;
            nextTrack.classList.add('next-title');
            applyMarquee(nextTrack);
          }
        }else{
          setBadge(nextBadge,'done');
        }
      });
    }
    function initPublic(){
      updatePublicCards();
      setInterval(updatePublicCards, 30000);
    }
    return { initPublic, updatePublicCards };
  })();

  // ===== Dropdown filter =====
  (function(){
    const sel = document.getElementById('roomSelect');
    const clearBtn = document.getElementById('clearRoomSelect');
    const grid = document.getElementById('rooms');
    const labelEl = document.getElementById('roomSelectLabel');
    if(!sel || !grid) return;

    const cards = Array.from(grid.querySelectorAll('[data-room-id]'));
    function textFor(v){
      if(!v) return 'แสดงทุกห้อง';
      const opt = sel.querySelector(`option[value="${CSS.escape(String(v))}"]`);
      return (opt?.textContent?.trim() || 'เลือกห้อง');
    }
    function updateUI(id){
      labelEl.textContent = textFor(id);
      clearBtn.classList.toggle('hidden', !id);
    }
    function applyFilter(id){
      const str = String(id || '');
      cards.forEach(card=>{
        const match = card.getAttribute('data-room-id') === str;
        if(!str) card.classList.remove('hidden');
        else card.classList.toggle('hidden', !match);
      });
      updateUI(str);
    }
    sel.addEventListener('change', e=>applyFilter(e.target.value||''), {passive:true});
    clearBtn.addEventListener('click', ()=>{ sel.value=''; applyFilter(''); window.scrollTo({top:0,behavior:'smooth'}); });
    window.addEventListener('load', ()=>{ sel.value=''; applyFilter(''); });
  })();

  // ===== Accordion Exclusive =====
  (function(){
    const root = document.getElementById('rooms');
    if(!root) return;
    function toggleSheet(box){
      const isOpen = box.getAttribute('aria-expanded') === 'true';
      const bodyId = box.getAttribute('aria-controls');
      const body = document.getElementById(bodyId);
      // ปิดใบอื่น
      root.querySelectorAll('.sheet[aria-expanded="true"]').forEach(s=>{
        s.setAttribute('aria-expanded','false');
        const id = s.getAttribute('aria-controls');
        const b = id && document.getElementById(id);
        if(b){ b.hidden = true; b.style.maxHeight = '0px'; }
      });
      // เปิด/ปิดใบที่คลิก
      if(!isOpen){
        box.setAttribute('aria-expanded','true');
        if(body){ body.hidden = false; requestAnimationFrame(()=>{ body.style.maxHeight = body.scrollHeight + 'px'; }); }
      }else{
        box.setAttribute('aria-expanded','false');
        if(body){ body.style.maxHeight = '0px'; setTimeout(()=>{ body.hidden = true; }, 220); }
      }
    }
    root.addEventListener('click', ev=>{
      const box = ev.target.closest('.sheet'); if(!box || !root.contains(box)) return;
      toggleSheet(box);
    });
    root.addEventListener('keydown', ev=>{
      const box = ev.target.closest?.('.sheet'); if(!box) return;
      if(ev.key==='Enter'||ev.key===' '){ ev.preventDefault(); toggleSheet(box); }
    });
  })();

  // ===== Back To Top =====
  (function(){
    const btn = document.getElementById('backToTop');
    const onScroll = () => ((document.documentElement.scrollTop>200)||(document.body.scrollTop>200))
      ? btn.classList.remove('hidden') : btn.classList.add('hidden');
    const toTop = () => window.scrollTo({ top:0, behavior:'smooth' });
    window.addEventListener('scroll', onScroll, { passive:true });
    btn.addEventListener('click', toTop);
  })();

  // ===== Boot =====
  window.addEventListener('load', ()=>{
    window.MeetingApp.initPublic();
  });
  </script>
</body>
</html>
