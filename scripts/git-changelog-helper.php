<?php
date_default_timezone_set('Asia/Riyadh');

$docsDir = '/httpdocs/docs';
$backupDir = '/httpdocs/backups';
if (!is_dir($backupDir)) mkdir($backupDir, 0775, true);

$type = $argv[1] ?? 'commit';
$id = trim(shell_exec('git rev-parse --short HEAD'));
$user = trim(shell_exec('git config user.name'));
$branch = trim(shell_exec('git rev-parse --abbrev-ref HEAD'));
$message = trim(shell_exec('git log -1 --pretty=%B'));
$time = date('Y-m-d H:i:s O');
$files = array_filter(explode(PHP_EOL, trim(shell_exec('git diff --name-only HEAD~1 HEAD'))));

$entry = [
  'type' => $type,
  'id' => $id,
  'branch' => $branch,
  'user' => $user ?: 'system',
  'datetime' => $time,
  'files' => $files,
  'message' => $message
];

$mdEntry = "### " . ($type === 'merge' ? '🔄' : '📝') . " [{$time}] " . ucfirst($type) . " Log\n" .
           "**Commit ID:** {$id}\n" .
           "**Branch:** {$branch}\n" .
           "**Committed by:** {$user}\n" .
           "**Modified Files:**\n" . implode("\n", array_map(fn($f) => "- {$f}", $files)) . "\n" .
           "**Message:** {$message}\n---\n";

$mdFile = "$docsDir/changelog.md";
$jsonFile = "$docsDir/changelog.json";

$oldMd = file_exists($mdFile) ? file_get_contents($mdFile) : '';
file_put_contents($mdFile, "$mdEntry\n$oldMd");

$oldJson = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];
array_unshift($oldJson, $entry);
file_put_contents($jsonFile, json_encode($oldJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

if (date('w') == 5) {
  $date = date('Y-m-d');
  copy($mdFile, "$backupDir/changelog_backup_{$date}.md");
  copy($jsonFile, "$backupDir/changelog_backup_{$date}.json");
  $backups = glob("$backupDir/changelog_backup_*.md");
  sort($backups);
  if (count($backups) >= 2) {
    $last = $backups[count($backups)-2];
    $diff = shell_exec("git diff --no-index -- $last $backupDir/changelog_backup_{$date}.md");
    file_put_contents("$backupDir/changelog_diff_{$date}.md", "## 🔍 Weekly Changelog Diff ({$date})\n$diff");
  }
}

echo "✅ " . ucfirst($type) . " {$id} recorded successfully on branch {$branch} (Saudi Time).\n";
?>
