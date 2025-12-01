---
description: Audit codebase for internationalization (i18n) compliance
---

Audit CMIS for internationalization compliance (Arabic/English bilingual support):

## Step 1: Find Hardcoded Text in Views

```bash
echo "=== Hardcoded Text Search ==="

# Common English words that should be translated
echo "Searching for hardcoded English text..."
grep -r -n -E "\b(Campaign|Dashboard|Save|Delete|Cancel|Submit|Edit|Create|Update|Settings|Profile|Search|Filter|Export|Import|Loading|Error|Success|Warning)\b" resources/views/ --include="*.blade.php" | grep -v "{{ __(" | grep -v "{{__(" | grep -v "@lang(" | head -30

echo ""
echo "Searching for hardcoded button/label text..."
grep -r -n -E ">(Save|Cancel|Submit|Delete|Edit|Create|Update|Close|Back|Next|Previous)<" resources/views/ --include="*.blade.php" | head -20
```

## Step 2: Find Directional CSS Issues

```bash
echo "=== Directional CSS Issues (RTL/LTR) ==="

# Find physical properties that should be logical
echo "Looking for non-RTL-safe CSS classes..."
grep -r -n -E "(ml-|mr-|pl-|pr-|text-left|text-right|left-|right-)" resources/views/ --include="*.blade.php" | head -30

echo ""
echo "These should use logical properties:"
echo "  ml-* → ms-* (margin-start)"
echo "  mr-* → me-* (margin-end)"
echo "  pl-* → ps-* (padding-start)"
echo "  pr-* → pe-* (padding-end)"
echo "  text-left → text-start"
echo "  text-right → text-end"
```

## Step 3: Check Translation Files

```bash
echo "=== Translation File Analysis ==="

# Count translation keys per language
echo "Arabic translations:"
find resources/lang/ar -name "*.php" -exec wc -l {} \; 2>/dev/null | awk '{sum+=$1} END {print "Total lines: " sum}'

echo "English translations:"
find resources/lang/en -name "*.php" -exec wc -l {} \; 2>/dev/null | awk '{sum+=$1} END {print "Total lines: " sum}'

# Check for missing translation files
echo ""
echo "Checking for translation file parity..."
for f in resources/lang/en/*.php; do
    fname=$(basename "$f")
    if [ ! -f "resources/lang/ar/$fname" ]; then
        echo "⚠️ Missing Arabic: $fname"
    fi
done
```

## Step 4: Find Missing Translation Keys

```bash
echo "=== Used Translation Keys ==="

# Extract all translation keys used in views
echo "Extracting translation keys from views..."
grep -r -h -o "__('[^']*')" resources/views/ | sort | uniq -c | sort -rn | head -20

echo ""
echo "Extracting @lang keys..."
grep -r -h -o "@lang('[^']*')" resources/views/ | sort | uniq -c | sort -rn | head -20
```

## Step 5: Check for Mixed Content

```bash
echo "=== Mixed Content Issues ==="

# Find Arabic text in code (should be in translation files)
echo "Looking for hardcoded Arabic text..."
grep -r -n -P "[\x{0600}-\x{06FF}]" resources/views/ --include="*.blade.php" | grep -v "resources/lang" | head -10

# Find concatenated strings (translation anti-pattern)
echo ""
echo "Looking for string concatenation (anti-pattern)..."
grep -r -n -E "\. *__\(|\__\([^)]+\) *\." resources/views/ --include="*.blade.php" | head -10
```

## Step 6: Generate Compliance Report

```
╔══════════════════════════════════════════════════════════════╗
║                    i18n COMPLIANCE REPORT                     ║
╠══════════════════════════════════════════════════════════════╣
║ Hardcoded Text Issues:     [X files, Y occurrences]          ║
║ Directional CSS Issues:    [X files, Y occurrences]          ║
║ Missing Translation Files: [X files]                          ║
║ Mixed Content Issues:      [X occurrences]                    ║
╠══════════════════════════════════════════════════════════════╣
║ Compliance Score: [XX%]                                       ║
║ Status: [🟢 Compliant / 🟡 Needs Work / 🔴 Critical]         ║
╠══════════════════════════════════════════════════════════════╣
║ Priority Fixes:                                               ║
║   1. [Most critical issue]                                    ║
║   2. [Second priority]                                        ║
║   3. [Third priority]                                         ║
╚══════════════════════════════════════════════════════════════╝
```

## Step 7: Quick Fixes

For each issue type, provide fix suggestions:

### Hardcoded Text Fix
```php
// Before (wrong)
<button>Save</button>

// After (correct)
<button>{{ __('common.save') }}</button>
```

### Directional CSS Fix
```html
<!-- Before (wrong) -->
<div class="ml-4 text-left">

<!-- After (correct) -->
<div class="ms-4 text-start">
```

## Notes

- CMIS supports Arabic (RTL, default) and English (LTR)
- All user-facing text MUST use translation functions
- Use logical CSS properties for RTL/LTR support
- See `.claude/knowledge/I18N_RTL_REQUIREMENTS.md` for full guidelines
