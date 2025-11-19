---
description: Run Laravel test suite and report results
---

Run the full Laravel test suite for CMIS project:

1. Execute PHPUnit tests: `vendor/bin/phpunit`
2. Check for test failures
3. If tests fail, analyze the failures and suggest fixes
4. Report test coverage statistics if available
5. Highlight any multi-tenancy or RLS-related test failures

Important:
- Run tests from project root
- Ensure database is properly configured
- Check that RLS context is set correctly in tests
