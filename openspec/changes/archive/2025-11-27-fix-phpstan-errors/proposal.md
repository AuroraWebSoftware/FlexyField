# Change: Fix PHPStan Errors

## Why
PHPStan static analysis is currently reporting 23 errors across multiple files. These errors prevent the codebase from passing level 7 static analysis checks, which are important for:
- Maintaining code quality and type safety
- Catching potential bugs before runtime
- Improving IDE autocomplete and type inference
- Ensuring compatibility with strict type checking tools
- Meeting project quality standards (PHPStan level 7 is configured in `phpstan.neon.dist`)

The errors fall into several categories:
1. Missing generic type specifications for Eloquent relations (BelongsTo, HasMany)
2. Missing value type specifications in iterable type arrays
3. Missing PHPDoc annotations for Eloquent model properties
4. Missing return types and parameter types for query scope methods
5. Missing parameter type hints

## What Changes
- Add generic type specifications to all Eloquent relation return types (BelongsTo, HasMany)
- Add value type specifications to all array parameters and return types (e.g., `array<string>` instead of `array`)
- Add PHPDoc `@property` annotations for Eloquent model properties accessed via magic methods
- Add return types and parameter types to query scope methods
- Add missing parameter type hints where needed

**Files to be modified:**
- `src/Contracts/FlexyModelContract.php` (4 errors)
- `src/Exceptions/FieldNotInSetException.php` (1 error)
- `src/FlexyField.php` (1 error)
- `src/Models/FieldSet.php` (11 errors)
- `src/Models/SetField.php` (5 errors)
- `src/Models/Value.php` (1 error)

## Impact
- Affected specs: testing (static analysis quality requirements)
- Affected code: Multiple core files in `src/` directory
- Breaking changes: None (these are type annotation improvements only, no runtime behavior changes)
- Quality improvement: All PHPStan level 7 errors will be resolved, enabling stricter static analysis

