# Change: Add Production Documentation

## Why
Current documentation lacks critical information for production deployments including performance guidelines, best practices, deployment procedures, and troubleshooting guides. This gap makes it difficult for teams to evaluate FlexyField for production use, leads to common pitfalls, and results in poor deployment decisions. Comprehensive production documentation is essential for package adoption and successful deployments.

## What Changes
- Create PERFORMANCE.md with performance characteristics, query optimization, scaling strategies, and monitoring guidance
- Create BEST_PRACTICES.md with shape definition patterns, validation strategies, naming conventions, and migration patterns
- Create DEPLOYMENT.md with deployment checklist, rollback procedures, and monitoring setup
- Create TROUBLESHOOTING.md with common issues, solutions, and debug procedures
- Update README.md with links to new documentation and performance section
- Document when NOT to use FlexyField (anti-patterns)
- Add production readiness checklist

## Impact
- Affected specs: documentation
- New files:
  - docs/PERFORMANCE.md (~300 lines)
  - docs/BEST_PRACTICES.md (~400 lines)
  - docs/DEPLOYMENT.md (~200 lines)
  - docs/TROUBLESHOOTING.md (~150 lines)
- Updated files:
  - README.md (add links and performance section)
- No code changes, documentation only
