# Claude Code Hooks

Automation scripts that run at specific events during Claude Code sessions.

## Available Hooks

### session-start.sh
**Event:** SessionStart
**Purpose:** Display project status and important information when session begins

Shows:
- Current git branch and status
- Project health checks (.env, dependencies)
- Quick reference to documentation
- Available specialized agents

### pre-commit-check.sh
**Event:** PreToolUse (git commit)
**Purpose:** Validate code quality before commits

Checks for:
- Debugging statements (dd, dump, console.log, debugger)
- Hardcoded secrets or credentials
- PHP syntax errors
- Migrations without down() methods
- Missing RLS policies on new tables
- Code style issues (via Laravel Pint)

Blocks commit if:
- Secrets detected
- .env file included
- PHP syntax errors found

## Configuring Hooks

To enable these hooks, add to `.claude/settings.local.json`:

```json
{
  "hooks": {
    "SessionStart": [
      {
        "matcher": "*",
        "hooks": [
          {
            "type": "command",
            "command": ".claude/hooks/session-start.sh",
            "timeout": 5
          }
        ]
      }
    ],
    "PreToolUse": [
      {
        "matcher": "Bash(git commit*)",
        "hooks": [
          {
            "type": "command",
            "command": ".claude/hooks/pre-commit-check.sh",
            "timeout": 30
          }
        ]
      }
    ]
  }
}
```

## Creating Custom Hooks

Hook types available:
- **SessionStart** - When session begins
- **SessionEnd** - When session ends
- **PreToolUse** - Before a tool is used
- **PostToolUse** - After a tool is used
- **UserPromptSubmit** - When user submits a prompt
- **Stop** - When user stops generation
- **SubagentStop** - When subagent stops
- **PreCompact** - Before context compaction
- **Notification** - For notification events

Hook script format:
```bash
#!/bin/bash
# Your hook script
# Exit 0 for success, non-zero to block action

# Access hook data via environment variables or stdin
echo "Running hook..."
exit 0
```

## Best Practices

1. **Keep hooks fast** - Set appropriate timeouts
2. **Fail gracefully** - Don't block normal workflow unnecessarily
3. **Clear feedback** - Use emoji and clear messages
4. **Exit codes** - 0 for success, non-zero to block
5. **Security** - Never expose secrets in hook output

## Testing Hooks

Test hooks manually:
```bash
# Make executable
chmod +x .claude/hooks/session-start.sh

# Run directly
./.claude/hooks/session-start.sh

# Test pre-commit hook
./.claude/hooks/pre-commit-check.sh
```

## Disabling Hooks

Temporarily disable all hooks:
```json
{
  "disableAllHooks": true
}
```

Or disable specific hooks by removing from settings.local.json.
