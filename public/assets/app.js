window.MeetingApp = (function () {
  async function fetchJSON(url, opts) {
    const res = await fetch(url, opts);
    if (!res.ok) throw new Error("Network error");
    return res.json();
  }

  function fmtTime(dt) {
    const d = new Date(dt);
    const hh = d.getHours().toString().padStart(2, "0");
    const mm = d.getMinutes().toString().padStart(2, "0");
    return `${hh}:${mm}`;
  }

  function statusBadge(status) {
    const s = (status || "").toLowerCase();
    return `<span class="badge ${s}">${s || "-"}</span>`;
  }

  async function updatePublicCards() {
    const data = await fetchJSON("/public/api/status.php");
    const map = new Map();
    (data.rooms || []).forEach((r) => map.set(r.room_id, r));

    document.querySelectorAll("[data-room-id]").forEach((card) => {
      const id = parseInt(card.getAttribute("data-room-id"));
      const r = map.get(id);
      const el = card.querySelector(".current");
      if (!r || !r.session_id) {
        el.innerHTML = "ยังไม่เริ่ม หรือไม่มีหัวข้อที่กำลังบรรยาย";
      } else {
        el.innerHTML = `
        
            <div><strong>${r.topic}</strong></div>
            <div>วิทยากร: ${r.speaker || "-"}</div>
            <div>เวลา: ${fmtTime(r.start_time)} - ${fmtTime(r.end_time)}</div>
            <div>${statusBadge(r.status)} ${
          r.is_current ? "กำลังบรรยาย" : ""
        }</div>
           
          `;
      }
    });
  }

  async function loadRoomSessions(room_id) {
    const data = await fetchJSON(
      `/public/api/room_sessions.php?room_id=${room_id}`
    );
    const tbody = document.querySelector("#session-table tbody");
    tbody.innerHTML = "";
    (data.sessions || []).forEach((s) => {
      const tr = document.createElement("tr");
      tr.innerHTML = `
          <td>${fmtTime(s.start_time)} - ${fmtTime(s.end_time)}</td>
          <td>${s.topic}</td>
          <td>${s.speaker || "-"}</td>
          <td>${statusBadge(s.status)} ${
        s.is_current ? "<strong>กำลังบรรยาย</strong>" : ""
      }</td>
        `;
      tbody.appendChild(tr);
    });
  }

  function initPublic() {
    updatePublicCards();
    setInterval(updatePublicCards, 10000);
  }

  return { initPublic, loadRoomSessions };
})();
