<?php
require __DIR__ . '/../app/db.php';
require __DIR__ . '/../app/auth.php';
require_login();

$pdo = db();
$rooms = $pdo->query("SELECT id, name, location, display_order FROM rooms ORDER BY display_order ASC, id ASC")->fetchAll();
?>
<!doctype html>
<html lang="th">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="/public/assets/app.css">
</head>

<body>
  <div class="container">
    <h1>Admin Dashboard</h1>
    <div>ยินดีต้อนรับ, <?= htmlspecialchars(current_admin()['name']) ?> | <a href="/admin/logout.php">ออกจากระบบ</a></div>

    <h2>จัดลำดับการแสดงผลห้อง (ลาก-วาง)</h2>
    <ul id="room-sortable" class="card" style="list-style:none;padding:0;margin:0">
      <?php foreach ($rooms as $r): ?>
        <li class="btn" draggable="true" data-room-id="<?= $r['id'] ?>" style="margin:6px 0; display:flex; justify-content:space-between;">
          <span>⇅ <?= htmlspecialchars($r['name']) ?> <small>(<?= htmlspecialchars($r['location'] ?? '-') ?>)</small></span>
          <span>order: <code><?= (int)$r['display_order'] ?></code></span>
        </li>
      <?php endforeach; ?>
    </ul>
    <button id="save-order" class="btn primary" style="margin-top:10px">บันทึกการจัดลำดับ</button>

    <hr>

    <h2>จัดการตารางกำหนดการ</h2>
    <label>เลือกห้อง:</label>
    <select id="room-select" class="btn">
      <?php foreach ($rooms as $r): ?>
        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button id="reload-sessions" class="btn">รีเฟรช</button>

    <table id="admin-session-table" class="table" style="margin-top:10px">
      <thead>
        <tr>
          <th>เวลาเริ่ม</th>
          <th>เวลาจบ</th>
          <th>หัวข้อ</th>
          <th>วิทยากร</th>
          <th>สถานะ</th>
          <th>Current?</th>
          <th>เครื่องมือ</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>

    <h3>เพิ่ม Session</h3>
    <form id="session-form" class="card">
      <input type="hidden" name="id" value="">
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px">
        <div>
          <label>หัวข้อ</label>
          <input class="btn" name="topic" required>
        </div>
        <div>
          <label>วิทยากร</label>
          <input class="btn" name="speaker">
        </div>
        <div>
          <label>เริ่ม</label>
          <input class="btn" type="datetime-local" name="start_time" required>
        </div>
        <div>
          <label>จบ</label>
          <input class="btn" type="datetime-local" name="end_time" required>
        </div>
        <div>
          <label>สถานะ</label>
          <select class="btn" name="status">
            <option value="upcoming">upcoming</option>
            <option value="live">live</option>
            <option value="done">done</option>
          </select>
        </div>
      </div>
      <div style="margin-top:10px" class="flex">
        <button
          type="submit"
          class="mr-4 flex items-center bg-blue-500 text-white gap-1 px-4 py-2 cursor-pointer text-gray-800 font-semibold tracking-widest rounded-md hover:bg-blue-400 duration-300 hover:gap-2 hover:translate-x-3">
          บันทึก
          <svg
            class="w-5 h-5"
            stroke="currentColor"
            stroke-width="1.5"
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg">
            <path
              d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"
              stroke-linejoin="round"
              stroke-linecap="round"></path>
          </svg>
        </button>

        <button class="btn" type="button" id="reset-form">ล้างฟอร์ม</button>
      </div>
    </form>
    <!-- modal หน้าแก้ไขข้อมูล  -->
    <div id="editModal" class="fixed inset-0 z-50 hidden">
      <div id="editModalBackdrop" class="absolute inset-0 bg-black/50"></div>
      <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-2xl rounded-lg bg-white shadow">
          <div class="flex items-center justify-between border-b p-4">
            <h2 class="text-lg font-bold">แก้ไข Session</h2>
            <button id="editModalClose" class="px-2">✕</button>
          </div>
          <form id="sessionEdit-form" class="p-4 space-y-3">
            <input type="hidden" name="id" value="">
            <div>
              <div class="grid grid-cols-12 gap-4">
                <div class="col-span-6">
                  <label>หัวข้อ</label>
                  <input class="btn" name="topic" required>
                </div>
                <div class="col-span-6">
                  <label>วิทยากร</label>
                  <input class="btn" name="speaker">
                </div>
              </div>
              <div class="grid grid-cols-12 gap-4 mt-4">
                <div class="col-span-6">
                  <label>เริ่ม</label>
                  <input class="btn" type="datetime-local" name="start_time" required>
                </div>
                <div class="col-span-6">
                  <label>จบ</label>
                  <input class="btn" type="datetime-local" name="end_time" required>
                </div>
              </div>
              <div class="grid grid-cols-12 gap-4 mt-4">
                <div class="col-span-6">
                  <label>สถานะ</label>
                  <select class="btn" style="width: 70%;" name="status">
                    <option value="upcoming">upcoming</option>
                    <option value="live">live</option>
                    <option value="done">done</option>
                  </select>
                </div>
              </div>
              <div class="mt-2 flex justify-end gap-2">
                <button class="btn" type="button" id="reset-form">ล้างฟอร์ม</button>
                <button class="btn primary" type="submit">บันทึก</button>
              </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    const $ = (q, d = document) => d.querySelector(q);
    const $$ = (q, d = document) => Array.from(d.querySelectorAll(q));
    const toLocal = s => (s || '').slice(0, 16).replace(' ', 'T');

    async function jfetch(url, opts) {
      const r = await fetch(url, {
        headers: {
          'X-Requested-With': 'fetch'
        },
        ...opts
      });
      if (!r.ok) throw new Error('network');
      return r.json();
    }

    function openEditModal(d = {}) {
      const f = $('#sessionEdit-form');
      // เติมค่า
      f.id.value = d.id || '';
      f.topic.value = d.topic || '';
      f.speaker.value = d.speaker || '';
      f.start_time.value = toLocal(d.start_time);
      f.end_time.value = toLocal(d.end_time);
      f.status.value = d.status || 'upcoming';
      // โชว์โมดัล
      $('#editModal').classList.remove('hidden');
      setTimeout(() => f.topic?.focus(), 0);
    }

    const closeEditModal = () => $('#editModal').classList.add('hidden');

    // ปุ่มปิด/แบ็คดรอป
    ['#editModalClose', '#editModalBackdrop'].forEach(sel => {
      document.addEventListener('click', e => {
        if (e.target.matches(sel)) closeEditModal();
      });
    });
    // ปิดด้วย ESC
    window.addEventListener('keydown', e => {
      if (e.key === 'Escape' && !$('#editModal').classList.contains('hidden')) closeEditModal();
    });

    function editDataSestion(data) {
      openEditModal(data);
    }

    function initSort() {
      const list = $('#room-sortable');
      let dragEl = null;

      list.addEventListener('dragstart', e => {
        dragEl = e.target.closest('li');
        e.dataTransfer.effectAllowed = 'move';
      });
      list.addEventListener('dragover', e => {
        e.preventDefault();
        const li = e.target.closest('li');
        if (!li || li === dragEl) return;
        const rect = li.getBoundingClientRect();
        const next = (e.clientY - rect.top) / (rect.bottom - rect.top) > .5;
        list.insertBefore(dragEl, next ? li.nextSibling : li);
      });
      $('#save-order').addEventListener('click', async () => {
        const ids = $$('#room-sortable li').map(li => li.dataset.roomId);
        const res = await jfetch('/admin/api/reorder_rooms.php', {
          method: 'POST',
          body: new URLSearchParams({
            ids: ids.join(',')
          })
        });
        alert(res.message || 'บันทึกแล้ว');
        location.reload();
      });
    }

    async function loadSessions() {
      const roomId = $('#room-select').value;
      const data = await jfetch(`/public/api/room_sessions.php?room_id=${roomId}`);
      const tbody = $('#admin-session-table tbody');
      tbody.innerHTML = '';
      (data.sessions || []).forEach(s => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
      <td>${s.start_time.replace('T',' ').slice(0,16)}</td>
      <td>${s.end_time.replace('T',' ').slice(0,16)}</td>
      <td>${s.topic}</td>
      <td>${s.speaker||'-'}</td>
      <td>${s.status}</td>
      <td>${s.is_current ? `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-green-500 inline-block" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 00-1.414 0L8 12.586 4.707 9.293a1 1 0 00-1.414 
        1.414l4 4a1 1 0 001.414 0l8-8a1 1 0 000-1.414z" clip-rule="evenodd" />
      </svg>
    ` : ''}</td>
      <td>
        <button class="btn" onclick='editDataSestion(${JSON.stringify(s)})'>แก้ไข</button>
        <button class="btn" onclick="currentData(${s.id})">ตั้งเป็นกำลังบรรยาย</button>
        <button class="btn" onclick="deletsesionmeet(${s.id})">ลบ</button>
      </td>`;
        tbody.appendChild(tr);
      });

      // tbody.addEventListener('click', async e => {
      //   console.log('SDFOFKPFSK');
      //   const id = e.target.dataset.edit || e.target.dataset.current || e.target.dataset.del;
      //   if (!id) return;
      //   if (e.target.dataset.edit) {
      //     // เติมฟอร์ม
      //     const ss = (data.sessions || []).find(x => x.id == id);
      //     const f = $('#session-form');
      //     f.id.value = ss.id;
      //     f.topic.value = ss.topic;
      //     f.speaker.value = ss.speaker || '';
      //     f.start_time.value = ss.start_time.slice(0, 16).replace(' ', 'T');
      //     f.end_time.value = ss.end_time.slice(0, 16).replace(' ', 'T');
      //     f.status.value = ss.status;
      //   }
      // });
    }

    async function currentData(data) {
      const id = data
      const result = await Swal.fire({
        title: 'ยืนยันการทำงาน?',
        text: 'เปลี่ยนเป็น "กำลังบรรยาย"',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ใช่',
        cancelButtonText: 'ยกเลิก'
      });

      if (result.isConfirmed) {
        const res = await jfetch('/admin/api/set_active.php', {
          method: 'POST',
          body: new URLSearchParams({
            id,
            room_id: $('#room-select').value
          })
        });
        loadSessions();
        Swal.fire({
          icon: 'success',
          title: 'เปลี่ยนแปลงข้อมูลสำเร็จ',
          text: res.message || 'ข้อมูลถูกตั้งค่าแล้ว',
          confirmButtonText: 'ตกลง'
        });
      }
    }


    async function deletsesionmeet(data) {
      const id = data
      const result = await Swal.fire({
        title: 'ยืนยันการทำงาน?',
        text: 'คุณต้องการดำเนินการลบข้อมูลใช่หรือไม่',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'ใช่',
        cancelButtonText: 'ไม่'
      });

      if (result.isConfirmed) {
        const res = await jfetch('/admin/api/delete_session.php', {
          method: 'POST',
          body: new URLSearchParams({
            id
          })
        })
        loadSessions();
        Swal.fire({
          icon: 'success',
          title: 'ลบข้อมูลสำเร็จ',
          text: res.message || 'ข้อมูลถูกลบแล้ว',
          confirmButtonText: 'ตกลง'
        });
      }
    }

    function handleFormSubmit(formSelector) {
      const form = $(formSelector);
      form.addEventListener('submit', async e => {
        e.preventDefault();
        const room_id = $('#room-select').value;
        const fd = new FormData(e.target);
        fd.append('room_id', room_id);
        const res = await jfetch('/admin/api/upsert_session.php', {
          method: 'POST',
          body: fd
        });
        Swal.fire({
          icon: 'success',
          title: res.message || 'บันทึกแล้ว',
          confirmButtonText: 'ตกลง'
        }).then(() => {
          e.target.reset();
          closeEditModal();
          loadSessions();
        });
      });

      $('#reset-form').addEventListener('click', e => {
        form.reset();
        form.id.value = '';
      });
    }
    function initEditForm() {
      handleFormSubmit('#sessionEdit-form');
    }
    function initForm() {
      handleFormSubmit('#session-form');
    }


    // function initEditForm() {
    //   $('#sessionEdit-form').addEventListener('submit', async e => {
    //     e.preventDefault();
    //     const room_id = $('#room-select').value;
    //     const fd = new FormData(e.target);
    //     fd.append('room_id', room_id);
    //     const res = await jfetch('/admin/api/upsert_session.php', {
    //       method: 'POST',
    //       body: fd
    //     });
    //     alert(res.message || 'บันทึกแล้ว');
    //     e.target.reset();
    //     closeEditModal();
    //     loadSessions();
    //   });
    //   $('#reset-form').addEventListener('click', e => {
    //     $('#sessionEdit-form').reset();
    //     $('#sessionEdit-form').id.value = '';
    //   });
    // }

    // function initForm() {
    //   $('#session-form').addEventListener('submit', async e => {
    //     e.preventDefault();
    //     const room_id = $('#room-select').value;
    //     const fd = new FormData(e.target);
    //     fd.append('room_id', room_id);
    //     const res = await jfetch('/admin/api/upsert_session.php', {
    //       method: 'POST',
    //       body: fd
    //     });
    //     alert(res.message || 'บันทึกแล้ว');
    //     e.target.reset();
    //     closeEditModal();
    //     loadSessions();
    //   });
    //   $('#reset-form').addEventListener('click', e => {
    //     $('#session-form').reset();
    //     $('#session-form').id.value = '';
    //   });
    // }

    initSort();
    $('#reload-sessions').addEventListener('click', loadSessions);
    $('#room-select').addEventListener('change', loadSessions);
    initForm();
    initEditForm();
    loadSessions();
  </script>
</body>

</html>