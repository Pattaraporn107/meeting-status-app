<?php
declare(strict_types=1);

// ---- Bootstrap / DB ----
require __DIR__ . '/../app/db.php';
$pdo = db();
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// ดึงรายการห้อง (ไม่มี input จากผู้ใช้ -> safe)
$rooms = $pdo->query(
  "SELECT id, name, location FROM rooms ORDER BY display_order ASC, id ASC"
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

// ---- Helper ----
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
ob_start();
?>
<!doctype html>
<html lang="th" dir="ltr">
<head>
  <meta charset="utf-8">
  <link rel="icon" type="image/png" href="/public/assets/picture/MOPH202503.png">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>สถานะห้องประชุม</title>

  <!-- App CSS (self-hosted) -->
  <link rel="stylesheet" href="/public/assets/app.css">
  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com/3.4.10" defer></script>

  <style nonce="<?= $nonce ?>">
    .container{max-width:1200px;margin-inline:auto;padding:1rem}
    .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem}
    .card{border:1px solid #e5e7eb;border-radius:.75rem;padding:1rem;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.04)}
    .pill{width:.75rem;height:.75rem;border-radius:999px;background:#16a34a;box-shadow:0 0 0 3px rgba(22,163,74,.15)}

    /* Dropdown beautify */
    .pretty-select-btn{border-color:#bae6fd;box-shadow:0 4px 10px rgba(14,165,233,.15);background:#fff;border:1px solid #bae6fd;border-radius:1rem}
    .pretty-select-btn:hover{box-shadow:0 8px 18px rgba(14,165,233,.2)}
    .pretty-select-btn:focus{outline:3px solid rgba(14,165,233,.25);outline-offset:2px}
    #clearRoomSelect{border:1px solid #e5e7eb} #clearRoomSelect:hover{background:#f3f4f6}
    .card.highlight{outline:3px solid #22c55e;box-shadow:0 0 0 4px rgba(34,197,94,.2);transition:outline-color .3s ease}

    /* ===== Single-sheet Blue Accordion (แผ่นเดียว) ===== */
    .sheet{
      background: linear-gradient(180deg,#eaf2ff 0%,#eaf2ff 60%,#e7f0ff 100%);
      border-radius:.95rem; padding:.75rem 1rem;
      user-select:none; transition:box-shadow .15s ease, background-color .15s ease;
      box-shadow: 0 6px 20px rgba(37,99,235,.06), inset 0 1px 0 rgba(255,255,255,.6);
    }
    .sheet:hover{ box-shadow: 0 8px 24px rgba(37,99,235,.10) }
    .sheet[aria-expanded="true"] .chev{ transform:rotate(180deg) }

    .sheet-head{ display:flex; align-items:center; gap:.6rem }
    .sheet-next{ display:flex; align-items:center; gap:.6rem; margin-top:.45rem }

    .badge{ display:inline-flex; align-items:center; justify-content:center;
      padding:.22rem .62rem; border-radius:999px; font-weight:700; font-size:.75rem }
    .badge-live{ background:#ef4444; color:#fff }
    .badge-next{ background:#1d4ed8; color:#fff }

    .title-clip{ position:relative; max-width:100%; overflow:hidden; white-space:nowrap }
    .title-track{ display:inline-block; white-space:nowrap; font-weight:700; color:#0f172a }

    .chev{ width:1.1rem; height:1.1rem; margin-left:auto; transition:transform .18s ease; opacity:.9 }

    /* เส้นคั่นก่อนเนื้อหา */
    .sheet-sep{
      height:1px; background:linear-gradient(90deg,transparent,rgba(15,23,42,.10),transparent);
      margin:.7rem 0 .4rem;
    }

    /* เนื้อหาด้านใน (เป็นผืนเดียว) */
    .sheet-body{ overflow:hidden; transition:max-height .22s ease }
    .sheet-body[hidden]{ display:block; max-height:0 !important; padding-top:0 !important }
    .sheet-body:not([hidden]){ margin-top:.1rem }
    .sheet-content{ padding:.4rem 0 .2rem }

    /* ปรับเนื้อหา .current ให้กลืนกับแผ่น */
    .sheet-content .current{
      background: transparent;
      padding:.6rem .75rem; border-radius:.75rem;
      box-shadow: 0 1px 0 rgba(15,23,42,.04) inset;
      color:#0f172a;
    }
    .sheet-content .current h1,
    .sheet-content .current h2,
    .sheet-content .current h3,
    .sheet-content .current .title{
      font-weight:800; font-size:1.05rem; line-height:1.35; color:#0f172a; margin-bottom:.35rem;
    }
    .sheet-content .current p{ margin:.15rem 0; color:#111827 }
    .sheet-content .current .muted, .sheet-content .current small{ color:#374151 }

    /* Room header (ชื่อห้อง) */
    .room-head{display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem}
    .room-title{font-weight:700;font-size:1.125rem;color:#0f172a}
    .room-sub{font-weight:500;color:#4b5563;margin-left:.25rem}

    /* Skeleton */
    .skeleton{background:linear-gradient(100deg,#f3f4f6 40%,#e5e7eb 50%,#f3f4f6 60%);background-size:200% 100%;animation:loading 1.2s ease-in-out infinite;border-radius:.5rem;padding:.75rem 1rem}
    @keyframes loading{0%{background-position:200% 0}100%{background-position:-200% 0}}

    /* marquee */
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

  <!-- HEADER -->
  <header class="px-4 py-4 bg-white border-b border-gray-200">
    <div class="container">
      <div class="flex flex-col md:flex-row items-center gap-4">
        <div class="flex items-center gap-3">
          <img src="/public/assets/picture/A.png" alt="โลโก้หน่วยงาน" class="h-14 w-auto" loading="lazy" decoding="async" onerror="this.hidden=true">
          <img src="/public/assets/picture/MOPH202503.png" alt="ตรากระทรวงสาธารณสุข" class="h-14 w-auto" loading="lazy" decoding="async" onerror="this.hidden=true">
        </div>

        <div class="flex-1 text-center hidden md:block">
          <h1 class="text-2xl font-bold text-[#121111]">งานประชุมวิชาการกระทรวงสาธารณสุข ประจำปี 2568</h1>
          <p class="text-gray-700">ยกระดับการสาธารณสุขไทยสุขภาพแข็งแรงทุกวัย เศรษฐกิจสุขภาพไทยมั่นคง</p>
        </div>

        <!-- Dropdown -->
        <div class="search-wrap ml-auto">
          <div class="flex items-center gap-2">
            <div class="relative">
              <button type="button"
                class="pretty-select-btn inline-flex w-full min-w-[12rem] items-center justify-between gap-2 rounded-2xl bg-white px-4 py-2.5 text-slate-800 font-semibold shadow transition hover:shadow-md focus:outline-none"
                aria-label="เลือกห้องประชุม">
                <span id="roomSelectLabel" class="truncate">เลือกห้อง</span>
                <svg class="h-5 w-5 text-sky-500 pointer-events-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                  <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.24a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08z" clip-rule="evenodd"/>
                </svg>
              </button>
              <select id="roomSelect" class="absolute inset-0 h-full w-full opacity-0 cursor-pointer" aria-label="เลือกห้องประชุม">
                <option value="">— แสดงทุกห้อง —</option>
                <?php foreach ($rooms as $r): ?>
                  <option value="<?= h((string)$r['id']) ?>">
                    <?= h($r['name']) ?><?= !empty($r['location']) ? ' ('.h($r['location']).')' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <button id="clearRoomSelect" type="button"
              class="inline-flex items-center rounded-xl bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-200 transition hidden">
              ล้าง
            </button>
          </div>
          <noscript>
            <form action="/public/room.php" method="get" class="mt-2">
              <label for="roomSelectNoJS" class="sr-only">เลือกห้องประชุม</label>
              <select id="roomSelectNoJS" name="id" class="border rounded px-3 py-2">
                <option value="">— เลือกห้อง —</option>
                <?php foreach ($rooms as $r): ?>
                  <option value="<?= h((string)$r['id']) ?>"><?= h($r['name']) ?><?= !empty($r['location']) ? ' ('.h($r['location']).')' : '' ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="ml-2 inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-green-600 text-white font-semibold hover:bg-green-700 transition">ไป</button>
            </form>
          </noscript>
        </div>

        <div class="mt-4 text-center md:hidden">
          <h1 class="text-xl font-bold text-[#121111]">งานประชุมวิชาการกระทรวงสาธารณสุข 2568</h1>
          <p class="text-gray-700 text-sm">ยกระดับการสาธารณสุขไทยสุขภาพแข็งแรงทุกวัย เศรษฐกิจสุขภาพไทยมั่นคง</p>
          <noscript><p class="text-red-600 mt-2">ต้องเปิดใช้งาน JavaScript เพื่อแสดงสถานะปัจจุบัน</p></noscript>
        </div>
      </div>
    </div>
  </header>

  <main class="container">
    <div id="rooms" class="grid" role="list">
      <?php foreach ($rooms as $r): ?>
        <section class="card flex-col h-full" role="listitem" data-room-id="<?= h($r['id']) ?>">
          <!-- ชื่อห้อง -->
          <div class="room-head">
            <i class="pill" aria-hidden="true"></i>
            <h2 class="room-title">
              <?= h($r['name']) ?>
              <?php if (!empty($r['location'])): ?>
                <span class="room-sub">(<?= h($r['location']) ?>)</span>
              <?php endif; ?>
            </h2>
          </div>

          <!-- แผ่นฟ้า: ย่อ/ขยาย (exclusive) -->
          <div
            id="sheet-<?= h($r['id']) ?>"
            class="sheet"
            tabindex="0"
            role="button"
            aria-expanded="false"
            aria-controls="body-<?= h($r['id']) ?>"
            aria-label="ดูรายละเอียดของห้อง <?= h($r['name']) ?>"
          >
            <!-- head row -->
            <div class="sheet-head pause">
              <span class="badge badge-live">live</span>
              <span class="title-clip flex-1">
                <span class="live-title title-track">กำลังโหลด…</span>
              </span>
              <svg viewBox="0 0 20 20" fill="currentColor" class="chev text-slate-700" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.24a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08z" clip-rule="evenodd"/>
              </svg>
            </div>

            <!-- next row -->
            <div class="sheet-next pause">
              <span class="badge badge-next">upcoming</span>
              <span class="title-clip flex-1">
                <span class="next-title title-track">—</span>
              </span>
            </div>

            <!-- เส้นคั่น -->
            <div class="sheet-sep" aria-hidden="true"></div>

            <!-- body -->
            <div id="body-<?= h($r['id']) ?>" class="sheet-body" hidden>
              <div class="sheet-content">
                <div class="current" aria-live="polite">กำลังโหลด…</div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <footer class="card-foot mt-3">
            <a
              href="/public/room.php?<?= http_build_query(['id' => (string)$r['id']]) ?>"
              class="inline-flex items-center justify-center gap-2 px-5 py-2.5
                     rounded-xl bg-[#275937] text-white font-semibold shadow
                     hover:bg-white hover:text-[#275937] border border-[#275937]
                     transition duration-200 w-full sm:w-auto text-center"
              aria-label="ดูตารางกำหนดการของห้อง <?= h($r['name']) ?>">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
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

  <!-- App JS (self-hosted) -->
  <script src="/public/assets/app.js" defer></script>

  <!-- Inline JS -->
  <script nonce="<?= $nonce ?>">
    // init app เดิม
    window.addEventListener('load', () => {
      try { window.MeetingApp?.initPublic?.(); } catch(e){}
    });

    // ===== helper: ทำ/ลบ marquee เมื่อข้อความยาว =====
    function applyMarquee(trackEl){
      if (!trackEl) return;
      trackEl.classList.remove('marquee');
      const clip = trackEl.parentElement;
      if (clip && trackEl.scrollWidth > clip.clientWidth + 2) {
        trackEl.classList.add('marquee');
      }
    }

    // ===== ลบทิ้ง live badge + "กำลังบรรยาย" ใน panel (ลบจาก DOM จริง ๆ) =====
    function stripInnerLive(panelRoot){
      if (!panelRoot) return;

      // คำที่ถือว่าเป็นสถานะ live ภายในเนื้อหา (เพิ่มได้)
      const LIVE_TEXTS = ['กำลังบรรยาย','กำลังบรรยาย...','กำลังดำเนินการ','กำลังพูด'];

      // 1) ลบ badge live และ text node ถัดไปถ้าเป็นคำใน LIVE_TEXTS
      panelRoot.querySelectorAll('.badge-live').forEach(b=>{
        const next = b.nextSibling;
        b.remove();
        if (next && next.nodeType === Node.TEXT_NODE) {
          const t = (next.textContent || '').trim();
          if (LIVE_TEXTS.includes(t)) next.remove();
        }
      });

      // 2) ลบ element ทั้งก้อนถ้าเนื้อหามีแต่คำสถานะ (เช่น chip/wrapper)
      panelRoot.querySelectorAll('span, p, div, small, em, strong').forEach(el=>{
        const txt = (el.textContent || '').trim();
        if (!txt) return;
        // ถ้าไม่มีตัวอักษรอื่นนอกจากคำสถานะ ให้ลบทิ้งทั้งก้อน
        if (LIVE_TEXTS.includes(txt)) { el.remove(); return; }
        // ถ้าเป็น "live กำลังบรรยาย" ใน element เดียว ให้ลบทิ้ง
        const compact = txt.replace(/\s+/g,'');
        if (compact.includes('กำลังบรรยาย') && compact.length <= 'liveกำลังบรรยาย'.length + 6){
          el.remove();
        }
      });

      // 3) ลบ text node เดี่ยว ๆ ที่เหลือ (เช่น “…กำลังบรรยาย” ลอยตัว)
      const walker = document.createTreeWalker(panelRoot, NodeFilter.SHOW_TEXT, null);
      const toRemove = [];
      while (walker.nextNode()){
        const n = walker.currentNode;
        if ((n.textContent || '').trim() && LIVE_TEXTS.some(t => (n.textContent || '').trim() === t)){
          toRemove.push(n);
        }
      }
      toRemove.forEach(n=>{
        const parent = n.parentNode;
        n.remove();
        // ถ้าพ่อกลายเป็นว่างเปล่า ให้ลบพ่อด้วย (กันเศษ markup)
        if (parent && parent.nodeType === Node.ELEMENT_NODE && !parent.textContent.trim()){
          parent.remove();
        }
      });
    }

    // ===== Sync live/next อัตโนมัติ (ไม่ต้องกด) =====
    (function(){
      const root = document.getElementById('rooms');
      if(!root) return;

      function textFrom(card, sel){
        const el = card.querySelector(sel);
        return (el?.textContent || '').trim();
      }
      function syncTitles(card){
        const liveSel = '.sheet-body .current .title, .sheet-body .current h3, .sheet-body .current h4, .sheet-body .current strong, .sheet-body .current .topic-title';
        const nextSel = '.sheet-body .current .next .title, .sheet-body .current .next h4, .sheet-body .next .title, .sheet-body .next h4, .sheet-body .upcoming .title';

        const liveTxt = textFrom(card, liveSel) || '—';
        const nextTxt = textFrom(card, nextSel) || '—';

        const liveTrack = card.querySelector('.live-title');
        theNextTrack = card.querySelector('.next-title');

        if (liveTrack) { liveTrack.textContent = liveTxt; applyMarquee(liveTrack); }
        if (theNextTrack) { theNextTrack.textContent = nextTxt; applyMarquee(theNextTrack); }
      }

      // Observe ทุกห้อง
      root.querySelectorAll('[data-room-id]').forEach(card=>{
        const current = card.querySelector('.sheet-body .current');
        if (current){
          const obs = new MutationObserver(()=>{
            syncTitles(card);
            stripInnerLive(current);      // <<< ลบของซ้ำทุกครั้งที่ข้อมูลอัปเดต
          });
          obs.observe(current,{childList:true,subtree:true,characterData:true});
        }
        // first pass
        syncTitles(card);
        stripInnerLive(current);          // <<< ลบของซ้ำตั้งแต่แรก
      });
    })();

    // ===== Accordion Exclusive (คลิกที่แผ่นฟ้า) =====
    (function(){
      const root = document.getElementById('rooms');
      if(!root) return;

      function toggleSheet(box){
        const isOpen = box.getAttribute('aria-expanded') === 'true';
        const bodyId = box.getAttribute('aria-controls');
        const body = document.getElementById(bodyId);

        // ปิดทุกใบก่อน
        root.querySelectorAll('.sheet[aria-expanded="true"]').forEach(s=>{
          s.setAttribute('aria-expanded','false');
          const id = s.getAttribute('aria-controls');
          const b  = id && document.getElementById(id);
          if (b){ b.hidden = true; b.style.maxHeight = '0px'; }
        });

        // เปิด/ปิดใบที่คลิก
        if (!isOpen){
          box.setAttribute('aria-expanded','true');
          if (body){
            body.hidden = false;
            requestAnimationFrame(()=>{ body.style.maxHeight = body.scrollHeight + 'px'; });
          }
        } else {
          box.setAttribute('aria-expanded','false');
          if (body){
            body.style.maxHeight = '0px';
            setTimeout(()=>{ body.hidden = true; }, 220);
          }
        }
      }

      // Click & Keyboard
      root.addEventListener('click',(ev)=>{
        const box = ev.target.closest('.sheet'); if(!box || !root.contains(box)) return;
        toggleSheet(box);
      });
      root.addEventListener('keydown',(ev)=>{
        const box = ev.target.closest?.('.sheet'); if(!box) return;
        if (ev.key === 'Enter' || ev.key === ' '){ ev.preventDefault(); toggleSheet(box); }
      });
    })();

    // ===== Dropdown เลือกห้อง (เดิม) =====
    (function () {
      const sel = document.getElementById('roomSelect');
      const clearBtn = document.getElementById('clearRoomSelect');
      const grid = document.getElementById('rooms');
      const labelEl = document.getElementById('roomSelectLabel');
      if(!sel || !grid) return;

      if (typeof window.CSS === 'undefined') window.CSS = {};
      if (typeof window.CSS.escape !== 'function') {
        window.CSS.escape = (v) => String(v).replace(/[^a-zA-Z0-9_\-]/g, (c) => '\\' + c.codePointAt(0).toString(16) + ' ');
      }

      const cards = Array.from(grid.querySelectorAll('[data-room-id]'));

      function textFor(value){
        if (!value) return 'แสดงทุกห้อง';
        const opt = sel.querySelector(`option[value="${CSS.escape(String(value))}"]`);
        return (opt?.textContent?.trim() || 'เลือกห้อง');
      }
      function updateUI(id){
        if (labelEl) labelEl.textContent = textFor(id);
        if (clearBtn) clearBtn.classList.toggle('hidden', !id);
      }

      function applyFilter(id){
        const str = String(id || '');
        cards.forEach((card)=>{
          const match = card.getAttribute('data-room-id') === str;
          card.classList.remove('highlight');
          if (!str) card.classList.remove('hidden');
          else card.classList.toggle('hidden', !match);
        });

        if (str){
          const active = grid.querySelector(`[data-room-id="${CSS.escape(str)}"]`);
          if (active){
            active.classList.add('highlight');
            active.scrollIntoView({ behavior:'smooth', block:'start' });
            setTimeout(()=>active.classList.remove('highlight'),1500);
          }
        }

        const url = new URL(window.location.href);
        if (str) url.searchParams.set('room', str);
        else url.searchParams.delete('room');
        window.history.replaceState({}, '', url);

        updateUI(str);
      }

      sel.addEventListener('change', (e)=>applyFilter(e.target.value || ''), { passive:true });
      clearBtn?.addEventListener('click', ()=>{
        sel.value = ''; applyFilter(''); window.scrollTo({ top:0, behavior:'smooth' });
      });

      window.addEventListener('load', ()=>{
        const url = new URL(window.location.href);
        if (url.searchParams.has('room')){ url.searchParams.delete('room'); window.history.replaceState({}, '', url); }
        sel.value = ''; applyFilter('');
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
  </script>
</body>
</html>
<?php ob_end_flush(); ?>
