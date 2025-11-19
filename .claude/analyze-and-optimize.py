#!/usr/bin/env python3
"""
Analyze .claude/ directory and create optimization plan
"""

import os
import re
from pathlib import Path
from collections import defaultdict

# Base directory
base_dir = Path('/home/user/cmis.marketing.limited/.claude')

# Results
results = {
    'agents_missing_frontmatter': [],
    'agents_with_frontmatter': [],
    'duplicate_files': [],
    'root_md_files': [],
    'knowledge_files': [],
    'total_agent_files': 0,
    'agents_by_model': defaultdict(list),
}

def check_yaml_frontmatter(file_path):
    """Check if file has YAML frontmatter"""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            first_line = f.readline().strip()
            if first_line == '---':
                # Read until next ---
                content = []
                for line in f:
                    if line.strip() == '---':
                        return True, '\n'.join(content)
                    content.append(line)
            return False, None
    except Exception as e:
        return False, None

def extract_model_from_frontmatter(frontmatter):
    """Extract model from frontmatter"""
    if frontmatter:
        match = re.search(r'model:\s*(\w+)', frontmatter)
        if match:
            return match.group(1)
    return None

# Analyze agent files
agent_dir = base_dir / 'agents'
if agent_dir.exists():
    for file in agent_dir.glob('*.md'):
        results['total_agent_files'] += 1
        has_fm, fm_content = check_yaml_frontmatter(file)

        if has_fm:
            results['agents_with_frontmatter'].append(str(file.name))
            model = extract_model_from_frontmatter(fm_content)
            if model:
                results['agents_by_model'][model].append(str(file.name))
        else:
            results['agents_missing_frontmatter'].append(str(file.name))

# Find root .claude/ MD files
for file in base_dir.glob('*.md'):
    results['root_md_files'].append({
        'name': file.name,
        'size': file.stat().st_size,
        'path': str(file)
    })

# Find knowledge files
knowledge_dir = base_dir / 'knowledge'
if knowledge_dir.exists():
    for file in knowledge_dir.glob('*.md'):
        if file.name != 'README.md':
            results['knowledge_files'].append(file.name)

# Check for duplicates between root and knowledge
root_names = {f['name'] for f in results['root_md_files']}
knowledge_names = set(results['knowledge_files'])
duplicates = root_names & knowledge_names
if duplicates:
    results['duplicate_files'] = list(duplicates)

# Print results
print("=" * 60)
print("CLAUDE FRAMEWORK ANALYSIS REPORT")
print("=" * 60)
print()

print(f"📁 Total Agent Files: {results['total_agent_files']}")
print(f"   ✅ With YAML frontmatter: {len(results['agents_with_frontmatter'])}")
print(f"   ❌ Missing frontmatter: {len(results['agents_missing_frontmatter'])}")
print()

if results['agents_missing_frontmatter']:
    print("🔧 Agents Missing Frontmatter:")
    for agent in results['agents_missing_frontmatter']:
        print(f"   - {agent}")
    print()

print("📊 Agents by Model:")
for model, agents in results['agents_by_model'].items():
    print(f"   {model}: {len(agents)} agents")
print()

print(f"📄 Root .claude/ MD Files: {len(results['root_md_files'])}")
for file in results['root_md_files']:
    size_kb = file['size'] / 1024
    print(f"   - {file['name']} ({size_kb:.1f} KB)")
print()

print(f"📚 Knowledge Base Files: {len(results['knowledge_files'])}")
print()

if results['duplicate_files']:
    print(f"⚠️  Duplicate Files (root & knowledge/): {len(results['duplicate_files'])}")
    for dup in results['duplicate_files']:
        print(f"   - {dup}")
    print()

print("=" * 60)
print("OPTIMIZATION RECOMMENDATIONS")
print("=" * 60)
print()

if results['agents_missing_frontmatter']:
    print("1. ❌ Add YAML frontmatter to agents missing it")
    print()

if results['duplicate_files']:
    print("2. ❌ Remove duplicate files from .claude/ root")
    print()

if results['root_md_files']:
    non_readme = [f for f in results['root_md_files'] if f['name'] != 'README.md']
    if non_readme:
        print(f"3. ❌ Archive or move {len(non_readme)} root MD files")
        print()

print("4. ✅ All agents should reference knowledge base")
print("5. ✅ Ensure consistent model selection (haiku/sonnet)")
print("6. ✅ Add tool restrictions where appropriate")
print()
