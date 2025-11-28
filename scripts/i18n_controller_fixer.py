#!/usr/bin/env python3
"""
CMIS Controller i18n Fixer
Systematically replaces hardcoded messages with translation keys
"""

import os
import re
import json
from pathlib import Path
from collections import defaultdict

# Base directory
BASE_DIR = Path('/home/cmis-test/public_html')
CONTROLLERS_DIR = BASE_DIR / 'app/Http/Controllers'
LANG_DIR = BASE_DIR / 'resources/lang'

# Translation key mappings by domain
TRANSLATIONS = defaultdict(dict)

# Patterns to match hardcoded strings
PATTERNS = [
    # Flash messages: with('success', 'message')
    (r"with\('(success|error|warning|info)',\s*'([^']+)'\)", 'flash'),
    # JSON responses: ['message' => 'text']
    (r"\['message'\]\s*=>\s*'([^']+)'", 'json'),
    # Exception messages: throw new Exception('text')
    (r"throw new [\\a-zA-Z]+Exception\('([^']+)'\)", 'exception'),
]

def slugify(text):
    """Convert text to snake_case key"""
    # Remove Arabic characters for key generation
    text = re.sub(r'[\u0600-\u06FF]+', '', text)
    # Convert to lowercase and replace spaces/special chars
    text = text.lower().strip()
    text = re.sub(r'[^\w\s-]', '', text)
    text = re.sub(r'[-\s]+', '_', text)
    return text[:50]  # Limit length

def detect_language(text):
    """Detect if text is Arabic or English"""
    arabic_pattern = re.compile(r'[\u0600-\u06FF]')
    return 'ar' if arabic_pattern.search(text) else 'en'

def extract_domain_from_path(filepath):
    """Extract domain name from controller path"""
    parts = Path(filepath).parts
    controllers_idx = parts.index('Controllers')

    # Get the domain from path structure
    if len(parts) > controllers_idx + 1:
        # Has subdirectory like Influencer/InfluencerController.php
        return parts[controllers_idx + 1].lower()
    else:
        # Top-level controller
        filename = Path(filepath).stem
        # Extract domain from filename (e.g., CampaignController -> campaign)
        domain = re.sub(r'Controller$', '', filename)
        return domain.lower()

def generate_translation_key(domain, message_type, text):
    """Generate a translation key"""
    base_key = slugify(text)

    # Special handling for common patterns
    if 'created successfully' in text.lower() or 'تم إضافة' in text or 'تم إنشاء' in text:
        return f'{domain}.created_success'
    elif 'updated successfully' in text.lower() or 'تم تحديث' in text:
        return f'{domain}.updated_success'
    elif 'deleted successfully' in text.lower() or 'تم حذف' in text:
        return f'{domain}.deleted_success'
    elif 'failed' in text.lower() or 'فشل' in text:
        return f'{domain}.operation_failed'
    elif 'not found' in text.lower() or 'لم يتم العثور' in text:
        return f'{domain}.not_found'
    elif 'invalid' in text.lower() or 'غير صالح' in text:
        return f'{domain}.invalid'
    else:
        return f'{domain}.{base_key}'

def scan_controller(filepath):
    """Scan a controller file for hardcoded messages"""
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    domain = extract_domain_from_path(filepath)
    messages = []

    # Pattern 1: with('type', 'message')
    for match in re.finditer(r"with\('(success|error|warning|info)',\s*'([^']+)'\)", content):
        msg_type = match.group(1)
        msg_text = match.group(2)
        lang = detect_language(msg_text)
        key = generate_translation_key(domain, msg_type, msg_text)

        messages.append({
            'original': match.group(0),
            'type': 'flash',
            'msg_type': msg_type,
            'text': msg_text,
            'lang': lang,
            'key': key,
            'replacement': f"with('{msg_type}', __('{key}'))"
        })

    # Pattern 2: ['message' => 'text']
    for match in re.finditer(r"\['message'\]\s*=>\s*'([^']+)'", content):
        msg_text = match.group(1)
        lang = detect_language(msg_text)
        key = generate_translation_key(domain, 'message', msg_text)

        messages.append({
            'original': match.group(0),
            'type': 'json',
            'text': msg_text,
            'lang': lang,
            'key': key,
            'replacement': f"['message'] => __('{key}')"
        })

    # Pattern 3: throw new Exception('text')
    for match in re.finditer(r"(throw new [\\a-zA-Z]+Exception)\('([^']+)'\)", content):
        exception_type = match.group(1)
        msg_text = match.group(2)
        lang = detect_language(msg_text)
        key = generate_translation_key(domain, 'error', msg_text)

        messages.append({
            'original': match.group(0),
            'type': 'exception',
            'text': msg_text,
            'lang': lang,
            'key': key,
            'replacement': f"{exception_type}(__('{key}'))"
        })

    return domain, messages

def main():
    """Main processing function"""
    all_messages = defaultdict(list)
    files_processed = 0
    total_messages = 0

    # Scan all controllers
    for root, dirs, files in os.walk(CONTROLLERS_DIR):
        for filename in files:
            if filename.endswith('.php'):
                filepath = os.path.join(root, filename)
                domain, messages = scan_controller(filepath)

                if messages:
                    files_processed += 1
                    total_messages += len(messages)
                    all_messages[domain].extend([{
                        'file': filepath,
                        'messages': messages
                    }])

    # Output results as JSON
    output = {
        'summary': {
            'files_processed': files_processed,
            'total_messages': total_messages,
            'domains': list(all_messages.keys())
        },
        'messages_by_domain': dict(all_messages)
    }

    output_file = BASE_DIR / 'scripts/i18n_analysis.json'
    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(output, f, ensure_ascii=False, indent=2)

    print(f"Analysis complete!")
    print(f"Files processed: {files_processed}")
    print(f"Total messages found: {total_messages}")
    print(f"Domains identified: {len(all_messages)}")
    print(f"Results saved to: {output_file}")

if __name__ == '__main__':
    main()
