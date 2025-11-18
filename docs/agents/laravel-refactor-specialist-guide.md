# Laravel Refactor Specialist Agent - User Guide

## Overview

The **laravel-refactor-specialist** agent is a dedicated Claude AI agent designed to transform monolithic, hard-to-maintain Laravel code into clean, modular, testable components following the Single Responsibility Principle (SRP).

## When to Use This Agent

Use the `laravel-refactor-specialist` agent when you encounter:

- **Large Files** (>300 lines of code)
- **God Classes** (>500 lines or >20 methods)
- **Fat Controllers** (>200 lines or containing business logic)
- **Long Methods** (>30 lines)
- **Duplicate Code** across multiple locations
- **Mixed Responsibilities** in a single class
- **Poor Test Coverage** combined with high complexity

## Core Capabilities

### 1. Discovery-First Analysis
The agent performs comprehensive code analysis before any refactoring:
- Measures file metrics (LOC, methods, complexity)
- Identifies code smells (God Classes, Fat Controllers, Long Methods)
- Verifies test coverage and runs tests
- Documents current state as baseline

### 2. Refactoring Patterns
The agent applies industry-standard refactoring patterns:
- **Extract Method** - Break down long methods
- **Extract Class** - Split God Classes into focused components
- **Extract Service Layer** - Move business logic from controllers
- **Repository Pattern** - Abstract data access
- **Replace Magic Numbers/Strings** - Use constants
- **Replace Conditional with Polymorphism** - Eliminate complex switches
- **Introduce Parameter Object** - Group related parameters

### 3. Safe Refactoring Workflow
The agent follows Test-Driven Refactoring (Red-Green-Refactor):
1. ✅ Run tests (all must pass)
2. 🔧 Make ONE small refactoring change
3. ✅ Run tests again (verify still passing)
4. 💾 Commit with clear message
5. 🔁 Repeat

### 4. CMIS-Specific Awareness
The agent understands CMIS architecture:
- Preserves Row-Level Security (RLS) compliance
- Avoids manual `org_id` filtering
- Maintains multi-tenancy patterns
- Follows Laravel best practices

### 5. Comprehensive Reporting
The agent generates detailed reports at `docs/active/reports/`:
- Before/after metrics comparison
- Refactoring strategy documentation
- Test coverage verification
- Risk assessment
- Deployment recommendations

## How to Invoke the Agent

### Method 1: Direct Request
```
Can you refactor the UserController? It's over 500 lines.
```

Claude Code will automatically recognize this as a refactoring task and invoke the agent.

### Method 2: Explicit Agent Call
```
Use the laravel-refactor-specialist agent to analyze and refactor app/Http/Controllers/CampaignController.php
```

### Method 3: Code Smell Mention
```
This class is a God class with too many responsibilities. Can you help modularize it?
```

## Example Usage Scenarios

### Scenario 1: Refactoring a Fat Controller

**User Request:**
```
My UserController has 547 lines with business logic, database queries, and validation all mixed together. Please refactor it.
```

**Agent Workflow:**
1. **Discovery**: Analyzes UserController, identifies:
   - 547 lines, 23 methods
   - 7 long methods (>30 lines)
   - Business logic in controller (Fat Controller smell)
   - 12 existing tests (all passing)

2. **Strategy**: Plans to split into 4 components:
   - UserController (presentation only)
   - UserRegistrationService (registration logic)
   - UserProfileService (profile management)
   - UserRepository (data access)

3. **Refactor**: Applies Extract Service Layer and Repository Pattern
   - Moves business logic to services
   - Abstracts queries to repository
   - Keeps controller thin (HTTP concerns only)

4. **Validate**: Runs tests after each change (15 commits, all green)

5. **Report**: Generates `docs/active/reports/refactoring-2025-11-18-UserController.md` with:
   - 23% reduction in total lines
   - 48% improvement in average method length
   - 40% improvement in nesting depth
   - All tests passing + 3 new unit tests

### Scenario 2: Breaking Down a God Class

**User Request:**
```
The CampaignService class handles campaign creation, updates, publishing, scheduling, analytics, and notifications. It's too big. Please split it.
```

**Agent Workflow:**
1. **Discovery**: Identifies 782 lines, 31 methods, multiple responsibilities
2. **Strategy**: Splits by responsibility (SRP):
   - CampaignManagementService (CRUD operations)
   - CampaignPublishingService (publishing logic)
   - CampaignSchedulingService (scheduling)
   - CampaignAnalyticsService (metrics/reporting)
   - CampaignNotificationService (notifications)

3. **Refactor**: Extracts each responsibility to separate class
4. **Validate**: All tests green, behavior preserved
5. **Report**: Documents 5-way split with improved maintainability

### Scenario 3: Eliminating Magic Strings

**User Request:**
```
There are hardcoded status strings like "active", "pending", "completed" throughout the code. Can you refactor these?
```

**Agent Workflow:**
1. **Discovery**: Finds 23 occurrences across 8 files
2. **Strategy**: Create CampaignStatus class with constants
3. **Refactor**: Replace all magic strings with `CampaignStatus::ACTIVE`
4. **Validate**: Tests pass, no behavior change
5. **Report**: Documents improved type safety and maintainability

## Agent Output

### During Refactoring
The agent provides real-time progress updates:
```
✅ Discovery Phase Complete
   - UserController: 547 lines, 23 methods
   - Code Smells: God Class, Fat Controller, 7 Long Methods
   - Tests: 12 passing ✅

🎯 Refactoring Strategy
   - Extract Service Layer (business logic)
   - Introduce Repository Pattern (data access)
   - Split into 4 focused classes

🔧 Refactoring in Progress
   ✅ [1/5] Extract UserRegistrationService - Tests passing
   ✅ [2/5] Extract UserProfileService - Tests passing
   ✅ [3/5] Introduce UserRepository - Tests passing
   ...
```

### Final Report Location
```
docs/active/reports/refactoring-YYYY-MM-DD-ClassName.md
```

## Critical Agent Rules

The agent strictly follows these rules:

1. **NEVER Refactor Without Tests**
   - If tests don't exist, agent STOPS and asks to create them first
   - All tests MUST pass before starting

2. **One Refactoring at a Time**
   - One pattern application per commit
   - Small, incremental changes

3. **Maintain CMIS Patterns**
   - Preserve RLS compliance
   - No manual `org_id` filtering
   - Follow multi-tenancy conventions

4. **Measure Impact**
   - Capture before/after metrics
   - Document complexity reduction
   - Prove value with numbers

5. **Behavior Preservation**
   - Tests must pass after EVERY change
   - If tests fail: revert and try different approach
   - Refactoring changes structure, NOT behavior

## Metrics You Can Expect

The agent tracks and improves:

| Metric | What It Measures | Typical Improvement |
|--------|------------------|---------------------|
| **Lines of Code** | File size | 20-40% reduction |
| **Method Count** | Complexity | Better distribution |
| **Avg Method Length** | Readability | 40-60% reduction |
| **Max Nesting Depth** | Complexity | 30-50% reduction |
| **SRP Compliance** | Design Quality | ❌ → ✅ |
| **Test Coverage** | Safety | Maintained + new tests |

## Best Practices

### Before Requesting Refactoring

1. **Ensure Tests Exist**
   - Agent requires passing tests
   - Create characterization tests if needed

2. **Commit Clean State**
   - Working tree should be clean
   - Agent will make many commits

3. **Review Current State**
   - Know what the code does
   - Identify main pain points

### During Refactoring

1. **Trust the Process**
   - Agent makes small, incremental changes
   - Many commits are normal and safe

2. **Review Commits**
   - Each commit is focused and revertible
   - Check commit messages for clarity

3. **Monitor Tests**
   - Tests run after every change
   - All tests must stay green

### After Refactoring

1. **Review the Report**
   - Check metrics improvement
   - Understand architectural changes
   - Review risk assessment

2. **Code Review**
   - Review generated code with team
   - Ensure patterns align with team standards

3. **Deploy Safely**
   - Deploy to staging first
   - Monitor for 24h in production
   - Be ready to rollback if needed

## Troubleshooting

### Agent Stops and Says "No Tests Found"

**Cause:** Target file has no corresponding test file.

**Solution:**
1. Create test file first (e.g., `tests/Feature/UserControllerTest.php`)
2. Write basic characterization tests
3. Re-invoke agent

### Tests Fail After Refactoring

**Cause:** Refactoring changed behavior (not supposed to happen).

**Solution:**
1. Agent automatically reverts the change
2. Tries different refactoring approach
3. If persistent, agent reports and asks for guidance

### Too Many Small Commits

**Cause:** Agent follows one-refactoring-per-commit rule.

**Solution:**
- This is intentional and safe
- Each commit is revertible
- Use squash merge if desired for final PR

### Agent Suggests Too Much Refactoring

**Cause:** Agent is thorough and identifies all opportunities.

**Solution:**
- Agent prioritizes by impact
- You can request to focus on specific patterns
- Future refactoring suggestions are optional

## Integration with Development Workflow

### Git Workflow
```bash
# Agent creates feature branch
git checkout -b refactor/user-controller

# Agent makes many small commits
git log --oneline
# refactor: Extract UserRegistrationService from UserController
# refactor: Extract UserProfileService from UserController
# refactor: Introduce UserRepository for data access
# ...

# Review and push
git push origin refactor/user-controller

# Create PR with report link
```

### Pull Request Template
```markdown
## Refactoring: UserController

### Summary
Refactored monolithic UserController (547 lines) into 4 focused components following SRP.

### Metrics Improvement
- Total Lines: -23%
- Avg Method Length: -48%
- Max Nesting Depth: -40%
- Test Coverage: ✅ All passing + 3 new tests

### Detailed Report
See: `docs/active/reports/refactoring-2025-11-18-UserController.md`

### Risk Assessment
**LOW** - All tests passing, behavior preserved, incremental changes.

### Deployment
✅ Safe to deploy to staging
⚠️ Monitor error logs for 24h after production deploy
```

## Advanced Usage

### Custom Refactoring Focus

Request specific patterns:
```
Please refactor UserController, but ONLY extract the service layer. Don't touch the repository logic yet.
```

### Iterative Refactoring

Refactor in phases:
```
Phase 1: Extract service layer from UserController
Phase 2: Introduce repository pattern
Phase 3: Break down long methods
```

### Team Standards

Specify conventions:
```
When refactoring, follow our team's service layer naming: {Entity}Service, not {Entity}Manager.
```

## File Locations

### Agent Configuration
```
.claude/agents/laravel-refactor-specialist.md
```

### Generated Reports
```
docs/active/reports/refactoring-YYYY-MM-DD-ClassName.md
```

### Agent Guide (This File)
```
docs/agents/laravel-refactor-specialist-guide.md
```

## Related Documentation

- [Laravel Best Practices](../laravel_embedding_guidelines.md)
- [CMIS Implementation Plan](../CMIS_IMPLEMENTATION_PLAN.md)
- [Database Setup](../database-setup.md)

## Support

For issues or questions about the refactoring agent:
1. Check this guide first
2. Review generated refactoring reports
3. Consult with team lead on architectural decisions

---

**Agent Version:** 1.0
**Last Updated:** 2025-11-18
**Maintained By:** CMIS Development Team
