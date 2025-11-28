#!/bin/bash

echo "==========================================="
echo "CMIS Controller i18n Cleanup Verification"
echo "==========================================="
echo ""

BASE_DIR="/home/cmis-test/public_html"

echo "1. Checking for remaining hardcoded with() messages..."
WITH_COUNT=$(grep -r --include="*.php" -E "with\(['\"][a-z]+['\"],\s*['\"][^'\"]+['\"]" "$BASE_DIR/app/Http/Controllers/" | grep -v "__(" | wc -l)
if [ $WITH_COUNT -eq 0 ]; then
    echo "   ✅ PASS: No hardcoded with() messages found"
else
    echo "   ❌ FAIL: Found $WITH_COUNT hardcoded with() messages"
fi

echo ""
echo "2. Checking for remaining hardcoded JSON messages..."
JSON_COUNT=$(grep -r --include="*.php" -E "(\['message'\]|\[\"message\"\])\s*=>\s*['\"][^'\"]*['\"]" "$BASE_DIR/app/Http/Controllers/" | grep -v "__(" | wc -l)
if [ $JSON_COUNT -eq 0 ]; then
    echo "   ✅ PASS: No hardcoded JSON messages found"
else
    echo "   ❌ FAIL: Found $JSON_COUNT hardcoded JSON messages"
fi

echo ""
echo "3. Checking for remaining hardcoded exceptions..."
EXC_COUNT=$(grep -r --include="*.php" -E "throw new [A-Za-z\\\\]+Exception\(['\"][^'\"]*['\"]" "$BASE_DIR/app/Http/Controllers/" | grep -v "__(" | wc -l)
if [ $EXC_COUNT -eq 0 ]; then
    echo "   ✅ PASS: No hardcoded exception messages found"
else
    echo "   ❌ FAIL: Found $EXC_COUNT hardcoded exception messages"
fi

echo ""
echo "4. Counting translation usage..."
I18N_COUNT=$(grep -r --include="*.php" "__(" "$BASE_DIR/app/Http/Controllers/" | wc -l)
echo "   ✅ Found $I18N_COUNT uses of __() translation helper"

echo ""
echo "5. Verifying language files..."
AR_FILES=$(find "$BASE_DIR/resources/lang/ar" -name "*.php" | wc -l)
EN_FILES=$(find "$BASE_DIR/resources/lang/en" -name "*.php" | wc -l)
echo "   ✅ Arabic language files: $AR_FILES"
echo "   ✅ English language files: $EN_FILES"

if [ $AR_FILES -eq $EN_FILES ]; then
    echo "   ✅ PASS: Arabic and English files match"
else
    echo "   ⚠️  WARNING: File count mismatch (AR: $AR_FILES, EN: $EN_FILES)"
fi

echo ""
echo "6. Summary Statistics..."
TOTAL_CONTROLLERS=$(find "$BASE_DIR/app/Http/Controllers" -name "*.php" | wc -l)
MODIFIED_CONTROLLERS=$(grep -l "__(" "$BASE_DIR/app/Http/Controllers"/**/*.php | wc -l)
echo "   Total Controllers: $TOTAL_CONTROLLERS"
echo "   Controllers using i18n: $MODIFIED_CONTROLLERS"

echo ""
echo "==========================================="
if [ $WITH_COUNT -eq 0 ] && [ $JSON_COUNT -eq 0 ] && [ $EXC_COUNT -eq 0 ]; then
    echo "✅ ALL CHECKS PASSED - 100% i18n Coverage"
else
    echo "❌ SOME CHECKS FAILED - Review needed"
fi
echo "==========================================="
