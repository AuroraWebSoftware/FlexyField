# Design: Remove Debug Code and TODOs

## Context
During the recent `refactor-fieldset-to-set-and-simplify-schema` change, debug statements were added to troubleshoot type casting issues and model refresh behavior. These debug statements served their purpose and are no longer needed in production code.

## Problem Statement
The codebase contains:
1. **Active debug statements** in `src/Models/Flexy.php` using `fwrite(STDERR, ...)` - 3 instances
2. **Commented debug code** in `src/Traits/Flexy.php` and test files
3. **Unresolved TODO** in `src/FlexyField.php` about BOOLEAN value checking

These need to be removed before the next release to ensure clean, production-ready code.

## Approach

### Phase 1: Debug Statement Removal
Remove all `fwrite(STDERR, ...)` statements from:
- `src/Models/Flexy.php` lines 38, 40, 95

**Rationale**: These were added to trace datetime field type conversions and are no longer needed since the issue was resolved.

### Phase 2: Commented Code Cleanup
Remove commented debug code from:
- `src/Traits/Flexy.php`
- `tests/Feature/SchemaAssignmentTest.php`
- `tests/Feature/EdgeCaseValidationTest.php`

**Rationale**: Commented code creates noise and should be removed once it's no longer needed for reference.

### Phase 3: TODO Resolution
Address TODO in `src/FlexyField.php` line 133:
```php
// TODO BOOLEAN values will be checked
```

**Options**:
1. **Remove if complete**: If BOOLEAN handling is already implemented and tested, remove the TODO
2. **Implement if missing**: Add BOOLEAN value checking logic if it's actually missing
3. **Document if intentional**: If we decided not to implement it, document why

**Decision**: Verify current BOOLEAN handling in view creation code. If it's working correctly (which it should be based on passing tests), remove the TODO.

## Risk Assessment

**Risk Level**: **Low**

- ✅ Only removing debug/development code
- ✅ No functionality changes
- ✅ All tests currently passing
- ✅ Changes are isolated to specific lines
- ⚠️ Need to verify BOOLEAN handling before removing TODO

## Verification Plan

1. Run full test suite (`./vendor/bin/pest`)
2. Run PHPStan for type safety
3. Run Pint for code style
4. Manually verify BOOLEAN field handling in database view creation

## Out of Scope
The following were considered but moved to separate changes:
- Test consolidation (separate change: `consolidate-edge-case-tests`)
- Documentation simplification (separate change: `simplify-documentation`)
- OpenSpec specification cleanup (to be done in relevant feature changes)
- Laravel Boost guide rewrite (separate change: `improve-ai-documentation`)

