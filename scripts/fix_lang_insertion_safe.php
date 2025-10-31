<?php
$platforms = ['facebook', 'linkedin', 'tiktok'];
foreach ($platforms as $platform) {
    $file = "/httpdocs/app/Console/Commands/" . ucfirst($platform) . "ApiCommand.php";
    if (!file_exists($file)) {
        echo "[SKIP] {$platform} file not found.\n";
        continue;
    }

    $content = file_get_contents($file);
    if (strpos($content, '$lang = $this->option(') !== false) {
        echo "[OK] {$platform} already has language logic.\n";
        continue;
    }

    $lines = explode("\n", $content);
    $new = [];
    foreach ($lines as $line) {
        $new[] = $line;
        if (preg_match('/public function handle\(\)/', $line)) {
            $new[] = "    {";
            $new[] = "        $lang = $this->option('lang') ?? 'en';";
            $new[] = "        $helpFile = base_path(\"docs/social/{$platform}/help_{$lang}.md\");";
            continue;
        }
    }

    file_put_contents($file, implode("\n", $new));
    echo "[UPDATED] Inserted language logic into {$platform}.\n";
}
?>
