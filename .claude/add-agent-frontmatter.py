#!/usr/bin/env python3
"""
Add YAML frontmatter to agent files missing it
Based on Claude Code 2025 best practices
"""

import re
from pathlib import Path

# Agent file definitions with appropriate model and description
AGENT_CONFIGS = {
    'laravel-testing.md': {
        'name': 'laravel-testing',
        'model': 'sonnet',
        'description': '''Laravel Testing & QA Expert with adaptive test discovery.
  Uses META_COGNITIVE_FRAMEWORK to discover current test coverage, identify gaps, and design effective test strategies.
  Never assumes test structure - discovers it dynamically. Use for testing strategy, TDD, and quality assurance.'''
    },
    'laravel-documentation.md': {
        'name': 'laravel-documentation',
        'model': 'haiku',
        'description': '''Laravel Documentation Specialist for CMIS project.
  Creates and maintains clear, organized documentation. Follows CMIS documentation guidelines.
  Use for writing API docs, guides, and technical documentation.'''
    },
    'laravel-performance.md': {
        'name': 'laravel-performance',
        'model': 'sonnet',
        'description': '''Laravel Performance Optimization Expert with CMIS awareness.
  Discovers performance bottlenecks, optimizes queries, implements caching strategies.
  Understands RLS performance implications and pgvector optimization. Use for performance analysis and optimization.'''
    },
    'laravel-devops.md': {
        'name': 'laravel-devops',
        'model': 'sonnet',
        'description': '''Laravel DevOps & Deployment Expert for CMIS infrastructure.
  Handles CI/CD, server configuration, database deployment, monitoring setup.
  Understands PostgreSQL + pgvector deployment requirements. Use for deployment and infrastructure tasks.'''
    },
    'laravel-architect.md': {
        'name': 'laravel-architect',
        'model': 'sonnet',
        'description': '''Laravel Architecture Expert with CMIS domain knowledge.
  Provides high-level architectural guidance, design pattern recommendations, system design decisions.
  Understands CMIS multi-tenancy architecture and domain organization. Use for architectural decisions and system design.'''
    },
    'laravel-code-quality.md': {
        'name': 'laravel-code-quality',
        'model': 'sonnet',
        'description': '''Laravel Code Quality & Refactoring Expert with CMIS patterns.
  Reviews code quality, identifies code smells, suggests refactoring opportunities.
  Enforces CMIS coding standards and Repository+Service patterns. Use for code reviews and refactoring.'''
    },
    'laravel-tech-lead.md': {
        'name': 'laravel-tech-lead',
        'model': 'sonnet',
        'description': '''Laravel Technical Lead for CMIS project guidance.
  Provides technical leadership, code review, implementation guidance, and best practices.
  Ensures consistency with CMIS architectural patterns and multi-tenancy requirements. Use for code reviews and technical guidance.'''
    },
    'laravel-auditor.md': {
        'name': 'laravel-auditor',
        'model': 'sonnet',
        'description': '''Laravel System Auditor for comprehensive CMIS platform review.
  Performs complete system audits checking architecture, security, performance, code quality, and multi-tenancy compliance.
  Generates detailed audit reports with prioritized recommendations. Use for comprehensive system audits.'''
    },
    'laravel-security.md': {
        'name': 'laravel-security',
        'model': 'sonnet',
        'description': '''Laravel Security Expert with CMIS multi-tenancy focus.
  Audits security vulnerabilities, reviews RLS policies, validates authentication/authorization.
  Checks for OWASP top 10 vulnerabilities and CMIS-specific security requirements. Use for security audits and vulnerability assessment.'''
    },
    'laravel-api-design.md': {
        'name': 'laravel-api-design',
        'model': 'sonnet',
        'description': '''Laravel API Design Expert with CMIS RESTful patterns.
  Designs consistent REST APIs, reviews endpoint structure, ensures proper HTTP methods and status codes.
  Understands org-scoped routing and platform webhook patterns. Use for API design and consistency.'''
    },
    'cmis-doc-organizer.md': {
        'name': 'cmis-doc-organizer',
        'model': 'haiku',
        'description': '''CMIS Documentation Organization Specialist.
  Automatically organizes, consolidates, and maintains project documentation.
  Moves files to proper locations, archives old docs, creates indexes. Use for documentation maintenance and organization.'''
    },
}

def add_frontmatter(file_path, config):
    """Add YAML frontmatter to file"""

    # Read current content
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Check if already has frontmatter
    if content.startswith('---'):
        print(f"   ⏭️  {file_path.name} already has frontmatter, skipping...")
        return False

    # Create frontmatter
    frontmatter = f"""---
name: {config['name']}
description: |
  {config['description']}
model: {config['model']}
---

"""

    # Combine frontmatter with content
    new_content = frontmatter + content

    # Write back
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(new_content)

    print(f"   ✅ Added frontmatter to {file_path.name}")
    return True

def main():
    base_dir = Path('/home/user/cmis.marketing.limited/.claude/agents')

    print("🔧 Adding YAML Frontmatter to Agent Files")
    print("=" * 60)
    print()

    updated_count = 0
    skipped_count = 0

    for filename, config in AGENT_CONFIGS.items():
        file_path = base_dir / filename

        if not file_path.exists():
            print(f"   ⚠️  {filename} not found, skipping...")
            continue

        if add_frontmatter(file_path, config):
            updated_count += 1
        else:
            skipped_count += 1

    print()
    print("=" * 60)
    print(f"✅ Complete! Updated {updated_count} files, skipped {skipped_count}")
    print()

if __name__ == '__main__':
    main()
