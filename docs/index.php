<?php
header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set('Asia/Riyadh');
$docsDir = __DIR__;
$changelogMd = $docsDir . '/changelog.md';
$changelogJson = $docsDir . '/changelog.json';
$diffFiles = glob($docsDir . '/../backups/changelog_diff_*.md');
$latestDiff = $diffFiles ? end($diffFiles) : null;
$branch = trim(shell_exec('cd ' . escapeshellarg($docsDir . '/..') . ' && git rev-parse --abbrev-ref HEAD'));
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>📜 سجل تغييرات CMIS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/github-dark.min.css" rel="stylesheet">
<style>
body { background: #121212; color: #e0e0e0; font-family: 'Tajawal', sans-serif; }
nav { background: #1f1f1f; }
pre { background: #1e1e1e; padding: 10px; border-radius: 5px; }
.tab-content { margin-top: 20px; }
.btn-group .btn { border-radius: 0; }
.markdown-body { direction: ltr; text-align: left; white-space: pre-wrap; }
.commit-entry { border-left: 4px solid #2196F3; padding-left: 10px; margin-bottom: 20px; }
.merge-entry { border-left: 4px solid #4CAF50; padding-left: 10px; margin-bottom: 20px; }
hr { border-color: #333; }
#download-btn { position: fixed; bottom: 20px; left: 20px; }
</style>
</head>
<body>
<nav class="navbar navbar-dark shadow-sm">
  <div class="container-fluid">
    <span class="navbar-brand">📜 CMIS Git Smart Changelog</span>
    <span class="text-muted small"><?php echo date('Y-m-d H:i:s'); ?> (Saudi Time) | فرع: <?php echo htmlspecialchars($branch); ?></span>
  </div>
</nav>
<div class="container py-4">
  <div class="btn-group w-100" role="group">
    <button class="btn btn-outline-light active" id="btn-md">Markdown</button>
    <button class="btn btn-outline-light" id="btn-json">JSON</button>
    <button class="btn btn-outline-light" id="btn-diff">Diff</button>
  </div>

  <div id="view-md" class="tab-content mt-3">
    <div id="pdf-content" class="markdown-body bg-dark p-3 rounded">
      <?php
        if (file_exists($changelogMd)) {
          $content = file_get_contents($changelogMd);
          $content = preg_replace('/### 📝/u', '<div class="commit-entry"><strong>📝 Commit:</strong>', $content);
          $content = preg_replace('/### 🔄/u', '<div class="merge-entry"><strong>🔄 Merge:</strong>', $content);
          $content = str_replace('---', '</div><hr>', $content);
          echo nl2br($content);
        } else {
          echo '<em>لا يوجد سجل بعد.</em>';
        }
      ?>
    </div>
  </div>

  <div id="view-json" class="tab-content mt-3" style="display:none;">
    <pre><code class="json"><?php echo file_exists($changelogJson) ? htmlspecialchars(file_get_contents($changelogJson)) : '{}'; ?></code></pre>
  </div>

  <div id="view-diff" class="tab-content mt-3" style="display:none;">
    <pre><code><?php echo $latestDiff ? htmlspecialchars(file_get_contents($latestDiff)) : 'لا يوجد فرق أسبوعي بعد.'; ?></code></pre>
  </div>
</div>

<button id="download-btn" class="btn btn-success">📥 تحميل PDF</button>

<script>
const branch = <?php echo json_encode(trim($branch)); ?>;
document.getElementById('btn-md').onclick = () => switchView('md');
document.getElementById('btn-json').onclick = () => switchView('json');
document.getElementById('btn-diff').onclick = () => switchView('diff');
function switchView(tab) {
  document.querySelectorAll('.tab-content').forEach(div => div.style.display = 'none');
  document.getElementById('view-' + tab).style.display = 'block';
  document.querySelectorAll('.btn').forEach(b => b.classList.remove('active'));
  document.getElementById('btn-' + tab).classList.add('active');
}
document.querySelectorAll('code.json').forEach(el => hljs.highlightElement(el));

document.getElementById('download-btn').addEventListener('click', () => {
  const element = document.getElementById('pdf-content');
  const date = new Date().toISOString().split('T')[0];
  const filename = `CMIS_Changelog_${branch || 'main'}_${date}.pdf`;
  const opt = {
    margin: 0.5,
    filename: filename,
    image: { type: 'jpeg', quality: 0.98 },
    html2canvas: { scale: 2 },
    jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
  };
  html2pdf().set(opt).from(element).save();
});
</script>
</body>
</html>
