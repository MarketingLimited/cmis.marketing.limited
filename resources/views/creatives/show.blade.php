<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تفاصيل الأصل الإبداعي - CMIS</title>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body { font-family: 'Tajawal', sans-serif; }
  </style>
</head>
<body class="bg-white text-gray-800">
  <!-- الشريط العلوي -->
  <header class="bg-indigo-700 text-white py-4 shadow-md">
    <div class="max-w-7xl mx-auto flex justify-between items-center px-6">
      <h1 class="text-lg font-bold">🧠 نظام CMIS للذكاء التسويقي</h1>
      <span class="text-sm">تم توليد التقرير بتاريخ: 25 أكتوبر 2025</span>
    </div>
  </header>

  <!-- العنوان الرئيسي -->
  <section class="text-center my-10">
    <h2 class="text-3xl font-bold text-indigo-800">تفاصيل الأصل الإبداعي</h2>
    <p class="text-lg mt-2 text-gray-600">عرض كامل للبيانات التسويقية والفنية والتحليلية</p>
    <div class="mt-4">
      <a href="/campaigns/93192fe3-dd40-4279-bea9-7fe16aa0fe7d" class="text-indigo-700 font-semibold hover:underline">🔙 العودة إلى الحملة</a>
    </div>
  </section>

  <div class="max-w-6xl mx-auto p-6 space-y-10" x-data="{ showModal: false, activePrediction: null }">
    <!-- البيانات الأساسية -->
    <section class="bg-gray-50 rounded-2xl shadow p-6">
      <h3 class="text-2xl font-bold text-indigo-700 mb-4">البيانات العامة للأصل</h3>
      <p><strong>القناة:</strong> تيك توك</p>
      <p><strong>الهدف:</strong> التفاعل</p>
      <p><strong>النص الإعلاني:</strong> خلي عملاءك يتكلموا عنك كل يوم!</p>
      <p><strong>الحالة:</strong> قيد المراجعة</p>
      <p><strong>تاريخ الإنشاء:</strong> 23 أكتوبر 2025</p>
    </section>

    <!-- التحليل والأداء -->
    <section class="bg-gray-50 rounded-2xl shadow p-6">
      <h3 class="text-2xl font-bold text-indigo-700 mb-4">تحليل الأداء</h3>
      <canvas id="performanceChart" class="w-full h-64"></canvas>
    </section>

    <!-- التنبؤات الذكية -->
    <section class="bg-gray-50 rounded-2xl shadow p-6" x-data>
      <h3 class="text-2xl font-bold text-indigo-700 mb-4">التنبؤات الذكية</h3>
      <div class="grid md:grid-cols-3 gap-4">
        <template x-for="prediction in [
          {color:'green',label:'نجاح مرتفع',score:88,confidence:91,advice:'التصميم الحيوي والنغمة النشطة يجذبان المستخدمين.'},
          {color:'yellow',label:'نجاح متوسط',score:65,confidence:75,advice:'تحسين الرسالة البصرية ودعوة الإجراء قد يرفع الأداء.'},
          {color:'red',label:'نجاح منخفض',score:40,confidence:60,advice:'يفضل إعادة النظر في الخطوط والتكوين البصري.'}
        ]">
          <div :class="{
              'bg-green-100 border-green-500': prediction.color === 'green',
              'bg-yellow-100 border-yellow-500': prediction.color === 'yellow',
              'bg-red-100 border-red-500': prediction.color === 'red'
            }" 
            class="border rounded-xl p-4 cursor-pointer hover:shadow-lg transition"
            @click="showModal = true; activePrediction = prediction">
            <h4 class="font-bold text-lg mb-2" x-text="prediction.label"></h4>
            <p><strong>النسبة المتوقعة:</strong> <span x-text="prediction.score + '%' "></span></p>
            <p><strong>الثقة:</strong> <span x-text="prediction.confidence + '%' "></span></p>
          </div>
        </template>
      </div>

      <!-- نافذة التفاصيل -->
      <div x-show="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-transition>
        <div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-lg">
          <h4 class="text-xl font-bold mb-3 text-indigo-700" x-text="activePrediction.label"></h4>
          <p class="mb-2"><strong>نسبة الأداء المتوقعة:</strong> <span x-text="activePrediction.score + '%' "></span></p>
          <p class="mb-2"><strong>مؤشر الثقة:</strong> <span x-text="activePrediction.confidence + '%' "></span></p>
          <p class="text-gray-700 mb-4" x-text="activePrediction.advice"></p>
          <button @click="showModal = false" class="bg-indigo-700 text-white px-4 py-2 rounded hover:bg-indigo-800">إغلاق</button>
        </div>
      </div>
    </section>
  </div>

  <!-- التذييل -->
  <footer class="bg-gray-100 text-center text-gray-600 mt-16 py-6 border-t">
    <p>© 2025 Marketing Dot Limited — جميع الحقوق محفوظة</p>
    <p>تم إعداد هذه الصفحة تلقائيًا بواسطة نظام CMIS للذكاء التسويقي</p>
  </footer>

  <script>
    const ctx = document.getElementById('performanceChart');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['النص', 'التصميم', 'المراجعة', 'النشر'],
        datasets: [{
          label: 'نسبة الاكتمال (%)',
          data: [100, 80, 60, 0],
          backgroundColor: ['#4F46E5', '#EC4899', '#3B82F6', '#A78BFA']
        }]
      },
      options: {
        scales: {
          y: { beginAtZero: true, title: { display: true, text: 'النسبة %' } },
          x: { title: { display: true, text: 'المراحل' } }
        }
      }
    });
  </script>
</body>
</html>