#!/usr/bin/env php
<?php
/**
 * Convert Models to use BaseModel
 *
 * This script converts Laravel models from extending Model to extending BaseModel
 * and applies HasOrganization trait where applicable.
 *
 * Usage: php scripts/convert-models-to-basemodel.php [model-path]
 */

class ModelConverter
{
    private array $stats = [
        'total' => 0,
        'converted' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    private array $conversions = [];

    public function convert(string $filePath): bool
    {
        $this->stats['total']++;

        if (!file_exists($filePath)) {
            echo "❌ File not found: {$filePath}\n";
            $this->stats['errors']++;
            return false;
        }

        $content = file_get_contents($filePath);
        $originalContent = $content;

        // Skip if already extends BaseModel
        if (preg_match('/extends\s+BaseModel/', $content)) {
            echo "⏭️  Already using BaseModel: {$filePath}\n";
            $this->stats['skipped']++;
            return false;
        }

        // Skip if doesn't extend Model
        if (!preg_match('/class\s+\w+\s+extends\s+Model/', $content)) {
            echo "⏭️  Doesn't extend Model: {$filePath}\n";
            $this->stats['skipped']++;
            return false;
        }

        echo "🔄 Converting: {$filePath}\n";

        $changes = [];

        // Step 1: Change import from Model to BaseModel
        if (preg_match('/use\s+Illuminate\\\\Database\\\\Eloquent\\\\Model;/', $content)) {
            $content = preg_replace(
                '/use\s+Illuminate\\\\Database\\\\Eloquent\\\\Model;/',
                'use App\\Models\\BaseModel;',
                $content
            );
            $changes[] = "Changed import to BaseModel";
        }

        // Step 2: Change class extends from Model to BaseModel
        $content = preg_replace(
            '/class\s+(\w+)\s+extends\s+Model/',
            'class $1 extends BaseModel',
            $content
        );
        $changes[] = "Changed extends to BaseModel";

        // Step 3: Check if model has org_id field (needs HasOrganization trait)
        $hasOrgId = $this->hasOrgIdField($content);
        $hasOrgRelationship = preg_match('/public\s+function\s+org\(\)/', $content);

        if ($hasOrgId && !preg_match('/use\s+HasOrganization;/', $content)) {
            // Add HasOrganization trait import
            if (!preg_match('/use\s+App\\\\Models\\\\Concerns\\\\HasOrganization;/', $content)) {
                $content = preg_replace(
                    '/(namespace\s+[\w\\\\]+;)/',
                    "$1\n\nuse App\\Models\\Concerns\\HasOrganization;",
                    $content
                );
            }

            // Add trait usage in class
            $content = preg_replace(
                '/(use\s+HasFactory(?:,\s*\w+)*;)/',
                '$1' . "\n    use HasOrganization;",
                $content
            );

            $changes[] = "Added HasOrganization trait";

            // Remove duplicate org() relationship
            if ($hasOrgRelationship) {
                $content = preg_replace(
                    '/\/\*\*[\s\S]*?\*\/\s*public\s+function\s+org\(\)[^}]+\{[^}]+\}/',
                    '',
                    $content
                );
                $content = preg_replace(
                    '/public\s+function\s+org\(\)[^}]+\{[^}]+\}/',
                    '',
                    $content
                );
                $changes[] = "Removed duplicate org() relationship";
            }
        }

        // Step 4: Remove duplicate boot() method if it only generates UUID
        if (preg_match('/protected\s+static\s+function\s+boot\(\)[\s\S]*?Str::uuid\(\);[\s\S]*?}\s*}/', $content)) {
            // Check if boot() only handles UUID generation
            preg_match('/protected\s+static\s+function\s+boot\(\)([\s\S]*?^\s*}$)/m', $content, $bootMatch);
            if (isset($bootMatch[1])) {
                $bootContent = $bootMatch[1];
                // If boot only has parent::boot() and UUID generation, remove it
                if (
                    preg_match('/parent::boot\(\);/', $bootContent) &&
                    preg_match('/Str::uuid\(\)/', $bootContent) &&
                    !preg_match('/static::(updating|updated|saving|saved|deleting|deleted)/', $bootContent)
                ) {
                    $content = preg_replace(
                        '/\/\*\*[\s\S]*?\*\/\s*protected\s+static\s+function\s+boot\(\)[\s\S]*?}\s*}/m',
                        '',
                        $content
                    );
                    $content = preg_replace(
                        '/protected\s+static\s+function\s+boot\(\)[\s\S]*?^\s*}$/m',
                        '',
                        $content
                    );
                    $changes[] = "Removed duplicate boot() method";

                    // Remove Str import if no longer needed
                    if (!preg_match('/Str::/', $content)) {
                        $content = preg_replace(
                            '/use\s+Illuminate\\\\Support\\\\Str;\s*\n/',
                            '',
                            $content
                        );
                    }
                }
            }
        }

        // Step 5: Remove duplicate properties (BaseModel handles these)
        $duplicateProperties = [
            '/protected\s+\$connection\s*=\s*[\'"]pgsql[\'"];?\s*\n/',
            '/public\s+\$incrementing\s*=\s*false;?\s*\n/',
            '/protected\s+\$keyType\s*=\s*[\'"]string[\'"];?\s*\n/',
        ];

        foreach ($duplicateProperties as $pattern) {
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, '', $content);
                $changes[] = "Removed duplicate property";
            }
        }

        // Step 6: Remove HasUuids trait if present (BaseModel includes it)
        if (preg_match('/use\s+HasUuids/', $content)) {
            $content = preg_replace('/,\s*HasUuids/', '', $content);
            $content = preg_replace('/HasUuids,\s*/', '', $content);
            $content = preg_replace('/use\s+HasUuids;/', '', $content);
            $content = preg_replace('/use\s+Illuminate\\\\Database\\\\Eloquent\\\\Concerns\\\\HasUuids;\s*\n/', '', $content);
            $changes[] = "Removed HasUuids trait (included in BaseModel)";
        }

        // Step 7: Clean up extra whitespace
        $content = preg_replace('/\n{3,}/', "\n\n", $content);

        // Only write if content changed
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->conversions[] = [
                'file' => $filePath,
                'changes' => $changes,
            ];
            $this->stats['converted']++;
            echo "✅ Converted successfully\n";
            foreach ($changes as $change) {
                echo "   - {$change}\n";
            }
            return true;
        }

        $this->stats['skipped']++;
        return false;
    }

    private function hasOrgIdField(string $content): bool
    {
        // Check in fillable array
        if (preg_match('/[\'"]org_id[\'"]/', $content)) {
            return true;
        }

        // Check in casts array
        if (preg_match('/[\'"]org_id[\'"] => /', $content)) {
            return true;
        }

        return false;
    }

    public function convertDirectory(string $directory): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->convert($file->getPathname());
            }
        }
    }

    public function printStats(): void
    {
        echo "\n";
        echo "═══════════════════════════════════════════════════\n";
        echo "📊 Conversion Statistics\n";
        echo "═══════════════════════════════════════════════════\n";
        echo "Total files scanned: {$this->stats['total']}\n";
        echo "✅ Converted: {$this->stats['converted']}\n";
        echo "⏭️  Skipped: {$this->stats['skipped']}\n";
        echo "❌ Errors: {$this->stats['errors']}\n";
        echo "═══════════════════════════════════════════════════\n";

        if (!empty($this->conversions)) {
            echo "\n📝 Detailed Conversion Report:\n\n";
            foreach ($this->conversions as $conversion) {
                echo "File: {$conversion['file']}\n";
                foreach ($conversion['changes'] as $change) {
                    echo "  - {$change}\n";
                }
                echo "\n";
            }
        }
    }
}

// Main execution
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

$basePath = dirname(__DIR__);
$modelsPath = $basePath . '/app/Models';

$targetPath = $argv[1] ?? null;

$converter = new ModelConverter();

if ($targetPath) {
    $fullPath = $basePath . '/' . ltrim($targetPath, '/');
    if (is_dir($fullPath)) {
        echo "Converting all models in directory: {$fullPath}\n\n";
        $converter->convertDirectory($fullPath);
    } elseif (is_file($fullPath)) {
        echo "Converting single model: {$fullPath}\n\n";
        $converter->convert($fullPath);
    } else {
        die("Error: Path not found: {$fullPath}\n");
    }
} else {
    echo "Converting all models in: {$modelsPath}\n\n";
    $converter->convertDirectory($modelsPath);
}

$converter->printStats();

echo "\n✨ Conversion complete!\n";
