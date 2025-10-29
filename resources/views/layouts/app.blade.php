<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CMIS Dashboard</title>
  <style>
    body { font-family: 'Tahoma', sans-serif; margin:0; background:#fafafa; }
    nav { background:#1e293b; color:#fff; padding:10px 20px; position:sticky; top:0; z-index:1000; display:flex; justify-content:space-between; align-items:center; }
    nav a { color:#fff; text-decoration:none; margin:0 10px; transition:color 0.3s; }
    nav a:hover { color:#38bdf8; }
    nav .nav-left { display:flex; align-items:center; }
    nav .logo { font-weight:bold; font-size:20px; margin-left:20px; color:#38bdf8; }
    nav .nav-links { display:flex; gap:10px; }
    nav .user-area { display:flex; align-items:center; gap:15px; position:relative; }
    nav button { background:#ef4444; color:white; border:none; padding:6px 12px; border-radius:4px; cursor:pointer; }
    nav button:hover { background:#dc2626; }
    main { padding:20px; }

    .notification-bell { cursor:pointer; position:relative; }
    .notification-bell span { position:absolute; top:-5px; right:-5px; background:red; color:white; font-size:12px; padding:2px 6px; border-radius:50%; }
    .notification-list { display:none; position:absolute; top:35px; right:0; background:#fff; color:#000; border:1px solid #ddd; border-radius:8px; width:280px; box-shadow:0 4px 8px rgba(0,0,0,0.1); }
    .notification-list.active { display:block; }
    .notification-item { padding:10px; border-bottom:1px solid #eee; }
    .notification-item:last-child { border-bottom:none; }
    .notification-item:hover { background:#f1f5f9; }
    .notification-time { font-size:12px; color:#555; display:block; margin-top:4px; }

    .toast-container { position:fixed; bottom:20px; right:20px; z-index:2000; display:flex; flex-direction:column; gap:10px; }
    .toast { color:#fff; padding:12px 20px; border-radius:6px; min-width:250px; box-shadow:0 2px 6px rgba(0,0,0,0.2); opacity:0; transform:translateY(20px); transition:all 0.3s ease; }
    .toast.show { opacity:1; transform:translateY(0); }
    .toast.info { background:#3b82f6; }
    .toast.warning { background:#f59e0b; }
    .toast.success { background:#10b981; }
    .toast.creative { background:#8b5cf6; }
    .toast.default { background:#6b7280; }
  </style>
</head>
<body>
  <nav>
    <div class="nav-left">
      <span class="logo">⚙️ CMIS</span>
      <div class="nav-links">
        <a href="/dashboard">الرئيسية</a>
        <a href="/orgs">المؤسسات</a>
        <a href="/campaigns">الحملات</a>
        <a href="/offerings">العروض</a>
        <a href="/analytics">التحليلات</a>
        <a href="/creative">الإبداع</a>
        <a href="/channels">القنوات</a>
        <a href="/integrations">التكاملات</a>
        <a href="/ai">الذكاء الاصطناعي</a>
      </div>
    </div>

    <div class="user-area">
      <div class="notification-bell" id="bell">
        🔔<span id="notifCount">0</span>
        <div class="notification-list" id="notifList">
          <div class="notification-item">لا توجد إشعارات حالياً</div>
        </div>
      </div>
      <span>👤 المستخدم: <strong>Admin</strong></span>
      <button onclick="alert('تم تسجيل الخروج (تجريبي)')">تسجيل الخروج</button>
    </div>
  </nav>

  <main>
    @yield('content')
  </main>

  <div class="toast-container" id="toastContainer"></div>

  <script>
    const bell = document.getElementById('bell');
    const notifList = document.getElementById('notifList');
    const notifCount = document.getElementById('notifCount');
    const toastContainer = document.getElementById('toastContainer');

    bell.addEventListener('click', () => {
      notifList.classList.toggle('active');
    });

    function showToast(message) {
      const toast = document.createElement('div');
      toast.classList.add('toast');

      if (message.includes('انخفاض')) toast.classList.add('warning');
      else if (message.includes('حملة')) toast.classList.add('info');
      else if (message.includes('أصل')) toast.classList.add('creative');
      else if (message.includes('تكامل')) toast.classList.add('success');
      else toast.classList.add('default');

      toast.textContent = message;
      toastContainer.appendChild(toast);

      setTimeout(() => toast.classList.add('show'), 100);
      setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
      }, 5000);
    }

    let lastNotifications = [];

    async function fetchNotifications() {
      try {
        const res = await fetch('/notifications/latest');
        const data = await res.json();

        notifCount.textContent = data.length;
        notifList.innerHTML = '';

        if (data.length === 0) {
          notifList.innerHTML = '<div class="notification-item">لا توجد إشعارات حالياً</div>';
        } else {
          data.forEach(n => {
            const item = document.createElement('div');
            item.className = 'notification-item';
            item.innerHTML = `<strong>${n.message}</strong><span class='notification-time'>${n.time}</span>`;
            notifList.appendChild(item);
          });

          const newOnes = data.filter(n => !lastNotifications.find(o => o.message === n.message));
          newOnes.forEach(n => showToast(n.message));
          lastNotifications = data;
        }
      } catch (e) {
        console.error('فشل تحميل الإشعارات', e);
      }
    }

    fetchNotifications();
    setInterval(fetchNotifications, 60000);
  </script>
</body>
</html>