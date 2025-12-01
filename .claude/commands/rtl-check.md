---
description: Check RTL/LTR layout compliance using browser tests
---

Check RTL (Arabic) and LTR (English) layout compliance for CMIS:

## Step 1: Quick Visual Check

```bash
echo "=== RTL/LTR Layout Check ==="

# Check if browser test scripts exist
test -f scripts/browser-tests/mobile-responsive-comprehensive.js && echo "✅ Mobile test available" || echo "❌ Mobile test not found"
test -f scripts/browser-tests/cross-browser-test.js && echo "✅ Cross-browser test available" || echo "❌ Cross-browser test not found"
test -f test-bilingual-comprehensive.cjs && echo "✅ Bilingual test available" || echo "❌ Bilingual test not found"
```

## Step 2: Run Mobile Responsive Test (Quick Mode)

```bash
echo "=== Running Mobile Responsive Test (Arabic + English) ==="
node scripts/browser-tests/mobile-responsive-comprehensive.js --quick 2>&1
```

This test checks:
- Horizontal overflow issues
- Touch target sizes (44x44px minimum)
- Font sizes (12px minimum)
- Viewport meta tag
- RTL/LTR direction consistency

## Step 3: Run Bilingual Test

```bash
echo "=== Running Bilingual Test ==="
node test-bilingual-comprehensive.cjs 2>&1 | head -100
```

This test checks:
- All pages render in Arabic (RTL)
- All pages render in English (LTR)
- Translation completeness
- Layout consistency between languages

## Step 4: Check CSS for RTL Issues

```bash
echo "=== CSS RTL Compliance ==="

# Count directional vs logical properties
echo "Physical properties (should migrate to logical):"
grep -r -c -E "(margin-left|margin-right|padding-left|padding-right|text-align:\s*(left|right))" resources/css/ public/css/ 2>/dev/null | grep -v ":0$"

echo ""
echo "Logical properties (correct):"
grep -r -c -E "(margin-inline|padding-inline|text-align:\s*(start|end))" resources/css/ public/css/ 2>/dev/null | grep -v ":0$"
```

## Step 5: Check Tailwind Classes

```bash
echo "=== Tailwind RTL Compliance ==="

# Count physical Tailwind classes in views
echo "Physical classes found (should use logical):"
grep -r -o -E "(ml-|mr-|pl-|pr-|text-left|text-right)[a-z0-9-]*" resources/views/ 2>/dev/null | sort | uniq -c | sort -rn | head -10

echo ""
echo "Logical classes found (correct):"
grep -r -o -E "(ms-|me-|ps-|pe-|text-start|text-end)[a-z0-9-]*" resources/views/ 2>/dev/null | sort | uniq -c | sort -rn | head -10
```

## Step 6: Screenshot Comparison

If tests generated screenshots, compare them:

```bash
echo "=== Screenshot Analysis ==="

# Check if screenshots exist
if [ -d "test-results/mobile-responsive/screenshots" ]; then
    echo "Screenshots available at: test-results/mobile-responsive/screenshots/"
    ls -la test-results/mobile-responsive/screenshots/ | head -10
else
    echo "No screenshots found. Run tests first."
fi

if [ -d "test-results/bilingual-web/screenshots" ]; then
    echo "Bilingual screenshots at: test-results/bilingual-web/screenshots/"
    ls -la test-results/bilingual-web/screenshots/ | head -10
fi
```

## Step 7: Generate Report

```
╔══════════════════════════════════════════════════════════════╗
║                    RTL/LTR COMPLIANCE REPORT                  ║
╠══════════════════════════════════════════════════════════════╣
║ Mobile Responsive:  [✅ Pass / ❌ X issues]                   ║
║ Cross-Browser:      [✅ Pass / ❌ X issues]                   ║
║ Bilingual Test:     [✅ Pass / ❌ X issues]                   ║
╠══════════════════════════════════════════════════════════════╣
║ CSS Analysis:                                                 ║
║   Physical properties: [X occurrences]                        ║
║   Logical properties:  [X occurrences]                        ║
║   Tailwind physical:   [X classes]                            ║
║   Tailwind logical:    [X classes]                            ║
╠══════════════════════════════════════════════════════════════╣
║ Issues Found:                                                 ║
║   High Severity:   [X]                                        ║
║   Medium Severity: [X]                                        ║
║   Low Severity:    [X]                                        ║
╠══════════════════════════════════════════════════════════════╣
║ Recommendations:                                              ║
║   1. [Primary fix needed]                                     ║
║   2. [Secondary fix]                                          ║
╚══════════════════════════════════════════════════════════════╝
```

## Quick Reference: RTL-Safe Classes

| Physical (Wrong) | Logical (Correct) |
|------------------|-------------------|
| `ml-*` | `ms-*` |
| `mr-*` | `me-*` |
| `pl-*` | `ps-*` |
| `pr-*` | `pe-*` |
| `text-left` | `text-start` |
| `text-right` | `text-end` |
| `left-*` | `start-*` |
| `right-*` | `end-*` |
| `border-l-*` | `border-s-*` |
| `border-r-*` | `border-e-*` |
| `rounded-l-*` | `rounded-s-*` |
| `rounded-r-*` | `rounded-e-*` |

## Notes

- Test URL: https://cmis-test.kazaaz.com/
- Default language: Arabic (RTL)
- Secondary language: English (LTR)
- See `.claude/knowledge/BROWSER_TESTING_GUIDE.md` for full testing guide
