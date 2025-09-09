<?php 
/**
 * Admin Dashboard — Unified Status (live/upcoming/done) + Now/Next strip
 * - Keep original endpoints/IDs/logic (no breaking changes)
 * - Status is computed consistently with public pages:
 *   live: is_current=1 OR NOW() between start_time..end_time
 *   upcoming: start_time > NOW()
 *   done: end_time < NOW()
 * - Adds a Now/Next strip for the selected room (uses /public/api/status.php)
 */

declare(strict_types=1);

require __DIR__ . '/../app/db.php';
require __DIR__ . '/../app/auth.php';
require_login();

function h(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

$pdo = db();
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$rooms = $pdo->query("SELECT id, name, location FROM rooms ORDER BY display_order ASC, id ASC")->fetchAll();

if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
$cspNonce = base64_encode(random_bytes(16));
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
$csrfToken = $_SESSION['csrf'];

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header('Cross-Origin-Opener-Policy: same-origin');
header('Cross-Origin-Resource-Policy: same-origin');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
header(
  "Content-Security-Policy: " .
  "default-src 'self'; " .
  "img-src 'self' data:; " .
  "style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; " .
  "script-src 'self' https://cdn.jsdelivr.net 'nonce-$cspNonce'; " .
  "font-src 'self' data:; " .
  "connect-src 'self'; " .
  "form-action 'self'; " .
  "frame-ancestors 'self';"
);
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Dashboard</title>
  <link rel="icon" type="image/png" href="/public/assets/picture/MOPH202503.png">
  <meta name="csrf-token" content="<?= h($csrfToken) ?>">

  <!-- Tailwind -->
  <script nonce="<?= $cspNonce ?>" src="https://cdn.tailwindcss.com"></script>

  <!-- Optional app stylesheet (kept) -->
  <link rel="stylesheet" href="/public/assets/app.css">

  <!-- SweetAlert2 -->
  <script nonce="<?= $cspNonce ?>" src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Micro theming -->
  <style nonce="<?= $cspNonce ?>">
    html { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
    ::-webkit-scrollbar{ height:12px; width:12px; }
    ::-webkit-scrollbar-thumb{ background: rgba(15,23,42,.15); border-radius: 9999px; border:3px solid transparent; background-clip: content-box; }
    ::-webkit-scrollbar-thumb:hover{ background: rgba(15,23,42,.28); background-clip: content-box; }
    .sticky-shadow { box-shadow: 0 1px 0 0 rgba(15,23,42,.06); }
    .elevate { box-shadow: 0 10px 30px -12px rgba(2,6,23,.15); }
    .glass { background: rgba(255,255,255,.72); backdrop-filter: blur(10px); }

    /* Now/Next strip */
    .nn{display:flex;gap:.625rem;align-items:center}
    .nn-badge{display:inline-flex;align-items:center;border-radius:999px;padding:3px 10px;font-size:12px;font-weight:800}
    .nn-live{background:#fee2e2;color:#b91c1c}
    .nn-next{background:#e5f0ff;color:#1e40af}
    .nn-sep{width:1px;height:18px;background:#E6EBF0}
    .nn-clip{position:relative;max-width:100%;overflow:hidden;white-space:nowrap}
    .nn-title{display:inline-block;white-space:nowrap;font-weight:800;color:#0f172a}
    .nn-title.next{color:#6b7280}

    @keyframes nn-marquee { 0%{transform:translateX(0)} 100%{transform:translateX(-100%)} }
    .nn-marquee { animation: nn-marquee 14s linear infinite; will-change: transform }
    @media (prefers-reduced-motion:reduce){ .nn-marquee{ animation:none !important; transform:none !important } }
  </style>
</head>

<body class="min-h-screen bg-gradient-to-b from-slate-50 to-white text-slate-800 selection:bg-emerald-100/60 selection:text-emerald-900">
  <!-- Top Bar -->
  <div class="relative">
    <div class="absolute inset-0 bg-gradient-to-r from-emerald-50 via-teal-50 to-slate-50"></div>
    <header class="relative max-w-6xl mx-auto px-6 pt-6">
      <div class="glass elevate rounded-2xl px-5 py-4 flex items-center justify-between border border-slate-200/60">
        <div class="flex items-center gap-4">
          <div class="w-10 h-10 rounded-xl bg-emerald-600/90 text-white grid place-items-center shadow">
            <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M12 3l8 4.5v9L12 21 4 16.5v-9L12 3Z"></path>
              <path d="M12 8v13"></path>
            </svg>
          </div>
          <div>
            <h1 class="text-xl font-semibold tracking-tight">Admin Dashboard</h1>
            <p class="text-sm text-slate-500">จัดการ “ตารางกำหนดการ” รายห้อง</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <span class="hidden sm:inline text-sm text-slate-600">ยินดีต้อนรับ, <strong><?= h(current_admin()['name'] ?? 'Admin') ?></strong></span>
          <a href="/admin/logout.php" class="group">
            <button class="inline-flex items-center gap-2 pl-3 pr-3 h-10 bg-slate-900 text-white rounded-full shadow hover:shadow-md transition-all duration-200 hover:-translate-y-0.5">
              <svg class="w-4 h-4 opacity-90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M10 17l5-5-5-5"></path><path d="M15 12H3"></path><path d="M21 3v18"></path>
              </svg>
              <span class="text-sm font-medium">Logout</span>
            </button>
          </a>
        </div>
      </div>
    </header>
  </div>

  <main class="max-w-6xl mx-auto p-6 space-y-6">
    <!-- Filters / Actions -->
    <section class="space-y-4">
      <div class="glass elevate rounded-2xl border border-slate-200/60 p-4">
        <div class="flex flex-col sm:flex-row sm:items-end gap-4">
          <div class="sm:flex-1">
            <label for="room-select" class="block text-sm text-slate-600 mb-1">เลือกห้อง</label>
            <div class="relative">
              <select id="room-select"
                class="peer w-full sm:min-w-[260px] rounded-xl border border-slate-200 bg-white/90 px-3 py-2 pr-9 shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <?php foreach ($rooms as $r): ?>
                  <option value="<?= (int)$r['id'] ?>"><?= h($r['name']) ?><?= $r['location'] ? ' — '.h($r['location']) : '' ?></option>
                <?php endforeach; ?>
              </select>
              <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 peer-focus:text-emerald-600"
                   viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M6 9l6 6 6-6"></path>
              </svg>
            </div>
          </div>

          <div class="flex gap-2">
            <button id="reload-sessions"
              class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 12a9 9 0 10-3.9 7.5"></path><path d="M21 12h-6"></path></svg>
              <span class="font-medium">รีเฟรช</span>
            </button>
          </div>
        </div>

        <!-- Now/Next strip -->
        <div id="nnWrap" class="mt-4 hidden">
          <div class="nn">
            <span class="nn-badge nn-live">LIVE</span>
            <div class="nn-clip"><span id="nnLive" class="nn-title">—</span></div>
            <span class="nn-sep" aria-hidden="true"></span>
            <span class="nn-badge nn-next">UPCOMING</span>
            <div class="nn-clip"><span id="nnNext" class="nn-title next">—</span></div>
          </div>
        </div>
      </div>

      <!-- Sessions Card -->
      <div class="rounded-2xl bg-white elevate border border-slate-200/60">
        <div class="flex items-center justify-between px-4 py-3">
          <h2 class="text-lg font-semibold tracking-tight">ตารางกำหนดการ</h2>
          <div class="text-xs text-slate-500">คลิก “แก้ไข” เพื่อเปิดโมดัลได้ทันที</div>
        </div>

        <div class="overflow-hidden">
          <div class="overflow-x-auto">
            <table id="admin-session-table" class="w-full text-left">
              <thead class="text-slate-600 text-sm sticky top-0 bg-white sticky-shadow z-10">
                <tr class="border-y border-slate-200">
                  <th class="py-2.5 px-3 font-semibold">เวลาเริ่ม</th>
                  <th class="py-2.5 px-3 font-semibold">เวลาจบ</th>
                  <th class="py-2.5 px-3 font-semibold">หัวข้อ</th>
                  <th class="py-2.5 px-3 font-semibold">ผู้นำเสนอ</th>
                  <th class="py-2.5 px-3 font-semibold">สถานะ</th>
                  <th class="py-2.5 px-3 font-semibold">Current?</th>
                  <th class="py-2.5 px-3 font-semibold">เครื่องมือ</th>
                </tr>
              </thead>
              <tbody class="[&>tr:nth-child(even)]:bg-slate-50/40"></tbody>
            </table>

            <!-- Loading state -->
            <div id="table-loading" class="hidden py-10 px-3">
              <div class="flex items-center gap-3 text-slate-500">
                <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-slate-300 border-t-transparent"></span>
                <span class="text-sm">กำลังโหลดข้อมูล…</span>
              </div>
            </div>

            <!-- Empty state -->
            <div id="table-empty" class="hidden py-10 px-3">
              <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center bg-slate-50/50">
                <div class="text-slate-700 font-medium">ยังไม่มีตารางสำหรับห้องนี้</div>
                <div class="text-xs text-slate-500 mt-1">เพิ่มรายการด้านล่างได้เลย</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Upsert Form -->
      <div class="rounded-2xl bg-white elevate border border-slate-200/60 p-5 space-y-3">
        <h3 class="text-base font-semibold">เพิ่ม / แก้ไข Session</h3>
        <form id="session-form" class="space-y-3" autocomplete="off">
          <input type="hidden" name="id" value="">
          <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div>
              <label class="block text-sm text-slate-600 mb-1">หัวข้อ</label>
              <input class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500" name="topic" required>
            </div>
            <div>
              <label class="block text-sm text-slate-600 mb-1">ผู้นำเสนอ</label>
              <input class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500" name="speaker">
            </div>
            <div>
              <label class="block text-sm text-slate-600 mb-1">เริ่ม</label>
              <input class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500" type="datetime-local" name="start_time" required>
            </div>
            <div>
              <label class="block text-sm text-slate-600 mb-1">จบ</label>
              <input class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500" type="datetime-local" name="end_time" required>
            </div>
            <div>
              <label class="block text-sm text-slate-600 mb-1">สถานะ</label>
              <select class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500" name="status">
                <option value="upcoming">upcoming</option>
                <option value="live">live</option>
                <option value="done">done</option>
              </select>
            </div>
          </div>
          <div class="flex items-center gap-2 pt-1">
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-white shadow hover:bg-emerald-500 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 5v14M5 12h14"/></svg>
              บันทึก
            </button>
            <button type="button" id="reset-form" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18M3 12h12M3 18h6"/></svg>
              ล้างฟอร์ม
            </button>
          </div>
        </form>
      </div>
    </section>
  </main>

  <!-- Edit Modal -->
  <div id="editModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="editTitle">
    <div id="editModalBackdrop" class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
      <div class="w-full max-w-2xl rounded-2xl glass elevate border border-slate-200/60">
        <div class="flex items-center justify-between border-b border-slate-200/70 p-4">
          <h2 id="editTitle" class="text-lg font-bold">แก้ไข Session</h2>
          <button id="editModalClose" class="px-2 rounded-lg hover:bg-slate-100" aria-label="ปิด">✕</button>
        </div>
        <form id="sessionEdit-form" class="p-4 space-y-3" autocomplete="off">
          <input type="hidden" name="id" value="">
          <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 sm:col-span-6">
              <label class="block text-sm text-slate-600 mb-1">หัวข้อ</label>
              <input class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500" name="topic" required>
            </div>
            <div class="col-span-12 sm:col-span-6">
              <label class="block text-sm text-slate-600 mb-1">ผู้นำเสนอ</label>
              <input class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500" name="speaker">
            </div>
          </div>
          <div class="grid grid-cols-12 gap-4 mt-2">
            <div class="col-span-12 sm:col-span-6">
              <label class="block text-sm text-slate-600 mb-1">เริ่ม</label>
              <input class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500" type="datetime-local" name="start_time" required>
            </div>
            <div class="col-span-12 sm:col-span-6">
              <label class="block text-sm text-slate-600 mb-1">จบ</label>
              <input class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500" type="datetime-local" name="end_time" required>
            </div>
          </div>
          <div class="grid grid-cols-12 gap-4 mt-2">
            <div class="col-span-12 sm:col-span-6">
              <label class="block text-sm text-slate-600 mb-1">สถานะ</label>
              <select class="w-full sm:w-3/4 rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500" name="status">
                <option value="upcoming">upcoming</option>
                <option value="live">live</option>
                <option value="done">done</option>
              </select>
            </div>
          </div>
          <div class="mt-3 flex justify-end gap-2 p-1">
            <button class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500" type="button" id="edit-reset">
              รีเซ็ต
            </button>
            <button class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-white shadow hover:bg-emerald-500 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-emerald-500" type="submit">
              บันทึก
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- JS -->
  <script nonce="<?= $cspNonce ?>">
    const $  = (q, d=document) => d.querySelector(q);
    const $$ = (q, d=document) => Array.from(d.querySelectorAll(q));
    const toLocal = s => (s||'').slice(0,16).replace(' ','T');
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;

    async function jfetch(url, opts={}) {
      const r = await fetch(url, {
        headers: { 'X-Requested-With': 'fetch', 'X-CSRF-Token': CSRF },
        ...opts,
        credentials: 'same-origin'
      });
      if(!r.ok) throw new Error('network');
      return r.json();
    }
    function h(v){
      return String(v==null?'':v)
        .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
        .replaceAll('"','&quot;').replaceAll("'",'&#039;');
    }

    // ===== Status helpers (same rule as public pages)
    const pad = n => String(n).padStart(2,'0');
    const parseDT = s => new Date((s||'').replace(' ','T'));
    function computeStatus(item){
      if (String(item.is_current) === '1') return 'live';
      const now = new Date(), st = parseDT(item.start_time), en = parseDT(item.end_time);
      if (!isNaN(st) && !isNaN(en)) {
        if (st <= now && en >= now) return 'live';
        if (en < now) return 'done';
        if (st > now) return 'upcoming';
      }
      return 'upcoming';
    }
    function statusBadgeClass(st){
      if (st==='live') return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
      if (st==='done') return 'bg-slate-100 text-slate-700 ring-1 ring-slate-300';
      return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200';
    }

    // ===== Now/Next strip helpers
    function hhmm2(s){ const d=parseDT(s); if(isNaN(d))return'--:--'; return pad(d.getHours())+':'+pad(d.getMinutes()); }
    function applyMarqueeEl(el){
      if(!el) return;
      el.classList.remove('nn-marquee');
      const clip = el.parentElement;
      if(clip && el.scrollWidth > clip.clientWidth + 2) el.classList.add('nn-marquee');
    }
    async function loadNowNext(roomId){
      try{
        const res = await jfetch('/public/api/status.php');
        const room = (res.rooms||[]).find(r => String(r.room_id) === String(roomId));
        const wrap = $('#nnWrap'), liveEl = $('#nnLive'), nextEl = $('#nnNext');
        if(!wrap || !liveEl || !nextEl){ return; }

        let hasAny = false;
        if(room?.current){
          hasAny = true;
          liveEl.textContent = `${room.current.topic||'—'} | ${hhmm2(room.current.start_time)}-${hhmm2(room.current.end_time)}`;
          applyMarqueeEl(liveEl);
        }else{
          liveEl.textContent = '—';
        }
        if(room?.next){
          hasAny = true;
          nextEl.textContent = `${room.next.topic||'—'} | ${hhmm2(room.next.start_time)}-${hhmm2(room.next.end_time)}`;
          nextEl.classList.add('next');
          applyMarqueeEl(nextEl); // marquee สีเทา
        }else{
          nextEl.textContent = '—';
        }
        wrap.classList.toggle('hidden', !hasAny);
      }catch(_e){
        // เงียบไว้ ไม่ให้รบกวนงานหลัก
      }
    }

    function showLoading(show){ $('#table-loading').classList.toggle('hidden', !show); }
    function showEmpty(show){ $('#table-empty').classList.toggle('hidden', !show); }

    async function loadSessions(){
      const roomId = $('#room-select').value;
      const tbody = $('#admin-session-table tbody');
      tbody.innerHTML = '';
      showEmpty(false);
      showLoading(true);

      try {
        const data = await jfetch(`/public/api/room_sessions.php?room_id=${encodeURIComponent(roomId)}`);
        const list = (data.sessions || []).slice().sort((a,b)=>parseDT(a.start_time)-parseDT(b.start_time));

        showLoading(false);
        if(list.length === 0){ showEmpty(true); await loadNowNext(roomId); return; }

        list.forEach(s=>{
          const st = computeStatus(s);
          const tr = document.createElement('tr');
          tr.className = "align-top border-b last:border-b-0 border-slate-200 hover:bg-emerald-50/40 transition-colors";
          tr.innerHTML = `
            <td class="py-2.5 px-3 whitespace-nowrap text-slate-700">${h((s.start_time||'').replace('T',' ').slice(0,16))}</td>
            <td class="py-2.5 px-3 whitespace-nowrap text-slate-700">${h((s.end_time||'').replace('T',' ').slice(0,16))}</td>
            <td class="py-2.5 px-3 min-w-[240px] font-medium text-slate-800">${h(s.topic)}</td>
            <td class="py-2.5 px-3 text-slate-700">${h(s.speaker||'-')}</td>
            <td class="py-2.5 px-3">
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium ${statusBadgeClass(st)}">${h(st)}</span>
            </td>
            <td class="py-2.5 px-3">${String(s.is_current)==='1' || st==='live' ? '<span class="inline-flex items-center text-emerald-600">✔</span>' : ''}</td>
            <td class="py-2.5 px-3">
              <div class="flex flex-wrap gap-1.5">
                <button class="rounded-full border border-slate-200 bg-white/90 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500" data-action="edit" data-id="${s.id}">แก้ไข</button>
                <button class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-sm text-emerald-700 hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-500" data-action="current" data-id="${s.id}">ตั้งเป็นกำลังบรรยาย</button>
                <button class="rounded-full border border-rose-200 bg-rose-50 px-3 py-1.5 text-sm text-rose-700 hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-400" data-action="delete" data-id="${s.id}">ลบ</button>
              </div>
            </td>`;
          tbody.appendChild(tr);
        });

        // อัปเดต Now/Next จาก API กลาง (ให้ตรงกับหน้า public)
        await loadNowNext(roomId);

      } catch(e){
        showLoading(false);
        Swal.fire({ icon:'error', title:'โหลดข้อมูลไม่สำเร็จ', text:'โปรดลองอีกครั้ง' });
      }
    }

    async function setCurrent(id){
      const ok = await Swal.fire({
        title:'ยืนยันการทำงาน?', text:'ตั้งค่าเป็น “กำลังบรรยาย”', icon:'warning',
        showCancelButton:true, confirmButtonText:'ใช่', cancelButtonText:'ยกเลิก'
      }).then(r=>r.isConfirmed);
      if(!ok) return;
      const body = new URLSearchParams({ id:String(id), room_id: $('#room-select').value, csrf: CSRF });
      const res = await jfetch('/admin/api/set_active.php', { method:'POST', body });
      await loadSessions();
      Swal.fire({ icon:'success', title:'เปลี่ยนแปลงข้อมูลสำเร็จ', text: res.message||'ข้อมูลถูกตั้งค่าแล้ว' });
    }

    async function deleteSession(id){
      const ok = await Swal.fire({
        title:'ยืนยันการทำงาน?', text:'คุณต้องการลบข้อมูลใช่หรือไม่', icon:'question',
        showCancelButton:true, confirmButtonText:'ใช่', cancelButtonText:'ไม่'
      }).then(r=>r.isConfirmed);
      if(!ok) return;
      const body = new URLSearchParams({ id:String(id), csrf: CSRF });
      const res = await jfetch('/admin/api/delete_session.php', { method:'POST', body });
      await loadSessions();
      Swal.fire({ icon:'success', title:'ลบข้อมูลสำเร็จ', text: res.message||'ข้อมูลถูกลบแล้ว' });
    }

    function bindTableActions(){
      $('#admin-session-table').addEventListener('click', e=>{
        const btn = e.target.closest('button[data-action]');
        if(!btn) return;
        const id = btn.getAttribute('data-id');
        const action = btn.getAttribute('data-action');
        if(!id) return;

        if(action==='edit'){
          const row = btn.closest('tr');
          const d = {
            id: id,
            start_time: (row.children[0].textContent.trim() || '').replace(' ','T')+':00',
            end_time:   (row.children[1].textContent.trim() || '').replace(' ','T')+':00',
            topic:      row.children[2].textContent.trim(),
            speaker:    row.children[3].textContent.trim().replace(/^-$|^\s*$/,'')||'',
            status:     row.children[4].textContent.trim()
          };
          openEditModal(d);
        } else if(action==='current'){
          setCurrent(id);
        } else if(action==='delete'){
          deleteSession(id);
        }
      });
    }

    function openEditModal(d={}){
      const f = $('#sessionEdit-form');
      f.id.value = d.id || '';
      f.topic.value = d.topic || '';
      f.speaker.value = d.speaker || '';
      f.start_time.value = toLocal(d.start_time);
      f.end_time.value   = toLocal(d.end_time);
      f.status.value     = d.status || 'upcoming';
      $('#editModal').classList.remove('hidden');
      setTimeout(()=>f.topic?.focus(), 0);
    }
    const closeEditModal = ()=> $('#editModal').classList.add('hidden');

    ['#editModalClose', '#editModalBackdrop'].forEach(sel=>{
      document.addEventListener('click', e=>{ if(e.target.matches(sel)) closeEditModal(); });
    });
    window.addEventListener('keydown', e=>{ if(e.key==='Escape' && !$('#editModal').classList.contains('hidden')) closeEditModal(); });

    function handleForm(formSelector){
      const form = document.querySelector(formSelector);
      form.addEventListener('submit', async e=>{
        e.preventDefault();
        const room_id = $('#room-select').value;
        const fd = new FormData(form);
        fd.append('room_id', room_id);
        fd.append('csrf', CSRF);

        try{
          const res = await jfetch('/admin/api/upsert_session.php', { method:'POST', body: fd });
          await Swal.fire({ icon:'success', title: res.message||'บันทึกแล้ว' });
          form.reset();
          if(form.id && form.id.value) form.id.value = '';
          if(formSelector==='#sessionEdit-form') closeEditModal();
          loadSessions();
        }catch(e){
          Swal.fire({ icon:'error', title:'บันทึกไม่สำเร็จ', text:'โปรดตรวจสอบข้อมูลแล้วลองใหม่' });
        }
      });
    }

    function initForms(){
      handleForm('#session-form');
      handleForm('#sessionEdit-form');
      $('#reset-form').addEventListener('click', ()=>{ const f=$('#session-form'); f.reset(); f.id.value=''; });
      $('#edit-reset').addEventListener('click', ()=>{ const f=$('#sessionEdit-form'); f.reset(); f.id.value=''; });
    }

    bindTableActions();
    $('#reload-sessions').addEventListener('click', loadSessions);
    $('#room-select').addEventListener('change', loadSessions);
    initForms();
    loadSessions();

    // auto refresh every 20s (ไม่บังคับ)
    setInterval(loadSessions, 20000);
  </script>
</body>
</html>
