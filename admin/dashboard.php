<?php
/**
 * Admin Dashboard (Secure & Polished)
 * - Hardened security headers + CSP with nonce
 * - Output escaped via helper h()
 * - CSRF token for state‑changing requests
 * - Organized markup with accessible Tailwind UI
 * - No inline event handlers; delegated JS
 */

declare(strict_types=1);

require __DIR__ . '/../app/db.php';
require __DIR__ . '/../app/auth.php';
require_login();

// ---- Helpers ----
function h(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// ---- DB ----
$pdo = db();
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$rooms = $pdo->query("SELECT id, name, location, display_order FROM rooms ORDER BY display_order ASC, id ASC")->fetchAll();

// ---- Security: Nonce + CSRF ----
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
$cspNonce = base64_encode(random_bytes(16));
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf'];

// ---- Security Headers ----
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header('Cross-Origin-Opener-Policy: same-origin');
header('Cross-Origin-Resource-Policy: same-origin');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
// CSP: allow self + needed CDNs (tailwind & sweetalert via jsDelivr). Inline scripts must carry nonce.
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
  <!-- Tailwind (CDN build ok behind CSP) -->
  <script nonce="<?= $cspNonce ?>" src="https://cdn.tailwindcss.com"></script>
  <!-- Your compiled app styles (optional) -->
  <link rel="stylesheet" href="/public/assets/app.css">
  <!-- SweetAlert2 -->
  <script nonce="<?= $cspNonce ?>" src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    /* light polish for non-TW areas */
    .card{ @apply rounded-2xl shadow p-4 bg-white; }
    .btn{ @apply inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 transition; }
    .btn.primary{ @apply bg-emerald-600 text-white border-emerald-600 hover:bg-emerald-500; }
    .table{ @apply w-full text-left border-separate border-spacing-y-2; }
    .table thead th{ @apply text-slate-600 text-sm font-semibold; }
    .table tbody tr{ @apply bg-white shadow rounded-xl; }
    .table td, .table th{ @apply px-4 py-3 align-top; }
  </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
  <div class="max-w-6xl mx-auto p-6 space-y-6">
    <!-- Header / Session info -->
    <header class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Admin Dashboard</h1>
        <p class="text-sm text-slate-500">จัดตารางกำหนดการ & จัดเรียงห้อง</p>
      </div>
      <div class="flex items-center gap-3">
        <span class="text-sm text-slate-600">ยินดีต้อนรับ, <strong><?= h(current_admin()['name'] ?? 'Admin') ?></strong></span>
        <a href="/admin/logout.php" class="group relative">
          <button class="inline-flex items-center w-11 h-11 justify-center bg-rose-600 rounded-full text-white shadow hover:w-32 hover:rounded-lg transition-all duration-200">
            <svg class="w-4 h-4 transition-all group-hover:ml-3" viewBox="0 0 512 512" fill="currentColor" aria-hidden="true"><path d="M377.9 105.9 500.7 228.7a38.6 38.6 0 0 1 0 54.6L377.9 406.1a33.9 33.9 0 0 1-57.9-24v-62.1H192a32 32 0 0 1-32-32v-64a32 32 0 0 1 32-32h128v-62.1a33.9 33.9 0 0 1 57.9-24ZM160 96H96a32 32 0 0 0-32 32v256a32 32 0 0 0 32 32h64a32 32 0 1 1 0 64H96C43 480 0 437 0 384V128C0 75 43 32 96 32h64a32 32 0 1 1 0 64Z"/></svg>
            <span class="absolute right-5 translate-x-full opacity-0 group-hover:translate-x-0 group-hover:opacity-100 text-sm font-semibold">Logout</span>
          </button>
        </a>
      </div>
    </header>

    <!-- Sort Rooms -->
    <section class="space-y-3">
      <h2 class="text-lg font-semibold">จัดลำดับการแสดงผลห้อง (ลาก‑วาง)</h2>
      <ul id="room-sortable" class="card divide-y divide-slate-100" role="list" aria-label="Room ordering">
        <?php foreach ($rooms as $r): ?>
          <li class="flex items-center justify-between py-2" draggable="true" data-room-id="<?= (int)$r['id'] ?>">
            <div class="flex items-center gap-3">
              <span class="cursor-grab select-none text-slate-400">⇅</span>
              <div class="font-medium"><?= h($r['name']) ?></div>
              <small class="text-slate-500">(<?= h($r['location'] ?? '-') ?>)</small>
            </div>
            <div class="text-xs text-slate-500">order: <code><?= (int)$r['display_order'] ?></code></div>
          </li>
        <?php endforeach; ?>
      </ul>
      <button id="save-order" class="btn primary">บันทึกการจัดลำดับ</button>
    </section>

    <hr class="border-slate-200">

    <!-- Sessions -->
    <section class="space-y-4">
      <div class="flex items-end gap-3">
        <div>
          <label class="block text-sm text-slate-600">เลือกห้อง</label>
          <select id="room-select" class="btn min-w-[220px]">
            <?php foreach ($rooms as $r): ?>
              <option value="<?= (int)$r['id'] ?>"><?= h($r['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button id="reload-sessions" class="btn">รีเฟรช</button>
      </div>

      <div class="card">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-lg font-semibold">จัดการตารางกำหนดการ</h2>
        </div>
        <div class="overflow-x-auto">
          <table id="admin-session-table" class="table">
            <thead>
              <tr>
                <th>เวลาเริ่ม</th>
                <th>เวลาจบ</th>
                <th>หัวข้อ</th>
                <th>ผู้นำเสนอ</th>
                <th>สถานะ</th>
                <th>Current?</th>
                <th>เครื่องมือ</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>

      <div class="card space-y-3">
        <h3 class="text-base font-semibold">เพิ่ม / แก้ไข Session</h3>
        <form id="session-form" class="space-y-3" autocomplete="off">
          <input type="hidden" name="id" value="">
          <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div>
              <label class="block text-sm text-slate-600">หัวข้อ</label>
              <input class="btn w-full" name="topic" required>
            </div>
            <div>
              <label class="block text-sm text-slate-600">ผู้นำเสนอ</label>
              <input class="btn w-full" name="speaker">
            </div>
            <div>
              <label class="block text-sm text-slate-600">เริ่ม</label>
              <input class="btn w-full" type="datetime-local" name="start_time" required>
            </div>
            <div>
              <label class="block text-sm text-slate-600">จบ</label>
              <input class="btn w-full" type="datetime-local" name="end_time" required>
            </div>
            <div>
              <label class="block text-sm text-slate-600">สถานะ</label>
              <select class="btn w-full" name="status">
                <option value="upcoming">upcoming</option>
                <option value="live">live</option>
                <option value="done">done</option>
              </select>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <button type="submit" class="btn primary">บันทึก</button>
            <button type="button" id="reset-form" class="btn">ล้างฟอร์ม</button>
          </div>
        </form>
      </div>
    </section>
  </div>

  <!-- Edit Modal -->
  <div id="editModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="editTitle">
    <div id="editModalBackdrop" class="absolute inset-0 bg-black/50"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
      <div class="w-full max-w-2xl rounded-2xl bg-white shadow">
        <div class="flex items-center justify-between border-b p-4">
          <h2 id="editTitle" class="text-lg font-bold">แก้ไข Session</h2>
          <button id="editModalClose" class="px-2" aria-label="ปิด">✕</button>
        </div>
        <form id="sessionEdit-form" class="p-4 space-y-3" autocomplete="off">
          <input type="hidden" name="id" value="">
          <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 sm:col-span-6">
              <label class="block text-sm text-slate-600">หัวข้อ</label>
              <input class="btn w-full" name="topic" required>
            </div>
            <div class="col-span-12 sm:col-span-6">
              <label class="block text-sm text-slate-600">ผู้นำเสนอ</label>
              <input class="btn w-full" name="speaker">
            </div>
          </div>
          <div class="grid grid-cols-12 gap-4 mt-2">
            <div class="col-span-12 sm:col-span-6">
              <label class="block text-sm text-slate-600">เริ่ม</label>
              <input class="btn w-full" type="datetime-local" name="start_time" required>
            </div>
            <div class="col-span-12 sm:col-span-6">
              <label class="block text-sm text-slate-600">จบ</label>
              <input class="btn w-full" type="datetime-local" name="end_time" required>
            </div>
          </div>
          <div class="grid grid-cols-12 gap-4 mt-2">
            <div class="col-span-12 sm:col-span-6">
              <label class="block text-sm text-slate-600">สถานะ</label>
              <select class="btn w-full sm:w-3/4" name="status">
                <option value="upcoming">upcoming</option>
                <option value="live">live</option>
                <option value="done">done</option>
              </select>
            </div>
          </div>
          <div class="mt-3 flex justify-end gap-2">
            <button class="btn" type="button" id="edit-reset">ล้างฟอร์ม</button>
            <button class="btn primary" type="submit">บันทึก</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script nonce="<?= $cspNonce ?>">
    // ---------- Utilities ----------
    const $  = (q, d=document) => d.querySelector(q);
    const $$ = (q, d=document) => Array.from(d.querySelectorAll(q));
    const toLocal = s => (s||'').slice(0,16).replace(' ','T');
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;

    async function jfetch(url, opts={}){
      const r = await fetch(url, {
        headers: {
          'X-Requested-With': 'fetch',
          'X-CSRF-Token': CSRF,
        },
        ...opts,
        credentials: 'same-origin'
      });
      if(!r.ok) throw new Error('network');
      return r.json();
    }

    // ---------- Drag & Drop Room Sort ----------
    function initSort(){
      const list = $('#room-sortable');
      let dragEl = null;
      list.addEventListener('dragstart', e=>{
        const li = e.target.closest('li');
        if(!li) return;
        dragEl = li;
        e.dataTransfer.effectAllowed = 'move';
      });
      list.addEventListener('dragover', e=>{
        e.preventDefault();
        const li = e.target.closest('li');
        if(!li || li===dragEl) return;
        const rect = li.getBoundingClientRect();
        const next = (e.clientY - rect.top) / (rect.height) > .5;
        list.insertBefore(dragEl, next ? li.nextSibling : li);
      });
      $('#save-order').addEventListener('click', async ()=>{
        const ids = $$('#room-sortable li').map(li=>li.dataset.roomId);
        const body = new URLSearchParams({ ids: ids.join(','), csrf: CSRF });
        const res = await jfetch('/admin/api/reorder_rooms.php', { method:'POST', body });
        Swal.fire({ icon:'success', title: res.message||'บันทึกแล้ว' }).then(()=>location.reload());
      });
    }

    // ---------- Modal Helpers ----------
    function openEditModal(d={}){
      const f = $('#sessionEdit-form');
      f.id.value = d.id || '';
      f.topic.value = d.topic || '';
      f.speaker.value = d.speaker || '';
      f.start_time.value = toLocal(d.start_time);
      f.end_time.value = toLocal(d.end_time);
      f.status.value = d.status || 'upcoming';
      $('#editModal').classList.remove('hidden');
      setTimeout(()=>f.topic?.focus(), 0);
    }
    const closeEditModal = ()=> $('#editModal').classList.add('hidden');

    ['#editModalClose', '#editModalBackdrop'].forEach(sel=>{
      document.addEventListener('click', e=>{ if(e.target.matches(sel)) closeEditModal(); });
    });
    window.addEventListener('keydown', e=>{ if(e.key==='Escape' && !$('#editModal').classList.contains('hidden')) closeEditModal(); });

    // ---------- Sessions Table ----------
    async function loadSessions(){
      const roomId = $('#room-select').value;
      const data = await jfetch(`/public/api/room_sessions.php?room_id=${encodeURIComponent(roomId)}`);
      const tbody = $('#admin-session-table tbody');
      tbody.innerHTML = '';
      (data.sessions||[]).forEach(s=>{
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${h(s.start_time).replace('T',' ').slice(0,16)}</td>
          <td>${h(s.end_time).replace('T',' ').slice(0,16)}</td>
          <td>${h(s.topic)}</td>
          <td>${h(s.speaker||'-')}</td>
          <td><span class="px-2 py-0.5 rounded-full text-xs ${s.status==='live'?'bg-emerald-50 text-emerald-700':(s.status==='done'?'bg-slate-100 text-slate-600':'bg-amber-50 text-amber-700')}">${h(s.status)}</span></td>
          <td>${s.is_current ? '<span class="inline-flex items-center text-emerald-600">✔</span>' : ''}</td>
          <td class="space-x-1">
            <button class="btn" data-action="edit" data-id="${s.id}">แก้ไข</button>
            <button class="btn" data-action="current" data-id="${s.id}">ตั้งเป็นกำลังบรรยาย</button>
            <button class="btn" data-action="delete" data-id="${s.id}">ลบ</button>
          </td>`;
        tbody.appendChild(tr);
      });
    }

    // escape helper for template literals
    function h(v){
      return String(v==null?'':v)
        .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
        .replaceAll('"','&quot;').replaceAll("'",'&#039;');
    }

    async function setCurrent(id){
      const ok = await Swal.fire({
        title:'ยืนยันการทำงาน?', text:'เปลี่ยนเป็น "กำลังบรรยาย"', icon:'warning',
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
        const s = id && id.trim();
        if(!s) return;
        if(action==='edit'){
          // Pull the current row data quickly from the DOM (or re-fetch from API if needed)
          const row = btn.closest('tr');
          const d = {
            id: id,
            start_time: row.children[0].textContent.trim().replace(' ','T')+':00',
            end_time: row.children[1].textContent.trim().replace(' ','T')+':00',
            topic: row.children[2].textContent.trim(),
            speaker: row.children[3].textContent.trim().replace(/^-$|^\s*$/,'')||'',
            status: row.children[4].textContent.trim()
          };
          openEditModal(d);
        } else if(action==='current'){
          setCurrent(id);
        } else if(action==='delete'){
          deleteSession(id);
        }
      });
    }

    // ---------- Upsert Forms (Create + Edit) ----------
    function handleForm(formSelector){
      const form = $(formSelector);
      form.addEventListener('submit', async e=>{
        e.preventDefault();
        const room_id = $('#room-select').value;
        const fd = new FormData(form);
        fd.append('room_id', room_id);
        fd.append('csrf', CSRF);
        const res = await jfetch('/admin/api/upsert_session.php', { method:'POST', body: fd });
        await Swal.fire({ icon:'success', title: res.message||'บันทึกแล้ว' });
        form.reset();
        if(form.id && form.id.value) form.id.value = '';
        if(formSelector==='#sessionEdit-form') closeEditModal();
        loadSessions();
      });
    }

    function initForms(){
      handleForm('#session-form');
      handleForm('#sessionEdit-form');
      $('#reset-form').addEventListener('click', ()=>{ const f=$('#session-form'); f.reset(); f.id.value=''; });
      $('#edit-reset').addEventListener('click', ()=>{ const f=$('#sessionEdit-form'); f.reset(); f.id.value=''; });
    }

    // ---------- Boot ----------
    initSort();
    bindTableActions();
    $('#reload-sessions').addEventListener('click', loadSessions);
    $('#room-select').addEventListener('change', loadSessions);
    initForms();
    loadSessions();
  </script>
</body>
</html>
