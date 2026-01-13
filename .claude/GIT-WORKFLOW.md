# Git Workflow for Claude Code

> General git practices for all projects. Not project-specific.

## Branch Strategy

- `main` = production-ready code
- `feature/descriptive-name` for larger features
- Small fixes can go directly on main

## Commit Messages

Use conventional commits in English:

```
feat: Add user authentication flow
fix: Resolve null pointer in form handler
refactor: Extract validation logic to helper
docs: Update API documentation
style: Format code according to standards
chore: Update dependencies
```

## When to Commit

Commit when it makes sense:
- After completing a logical unit of work
- Before switching context to something else
- When code is in a working state

**Do NOT commit:**
- Broken/incomplete code
- Debug statements or temporary code
- Secrets or credentials

## When to Push

**Always ask the user before pushing.**

Example:
> "I've committed the changes locally. Ready to push when you've validated."

This allows the user to:
- Test the changes locally first
- Review what will be pushed
- Decide on timing

## Co-Authored-By

Claude commits include attribution:
```
Co-Authored-By: Claude <model> <noreply@anthropic.com>
```

This is added automatically by Claude Code.

## Useful Commands

```bash
# Check status before committing
git status

# See what will be committed
git diff --staged

# Check remote status
git log origin/main..HEAD  # commits not yet pushed
```
