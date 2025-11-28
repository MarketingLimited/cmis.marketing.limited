#!/usr/bin/env python3
"""
CMIS Controller i18n Replacer - Phase 3
Replaces hardcoded strings with translation keys in controllers
"""

import os
import re
import json
from pathlib import Path
from collections import defaultdict

BASE_DIR = Path('/home/cmis-test/public_html')
CONTROLLERS_DIR = BASE_DIR / 'app/Http/Controllers'

# Domain mapping (same as processor)
DOMAIN_MAP = {
    'NotificationController': 'notifications',
    'ComplianceController': 'compliance',
    'WorkflowController': 'workflows',
    'OrgController': 'organizations',
    'DashboardController': 'dashboard',
    'ContactController': 'contacts',
    'BudgetController': 'budgets',
    'CreativeBriefController': 'creative_briefs',
    'ProfileController': 'profile',
    'CampaignAnalyticsController': 'analytics',
    'ContentLibraryController': 'content_library',
    'AdCreativeController': 'ad_creatives',
    'PublishingQueueController': 'publishing',
    'Influencer': 'influencers',
    'Campaign': 'campaigns',
    'ABTesting': 'ab_testing',
    'Intelligence': 'intelligence',
    'Core': 'core',
    'Auth': 'auth',
    'API': 'api',
    'Channels': 'channels',
    'Offerings': 'offerings',
    'AdPlatform': 'ad_platforms',
    'Enterprise': 'enterprise',
    'FeatureManagement': 'features',
    'OAuth': 'oauth',
    'Settings': 'settings',
    'Automation': 'automation',
    'Optimization': 'optimization',
    'Web': 'web',
}

def get_domain_from_path(filepath):
    """Extract logical domain from controller path"""
    path = Path(filepath)
    parts = path.parts
    controller_name = path.stem

    try:
        controllers_idx = parts.index('Controllers')
        if len(parts) > controllers_idx + 1:
            subdir = parts[controllers_idx + 1]
            if subdir in DOMAIN_MAP:
                return DOMAIN_MAP[subdir]
    except ValueError:
        pass

    if controller_name in DOMAIN_MAP:
        return DOMAIN_MAP[controller_name]

    base_name = re.sub(r'Controller$', '', controller_name).lower()
    return base_name

def generate_translation_key(domain, message_type, text):
    """Generate a translation key matching the lang file"""
    # Special handling for common patterns
    if 'created successfully' in text.lower() or 'تم إضافة' in text or 'تم إنشاء' in text:
        return f'{domain}.created_success'
    elif 'updated successfully' in text.lower() or 'تم تحديث' in text:
        return f'{domain}.updated_success'
    elif 'deleted successfully' in text.lower() or 'تم حذف' in text:
        return f'{domain}.deleted_success'
    elif 'applied successfully' in text.lower() or 'تم تطبيق' in text:
        return f'{domain}.applied_success'
    elif 'rejected' in text.lower() or 'تم رفض' in text:
        return f'{domain}.rejected'
    elif 'dismissed' in text.lower() or 'تم تجاهل' in text:
        return f'{domain}.dismissed'
    elif 'recorded successfully' in text.lower() or 'تم تسجيل' in text:
        return f'{domain}.recorded_success'
    elif 'training completed' in text.lower():
        return f'{domain}.training_completed'
    elif 'activated successfully' in text.lower():
        return f'{domain}.activated_success'
    elif 'deactivated successfully' in text.lower():
        return f'{domain}.deactivated_success'
    elif 'archived successfully' in text.lower():
        return f'{domain}.archived_success'
    elif 'failed' in text.lower() or 'فشل' in text:
        return f'{domain}.operation_failed'
    elif 'not found' in text.lower() or 'لم يتم العثور' in text:
        return f'{domain}.not_found'
    elif 'invalid' in text.lower() or 'غير صالح' in text:
        return f'{domain}.invalid'
    elif 'marked as read' in text.lower() or 'تم تعليم' in text and 'مقروء' in text:
        return f'{domain}.marked_read'
    elif 'unauthorized' in text.lower() or 'غير مصرح' in text:
        return f'{domain}.unauthorized'
    elif 'do not have access' in text.lower():
        return f'{domain}.access_denied'
    elif 'not configured' in text.lower():
        return f'{domain}.not_configured'
    elif 'request failed' in text.lower():
        return f'{domain}.request_failed'
    elif 'يجب اختيار' in text or 'must select' in text.lower():
        return f'{domain}.minimum_selection_required'
    elif 'unexpected' in text.lower():
        return f'{domain}.unexpected_response'
    else:
        # Generate slug from text
        slug = re.sub(r'[\u0600-\u06FF]+', '', text)
        slug = slug.lower().strip()
        slug = re.sub(r'[^\w\s-]', '', slug)
        slug = re.sub(r'[-\s]+', '_', slug)
        return f'{domain}.{slug[:50]}'

def replace_in_file(filepath):
    """Replace hardcoded strings in a single controller file"""
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    original_content = content
    domain = get_domain_from_path(filepath)
    replacements = 0

    # Pattern 1: with('type', 'message')
    def replace_flash(match):
        nonlocal replacements
        msg_type = match.group(1)
        msg_text = match.group(2)
        key = generate_translation_key(domain, msg_type, msg_text)
        replacements += 1
        return f"with('{msg_type}', __('{key}'))"

    content = re.sub(
        r"with\('(success|error|warning|info)',\s*'([^']+)'\)",
        replace_flash,
        content
    )

    # Pattern 2: ['message'] => 'text' or ["message"] => 'text'
    def replace_json(match):
        nonlocal replacements
        msg_text = match.group(1)
        key = generate_translation_key(domain, 'message', msg_text)
        replacements += 1
        return f"['message'] => __('{key}')"

    content = re.sub(
        r"\[(['\"])message\1\]\s*=>\s*'([^']+)'",
        lambda m: replace_json(re.match(r"'([^']+)'", m.group(0))),
        content
    )

    # More robust pattern for message arrays
    content = re.sub(
        r"(\[(?:'|\")?message(?:'|\")?\]\s*=>\s*)'([^']+)'",
        lambda m: m.group(1) + f"__('{generate_translation_key(domain, 'message', m.group(2))}')",
        content
    )

    # Pattern 3: throw new Exception('text')
    def replace_exception(match):
        nonlocal replacements
        exception_type = match.group(1)
        msg_text = match.group(2)
        key = generate_translation_key(domain, 'error', msg_text)
        replacements += 1
        return f"{exception_type}(__('{key}'))"

    content = re.sub(
        r"(throw new [\\a-zA-Z]+Exception)\('([^']+)'\)",
        replace_exception,
        content
    )

    # Only write if changes were made
    if content != original_content:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        return replacements
    return 0

def main():
    """Main processing"""
    print("Replacing hardcoded strings in controllers...")

    total_files_modified = 0
    total_replacements = 0
    modified_files = []

    # Process all controllers
    for root, dirs, files in os.walk(CONTROLLERS_DIR):
        for filename in files:
            if filename.endswith('.php'):
                filepath = os.path.join(root, filename)
                replacements = replace_in_file(filepath)

                if replacements > 0:
                    total_files_modified += 1
                    total_replacements += replacements
                    rel_path = os.path.relpath(filepath, BASE_DIR)
                    modified_files.append({
                        'file': rel_path,
                        'replacements': replacements
                    })

    print(f"\n✓ Processing complete!")
    print(f"  Files modified: {total_files_modified}")
    print(f"  Total replacements: {total_replacements}")

    # Save report
    report = {
        'summary': {
            'files_modified': total_files_modified,
            'total_replacements': total_replacements
        },
        'modified_files': modified_files
    }

    with open(BASE_DIR / 'scripts/i18n_replacement_report.json', 'w', encoding='utf-8') as f:
        json.dump(report, f, ensure_ascii=False, indent=2)

    print(f"\n✓ Report saved to scripts/i18n_replacement_report.json")

    # Show top 10 modified files
    if modified_files:
        print(f"\nTop modified files:")
        sorted_files = sorted(modified_files, key=lambda x: x['replacements'], reverse=True)
        for item in sorted_files[:10]:
            print(f"  {item['file']}: {item['replacements']} replacements")

if __name__ == '__main__':
    main()
