#!/usr/bin/env python3
"""
CMIS Controller i18n Processor - Phase 2
Generates translation files and replaces hardcoded strings
"""

import os
import re
import json
from pathlib import Path
from collections import defaultdict

BASE_DIR = Path('/home/cmis-test/public_html')
CONTROLLERS_DIR = BASE_DIR / 'app/Http/Controllers'
LANG_DIR = BASE_DIR / 'resources/lang'

# Domain mapping from controller path/name
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
    controller_name = path.stem  # Filename without extension

    # Check if it's in a subdirectory
    try:
        controllers_idx = parts.index('Controllers')
        if len(parts) > controllers_idx + 1:
            # Has subdirectory
            subdir = parts[controllers_idx + 1]
            if subdir in DOMAIN_MAP:
                return DOMAIN_MAP[subdir]
    except ValueError:
        pass

    # Check controller name mapping
    if controller_name in DOMAIN_MAP:
        return DOMAIN_MAP[controller_name]

    # Fallback: extract base name
    base_name = re.sub(r'Controller$', '', controller_name).lower()
    return base_name

def load_analysis():
    """Load the analysis JSON"""
    with open(BASE_DIR / 'scripts/i18n_analysis.json', 'r', encoding='utf-8') as f:
        return json.load(f)

def organize_by_proper_domain(analysis):
    """Re-organize messages by proper domain names"""
    organized = defaultdict(lambda: {'ar': {}, 'en': {}})

    for old_domain, files in analysis['messages_by_domain'].items():
        for file_info in files:
            filepath = file_info['file']
            domain = get_domain_from_path(filepath)

            for msg in file_info['messages']:
                text = msg['text']
                lang = msg['lang']
                key_parts = msg['key'].split('.')
                key_name = key_parts[-1] if len(key_parts) > 1 else 'message'

                # Store translation
                organized[domain][lang][key_name] = text

    return organized

def generate_lang_files(organized_messages):
    """Generate PHP language files"""
    generated_files = []

    for domain, translations in organized_messages.items():
        for lang in ['ar', 'en']:
            lang_dir = LANG_DIR / lang
            lang_dir.mkdir(parents=True, exist_ok=True)

            file_path = lang_dir / f'{domain}.php'

            # Check if file exists
            if file_path.exists():
                # File exists, merge translations
                with open(file_path, 'r', encoding='utf-8') as f:
                    existing_content = f.read()

                # Extract existing keys
                existing_keys = re.findall(r"'([^']+)'\s*=>\s*'", existing_content)

                # Add new keys
                trans = translations[lang]
                for key in trans:
                    if key not in existing_keys:
                        # Append to file (before final ];)
                        with open(file_path, 'r', encoding='utf-8') as f:
                            content = f.read()

                        # Insert before closing ];
                        insertion_point = content.rfind('];')
                        if insertion_point > 0:
                            new_entry = f"    '{key}' => '{trans[key]}',\n"
                            content = content[:insertion_point] + new_entry + content[insertion_point:]

                            with open(file_path, 'w', encoding='utf-8') as f:
                                f.write(content)

                            generated_files.append(f"Updated: {file_path}")
            else:
                # Create new file
                content = f"""<?php

return [
"""
                for key, value in translations[lang].items():
                    # Escape single quotes in value
                    value = value.replace("'", "\\'")
                    content += f"    '{key}' => '{value}',\n"

                content += "];\n"

                with open(file_path, 'w', encoding='utf-8') as f:
                    f.write(content)

                generated_files.append(f"Created: {file_path}")

    return generated_files

def main():
    """Main processing"""
    print("Loading analysis...")
    analysis = load_analysis()

    print("Organizing by proper domains...")
    organized = organize_by_proper_domain(analysis)

    print(f"\nDomains identified: {len(organized)}")
    for domain in organized:
        ar_count = len(organized[domain]['ar'])
        en_count = len(organized[domain]['en'])
        print(f"  - {domain}: {ar_count} AR keys, {en_count} EN keys")

    print("\nGenerating language files...")
    generated = generate_lang_files(organized)

    print(f"\n✓ Generated/updated {len(generated)} language files")
    for file in generated[:10]:  # Show first 10
        print(f"  {file}")

    if len(generated) > 10:
        print(f"  ... and {len(generated) - 10} more")

    # Save organized structure
    output = {
        'domains': list(organized.keys()),
        'translations': {
            domain: {
                lang: list(trans.keys())
                for lang, trans in translations.items()
            }
            for domain, translations in organized.items()
        }
    }

    with open(BASE_DIR / 'scripts/i18n_organized.json', 'w', encoding='utf-8') as f:
        json.dump(output, f, ensure_ascii=False, indent=2)

    print(f"\n✓ Organization saved to scripts/i18n_organized.json")

if __name__ == '__main__':
    main()
