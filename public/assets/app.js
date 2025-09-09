/* global window, document */
window.MeetingApp = (function () {
  // ---------- helpers ----------
  async function fetchJSON(url, opts) {
    const res = await fetch(url, { cache: "no-store", ...opts });
    if (!res.ok) throw new Error("Network error");
    return res.json();
  }

  const pad = (n) => String(n).padStart(2, "0");

  function parseDT(dt) {
    if (!dt) return new Date("invalid");
    // รองรับ 'YYYY-MM-DD HH:MM:SS' และ 'YYYY-MM-DDTHH:MM:SS'
    return new Date(String(dt).replace(" ", "T"));
  }

  function fmtTime(dt) {
    const d = parseDT(dt);
    if (isNaN(d)) return "--:--";
    return `${pad(d.getHours())}:${pad(d.getMinutes())}`;
  }

  function esc(s) {
    return String(s ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function computeStatus(item) {
    // รองรับทั้งกรณีส่ง status มาแล้ว หรือคำนวณจากเวลา
    if (item && typeof item.status === "string") {
      const s = item.status.toLowerCase();
      if (s === "live" || s === "upcoming" || s === "done") return s;
    }
    if (!item) return "upcoming";
    if (String(item.is_current) === "1") return "live";
    const now = new Date();
    const st = parseDT(item.start_time);
    const en = parseDT(item.end_time);
    if (!isNaN(st) && !isNaN(en)) {
      if (st <= now && en >= now) return "live";
      if (en < now) return "done";
      if (st > now) return "upcoming";
    }
    return "upcoming";
  }

  function statusBadge(status) {
    const s = computeStatus({ status });
    const cls =
      s === "live"
        ? "badge badge--live"
        : s === "done"
        ? "badge badge--done"
        : "badge badge--upc";
    const label = s === "live" ? "LIVE" : s === "done" ? "Done" : "Upcoming";
    return `<span class="${cls}">${label}</span>`;
  }

  function applyMarquee(el) {
    if (!el) return;
    el.classList.remove("marquee");
    const clip = el.parentElement;
    if (clip && el.scrollWidth > clip.clientWidth + 2) {
      el.classList.add("marquee");
    }
  }

  function setBadgePill(el, status) {
    if (!el) return;
    el.classList.remove("badge-live", "badge-next", "badge-done");
    if (status === "live") el.classList.add("badge-live");
    else if (status === "upcoming") el.classList.add("badge-next");
    else el.classList.add("badge-done");
  }

  // ---------- PUBLIC INDEX (cards) ----------
  async function updatePublicCards() {
    const data = await fetchJSON("/public/api/status.php");
    const byId = new Map();
    (data.rooms || []).forEach((r) => byId.set(Number(r.room_id), r));

    document.querySelectorAll("[data-room-id]").forEach((card) => {
      const id = Number(card.getAttribute("data-room-id"));
      const r = byId.get(id);
      const liveBadge = card.querySelector(".sheet-head .badge");
      const nextBadge = card.querySelector(".sheet-next .badge");
      const liveTrack = card.querySelector(".live-title");
      const nextTrack = card.querySelector(".next-title");
      const panelBody = card.querySelector(".sheet-body .sheet-content");

      // reset text ก่อนทุกครั้ง
      if (liveTrack) liveTrack.textContent = "—";
      if (nextTrack) nextTrack.textContent = "—";

      // LIVE
      if (r && r.current) {
        setBadgePill(liveBadge, "live");
        if (liveTrack) {
          liveTrack.textContent = r.current.topic || "—";
          applyMarquee(liveTrack);
        }
        if (panelBody) {
          panelBody.innerHTML = `
            <div class="current">
              <div class="title">${esc(r.current.topic || "")}</div>
              <p class="text-slate-600">${
                r.current.speaker ? "วิทยากร: " + esc(r.current.speaker) : ""
              }</p>
              <p class="text-slate-500">เวลา: ${fmtTime(
                r.current.start_time
              )} - ${fmtTime(r.current.end_time)}</p>
            </div>`;
        }
      } else {
        setBadgePill(liveBadge, "done");
        if (panelBody) {
          panelBody.innerHTML =
            '<div class="current text-slate-500">ยังไม่เริ่ม หรือไม่มีหัวข้อที่กำลังบรรยาย</div>';
        }
      }

      // UPCOMING (สีเทา + marquee)
      if (r && r.next) {
        setBadgePill(nextBadge, "upcoming");
        if (nextTrack) {
          nextTrack.textContent = `${r.next.topic || "—"} | ${fmtTime(
            r.next.start_time
          )}-${fmtTime(r.next.end_time)}`;
          nextTrack.classList.add("next-title");
          applyMarquee(nextTrack);
        }
      } else {
        setBadgePill(nextBadge, "done");
      }
    });
  }

  // ---------- ROOM PAGE / ADMIN TABLE ----------
  async function loadRoomSessions(room_id, { returnOnly = false } = {}) {
    const data = await fetchJSON(
      `/public/api/room_sessions.php?room_id=${encodeURIComponent(room_id)}`
    );
    const list = Array.isArray(data.sessions) ? data.sessions : [];

    if (returnOnly) return list; // สำหรับผู้ที่อยากได้ข้อมูลดิบ

    const tbody = document.querySelector("#session-table tbody");
    if (!tbody) return list;

    tbody.innerHTML = "";
    if (list.length === 0) {
      tbody.innerHTML =
        '<tr><td colspan="5" style="padding:16px;color:#64748b">วันนี้ยังไม่มีรายการ</td></tr>';
      return list;
    }

    list.forEach((s) => {
      const st = computeStatus(s);
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td><strong>${fmtTime(s.start_time)} - ${fmtTime(s.end_time)}</strong></td>
        <td><div class="topic" title="${esc(s.topic)}">${esc(s.topic)}</div></td>
        <td>${esc(s.speaker) || "-"}</td>
        <td>${statusBadge(st)} ${
        String(s.is_current) === "1" || st === "live"
          ? '<strong style="color:#047857;margin-left:.25rem">กำลังบรรยาย</strong>'
          : ""
      }</td>
      `;
      tbody.appendChild(tr);
    });

    return list;
  }

  function initPublic() {
    updatePublicCards();
    // refresh ทุก 30 วิ (พอดีกับ index ใหม่)
    setInterval(updatePublicCards, 30000);
  }

  return { initPublic, loadRoomSessions, updatePublicCards };
})();
