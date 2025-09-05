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
    "connect-src 'self'; " .
    "font-src 'self'; " .
    "base-uri 'self'; form-action 'self'; frame-ancestors 'self';"
);

// ---- Helper ----
function h($s)
{
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
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

  <!-- Marquee / UI extras -->
  <style nonce="<?= $nonce ?>">
    .mobile-set {}

    @keyframes marquee {
      0% {
        transform: translateX(0%);
      }
      100% {
        transform: translateX(-50%);
      }
    }

    .animate-marquee {
      animation: marquee-left 11s linear infinite
    }

    .pause:hover .animate-marquee {
      animation-play-state: paused
    }

    .animate-marquee {
      display: inline-block;
      white-space: nowrap;
      animation: marquee 15s linear infinite;
      will-change: transform;
    }


    /* ถ้าอยากให้เอาเมาส์ชี้แล้วหยุด */
    .pause:hover .animate-marquee {
      animation-play-state: paused;
    }

    @media (prefers-reduced-motion: reduce) {
      .animate-marquee {
        animation: none !important;
        transform: none !important
      }
    }

    .container {
      max-width: 1200px;
      margin-inline: auto;
      padding: 1rem
    }

    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 1rem
    }

    .card {
      border: 1px solid #e5e7eb;
      border-radius: .75rem;
      padding: 1rem;
      background: #fff;
      box-shadow: 0 1px 2px rgba(0, 0, 0, .04)
    }

    .card-head {
      display: flex;
      align-items: center;
      gap: .5rem;
      margin-bottom: .5rem
    }

    .pill {
      width: .75rem;
      height: .75rem;
      border-radius: 999px;
      background: #16a34a;
      box-shadow: 0 0 0 3px rgba(22, 163, 74, .15)
    }

    .room-name {
      font-weight: 700;
      font-size: 1.125rem
    }

    .room-loc {
      font-weight: 500;
      color: #4b5563;
      margin-left: .25rem
    }

    .skeleton {
      background: linear-gradient(100deg, #f3f4f6 40%, #e5e7eb 50%, #f3f4f6 60%);
      background-size: 200% 100%;
      animation: loading 1.2s ease-in-out infinite;
      border-radius: .5rem;
      padding: .75rem 1rem
    }

    @keyframes loading {
      0% {
        background-position: 200% 0
      }

      100% {
        background-position: -200% 0
      }
    }

    /* ปรับแถบค้นหาให้สวย/ชิดขวา */
    .search-wrap {
      width: 100%;
      max-width: 480px
    }

    .search-input {
      border: 1px solid #d1d5db
    }

    .search-btn {
      box-shadow: 0 4px 10px rgba(34, 197, 94, .25)
    }
  </style>
</head>

<body class="min-h-dvh bg-gray-50 text-gray-900">

  <!-- Back To Top -->
  <button id="backToTop"
    class="fixed bottom-6 right-6 bg-green-600 text-white px-4 py-2 rounded-full shadow-lg hover:bg-green-700 transition duration-300 hidden"
    type="button" aria-label="กลับขึ้นบนสุด">↑ Top</button>

  <!-- HEADER: โลโก้ซ้าย / ชื่อกลาง (ซ่อนในจอเล็ก) / ค้นหาชิดขวา -->
  <header class="px-4 py-4 bg-white border-b border-gray-200">
    <div class="container">
      <div class="flex flex-col md:flex-row items-center gap-4">
        <!-- ซ้าย: โลโก้ -->
        <div class="flex items-center gap-3">
          <img src="/public/assets/picture/A.png" alt="โลโก้หน่วยงาน" class="h-14 w-auto" loading="lazy" decoding="async" onerror="this.hidden=true">
          <img src="/public/assets/picture/MOPH202503.png" alt="ตรากระทรวงสาธารณสุข" class="h-14 w-auto" loading="lazy" decoding="async" onerror="this.hidden=true">
        </div>

        <!-- ตรงกลาง: ชื่ออีเวนต์ (ย่อบนมือถือ) -->
        <div class="flex-1 text-center hidden md:block">
          <h1 class="text-2xl font-bold text-[#121111]">งานประชุมวิชาการกระทรวงสาธารณสุข ประจำปี 2568</h1>
          <p class="text-gray-700">ยกระดับการสาธารณสุขไทยสุขภาพแข็งแรงทุกวัย เศรษฐกิจสุขภาพไทยมั่นคง</p>
        </div>

        <!-- ขวา: แถบค้นหา -->
        <div class="search-wrap ml-auto mobile-set">
          <form action="/public/search.php" method="get" class="flex items-stretch gap-2">
            <label for="q" class="sr-only">ค้นหาห้อง/หัวข้อ</label>
            <input
              id="q"
              type="text"
              name="q"
              placeholder="ค้นหาห้องประชุมหรือหัวข้อ..."
              class="search-input w-full rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-600"
              autocomplete="off">
            <button
              type="submit"
              class="search-btn inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-green-600 text-white font-semibold hover:bg-green-700 transition">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <circle cx="11" cy="11" r="7" stroke-width="1.8"></circle>
                <path d="M20 20l-3.5-3.5" stroke-width="1.8" stroke-linecap="round"></path>
              </svg>
              ค้นหา
            </button>
          </form>
        </div>
      </div>

      <!-- ชื่ออีเวนต์สำหรับมือถือ (แยกบรรทัด) -->
      <div class="mt-4 text-center md:hidden">
        <h1 class="text-xl font-bold text-[#121111]">งานประชุมวิชาการกระทรวงสาธารณสุข 2568</h1>
        <p class="text-gray-700 text-sm">ยกระดับการสาธารณสุขไทยสุขภาพแข็งแรงทุกวัย เศรษฐกิจสุขภาพไทยมั่นคง</p>
        <noscript>
          <p class="text-red-600 mt-2">ต้องเปิดใช้งาน JavaScript เพื่อแสดงสถานะปัจจุบัน</p>
        </noscript>
      </div>
    </div>
  </header>

  <main class="container">
    <div id="rooms" class="grid" role="list">
      <?php foreach ($rooms as $r): ?>
        <section class="card flex-col h-full" role="listitem" data-room-id="<?= h($r['id']) ?>">
          <div class="card-head">
            <i class="pill" aria-hidden="true"></i>
            <h2 class="room-name">
              <?= h($r['name']) ?>
              <?php if (!empty($r['location'])): ?>
                <span class="room-loc">(<?= h($r['location']) ?>)</span>
              <?php endif; ?>
            </h2>
          </div>

          <div class="card-body flex-1">
            <div class="current " aria-live="polite">กำลังโหลด…</div>
          </div>

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

  <!-- Inline JS (nonce) -->
  <script nonce="<?= $nonce ?>">
    // init
    window.addEventListener('load', () => {
      try {
        window.MeetingApp?.initPublic?.();
      } catch (e) {}
    });

    // Marquee for current topic
    (function() {
      const TITLE_SELECTOR = '.current h3, .current h4, .current strong, .current .title, .current .topic-title';

      function wrapAsMarquee(titleEl) {
        if (!titleEl || titleEl.dataset.marqueeWrapped) return;
        const text = (titleEl.textContent || '').trim();
        if (!text) return;
        const outer = document.createElement('div');
        outer.className = 'relative overflow-hidden whitespace-nowrap rounded bg-blue-50 px-3 py-2 pause';
        const track = document.createElement('span');
        track.className = 'animate-marquee inline-block font-semibold text-gray-800';
        track.textContent = text;
        outer.appendChild(track);
        titleEl.replaceWith(outer);
        outer.dataset.marqueeWrapped = '1';
      }

      function initialScan() {
        document.querySelectorAll(TITLE_SELECTOR).forEach(wrapAsMarquee);
      }
      const observer = new MutationObserver((ms) => {
        for (const m of ms) {
          m.addedNodes.forEach((n) => {
            if (!(n instanceof HTMLElement)) return;
            if (n.matches?.(TITLE_SELECTOR)) wrapAsMarquee(n);
            else {
              const t = n.querySelector?.(TITLE_SELECTOR);
              if (t) wrapAsMarquee(t);
            }
          });
        }
      });
      window.addEventListener('load', () => {
        initialScan();
        const root = document.getElementById('rooms') || document.body;
        observer.observe(root, {
          childList: true,
          subtree: true
        });
      });
    })();

    // Back To Top
    (function() {
      const btn = document.getElementById('backToTop');
      const onScroll = () => {
        (document.documentElement.scrollTop > 200 || document.body.scrollTop > 200) ? btn.classList.remove('hidden'): btn.classList.add('hidden')
      };
      const toTop = () => window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
      window.addEventListener('scroll', onScroll, {
        passive: true
      });
      btn.addEventListener('click', toTop);
    })();
  </script>
</body>

</html>
<?php ob_end_flush(); ?>