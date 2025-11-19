---
description: Create a new specialized AI agent for CMIS
---

Create a new specialized AI agent following CMIS conventions:

1. Ask for agent details:
   - Name (kebab-case, e.g., "cmis-feature-expert")
   - Purpose/responsibility
   - Model to use (haiku for lightweight, sonnet for complex)
   - Tools needed (if restricted)

2. Create agent file in .claude/agents/ with:
   - YAML frontmatter (name, description, model, tools if needed)
   - Reference to META_COGNITIVE_FRAMEWORK.md
   - Reference to CMIS_PROJECT_KNOWLEDGE.md
   - Adaptive discovery patterns (no hardcoded facts)
   - CMIS-specific examples
   - Multi-tenancy awareness

3. Update .claude/agents/README.md to include new agent

4. Test agent with sample query

5. Commit changes with clear message

Template structure:
```yaml
---
name: agent-name
description: |
  One-line summary of agent purpose and when to use it.
  Include key specializations and domains.
model: haiku|sonnet
tools: Read,Write,Bash,Grep,Glob  # Optional: restrict tools
---

# Agent Name
## Subtitle

You are the **Agent Name** - description...

## 🚨 CRITICAL: APPLY ADAPTIVE INTELLIGENCE

**BEFORE responding:**

1. Consult `.claude/knowledge/META_COGNITIVE_FRAMEWORK.md`
2. Use `.claude/knowledge/DISCOVERY_PROTOCOLS.md`
3. Reference `.claude/CMIS_PROJECT_KNOWLEDGE.md`

[Rest of agent prompt...]
```
