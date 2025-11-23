#!/bin/bash

cd /home/user/cmis.marketing.limited

count=0
for file in $(find app -name "*.php" -type f); do
    if php -l "$file" 2>&1 | grep -q "Parse error"; then
        echo "$file"
        count=$((count + 1))
        if [ $count -ge 50 ]; then
            break
        fi
    fi
done

echo "Total files with errors found: $count"
