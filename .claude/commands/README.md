# Claude Code Slash Commands

Custom slash commands for common CMIS workflows.

## Usage

Invoke commands by typing `/command-name` in Claude Code.

## Available Commands

### /test
**Description:** Run Laravel test suite and report results

Runs PHPUnit tests, analyzes failures, and provides recommendations. Particularly useful for:
- Verifying multi-tenancy isolation
- Checking RLS policy enforcement
- Platform integration tests

### /migrate
**Description:** Run database migrations with safety checks

Executes migrations with pre-flight checks:
- Reviews pending migrations
- Verifies RLS policies on new tables
- Asks for confirmation
- Validates migration success

### /audit-rls
**Description:** Audit Row-Level Security policies across all CMIS tables

Comprehensive multi-tenancy audit:
- Lists all tables in cmis.* schemas
- Checks RLS enabled status
- Verifies policy definitions
- Identifies tables missing RLS
- Generates detailed report

### /optimize-db
**Description:** Analyze and optimize database performance

Performance analysis including:
- Slow query identification
- Index usage statistics
- Missing index detection
- pgvector optimization
- N+1 query detection
- Caching recommendations

### /create-agent
**Description:** Create a new specialized AI agent for CMIS

Interactive agent creation wizard:
- Prompts for agent details
- Generates agent file with proper structure
- Includes CMIS conventions
- Updates agent registry
- Tests new agent

## Creating Custom Commands

### Command File Format

Create a markdown file in `.claude/commands/` with YAML frontmatter:

```markdown
---
description: Brief description of what this command does
---

Detailed instructions for what Claude should do when this command is invoked.

Can include:
- Multi-step workflows
- Bash commands to run
- Files to check
- Safety considerations
- Output format requirements
```

### Example: Custom Command

`.claude/commands/deploy.md`:
```markdown
---
description: Deploy CMIS to staging environment with checks
---

Deploy CMIS application to staging:

1. Run test suite first: `vendor/bin/phpunit`
2. Verify all tests pass
3. Check git status - ensure branch is clean
4. Build frontend assets: `npm run build`
5. Ask user for deployment confirmation
6. Deploy via: `php artisan deploy staging`
7. Verify deployment health checks
8. Report deployment status

Never deploy if:
- Tests are failing
- Uncommitted changes exist
- Production environment specified
```

### Command Best Practices

1. **Clear description** - Use frontmatter description for command listing
2. **Safety first** - Include safety checks and confirmations
3. **Multi-step workflows** - Break complex tasks into steps
4. **Error handling** - Describe what to do if steps fail
5. **CMIS-specific** - Consider multi-tenancy and RLS implications
6. **Idempotent** - Commands should be safe to run multiple times

## Command Categories

### Testing & Quality
- `/test` - Run test suite
- `/lint` - Run code linters
- `/coverage` - Generate coverage report

### Database
- `/migrate` - Run migrations
- `/audit-rls` - Audit RLS policies
- `/optimize-db` - Performance analysis
- `/seed` - Run database seeders

### Development
- `/create-agent` - New agent wizard
- `/docs` - Generate documentation
- `/analyze` - Code analysis

### Deployment
- `/deploy` - Deploy to environment
- `/rollback` - Rollback deployment

## Integration with Agents

Commands can leverage specialized agents:

```markdown
---
description: Review security across all CMIS components
---

Coordinate comprehensive security review:

1. Use cmis-multi-tenancy agent to audit RLS
2. Use laravel-security agent to check vulnerabilities
3. Use cmis-platform-integration agent to verify OAuth security
4. Generate consolidated security report
5. Prioritize findings by severity
```

## Permissions

Commands run with the same permissions as Claude Code. Configure allowed operations in `.claude/settings.local.json`:

```json
{
  "permissions": {
    "ask": [
      "Bash(php artisan migrate:fresh:*)",
      "Bash(git push --force:*)"
    ]
  }
}
```

## Troubleshooting

**Command not found?**
- Ensure file is in `.claude/commands/` directory
- Check file has `.md` extension
- Verify YAML frontmatter is valid

**Command fails?**
- Check permissions in settings.local.json
- Verify required tools are installed (phpunit, npm, etc.)
- Review command logs for error details

**Command runs but does wrong thing?**
- Update command file instructions
- Test command in isolation
- Check for conflicting settings

## Tips

1. **Chaining commands** - Commands can call other commands
2. **Context aware** - Commands have access to full project context
3. **Version control** - Commit command files to share with team
4. **Documentation** - Use commands as executable documentation
5. **Iteration** - Refine commands based on usage patterns

## Contributing

When adding new commands:
1. Create command file in `.claude/commands/`
2. Test thoroughly
3. Update this README
4. Commit with clear message
5. Share with team

---

**Last Updated:** 2025-11-19
**Total Commands:** 5
**Project:** CMIS - Campaign Management & Integration System
