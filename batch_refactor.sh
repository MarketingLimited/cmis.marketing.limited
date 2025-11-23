#!/bin/bash

# Batch Refactor Script for API Response Standardization
# This script systematically refactors controllers to use ApiResponse trait methods

CONTROLLERS_FILE="/tmp/controllers_to_fix.txt"
REFACTORED_COUNT=0
FAILED_COUNT=0
LOG_FILE="/tmp/refactor_log.txt"

echo "API Response Refactoring Batch Script" > "$LOG_FILE"
echo "Started: $(date)" >> "$LOG_FILE"
echo "========================================" >> "$LOG_FILE"

# Read controllers from file
while IFS= read -r controller; do
    if [ ! -f "$controller" ]; then
        echo "SKIP: File not found: $controller" >> "$LOG_FILE"
        continue
    fi

    # Check if already refactored (no response()->json calls)
    if ! grep -q "response()->json" "$controller"; then
        echo "ALREADY DONE: $(basename $controller)" >> "$LOG_FILE"
        continue
    fi

    echo "Processing: $(basename $controller)" >> "$LOG_FILE"

    # Create backup
    cp "$controller" "${controller}.bak"

    # Perform sed-based replacements for common patterns
    sed -i.tmp \
        -e 's/return response()->json(\[\s*'"'"'success'"'"'\s*=>\s*true,\s*'"'"'message'"'"'\s*=>\s*\([^,]*\)\s*\],\s*200);/return $this->deleted(\1);/g' \
        -e 's/return response()->json(\[\s*'"'"'error'"'"'\s*=>\s*\([^,]*\)\s*\],\s*404);/return $this->notFound(\1);/g' \
        -e 's/return response()->json(\[\s*\$\([^)]*\)\s*\]);/return $this->success($\1, '"'"'Data retrieved successfully'"'"');/g' \
        "$controller"

    # Check if changes were made
    if cmp -s "$controller" "${controller}.bak"; then
        echo "  No changes made" >> "$LOG_FILE"
        rm "${controller}.bak" "${controller}.tmp" 2>/dev/null
    else
        echo "  ✓ Refactored" >> "$LOG_FILE"
        ((REFACTORED_COUNT++))
        rm "${controller}.bak" "${controller}.tmp" 2>/dev/null
    fi

done < "$CONTROLLERS_FILE"

echo "========================================" >> "$LOG_FILE"
echo "Completed: $(date)" >> "$LOG_FILE"
echo "Refactored: $REFACTORED_COUNT controllers" >> "$LOG_FILE"
echo "Failed: $FAILED_COUNT controllers" >> "$LOG_FILE"

cat "$LOG_FILE"
