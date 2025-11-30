# Design: Remove View Schema Tracking Table

## Context
The `ff_view_schema` table was introduced to track which fields are currently in the pivot view, enabling optimized view recreation. However, this information is already available in the database metadata (information_schema), making the tracking table redundant.

### Stakeholders
- Package users with existing installations (need migration path)
- Developers maintaining the codebase (simpler code)
- Database administrators (one less table to manage)

### Constraints
- Must maintain backward compatibility for view recreation behavior
- Must work with both MySQL 8.0+ and PostgreSQL 15+
- Must handle case when view doesn't exist yet
- Performance must remain acceptable (metadata queries are typically fast)

## Goals / Non-Goals

### Goals
- Remove redundant `ff_view_schema` table
- Simplify database schema
- Eliminate potential synchronization issues
- Maintain same view recreation optimization behavior
- Use database metadata as single source of truth

### Non-Goals
- Changing view recreation frequency (still only recreate when new fields detected)
- Removing view recreation optimization (core feature remains)
- Changing view structure or behavior

## Decisions

### Decision 1: Use Database Metadata Instead of Tracking Table
**What**: Read view columns directly from information_schema instead of maintaining separate tracking table

**Why**:
- Eliminates redundant data storage
- Single source of truth (view itself)
- No synchronization issues
- Simpler codebase
- Standard database approach

**Alternatives Considered**:
1. Keep tracking table
   - Rejected: Redundant, adds complexity
2. Use cache instead
   - Rejected: Cache invalidation issues, not persistent
3. Always recreate view
   - Rejected: Performance regression

### Decision 2: Database-Specific Metadata Queries
**What**: Implement separate methods for MySQL and PostgreSQL metadata queries

**Why**:
- Different SQL syntax for information_schema
- Different column naming conventions
- Ensures compatibility with both databases

**MySQL Implementation**:
```sql
SELECT COLUMN_NAME 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'ff_values_pivot_view' 
AND COLUMN_NAME LIKE 'flexy_%'
```

**PostgreSQL Implementation**:
```sql
SELECT column_name 
FROM information_schema.columns 
WHERE table_schema = current_schema()
AND table_name = 'ff_values_pivot_view' 
AND column_name LIKE 'flexy_%'
```

### Decision 3: Handle Non-Existent View
**What**: Return empty array when view doesn't exist yet

**Why**:
- First view creation scenario
- Graceful degradation
- Allows view creation to proceed normally

### Decision 4: Migration Strategy
**What**: Provide migration to drop table for existing installations

**Why**:
- Clean upgrade path
- Prevents orphaned table
- Documents breaking change clearly

## Risks / Trade-offs

### Risk: Metadata Query Performance
**Mitigation**:
- information_schema queries are typically fast (indexed)
- Only executed when checking for new fields (same frequency as before)
- Benchmark to ensure acceptable performance

### Risk: Database-Specific Differences
**Mitigation**:
- Test on both MySQL and PostgreSQL
- Use database-specific queries
- Handle edge cases (view doesn't exist, different schemas)

### Risk: Breaking Change for Existing Installations
**Mitigation**:
- Provide clear migration documentation
- Create migration to drop table
- Document in CHANGELOG as breaking change

## Migration Plan

### For New Installations
1. Run `php artisan migrate` (table won't be created)
2. No additional steps required

### For Existing Installations
1. Run migration to drop `ff_view_schema` table
2. Verify view still works correctly
3. No code changes required (behavior remains same)

### Rollback Plan
1. Revert code changes
2. Re-run migration to recreate table
3. Run `php artisan flexyfield:rebuild-view` to repopulate table
4. No data loss (ff_values unchanged)

## Implementation Details

### getViewColumns() Method Signature
```php
/**
 * Get field names currently in the pivot view from database metadata
 * 
 * @return array<string> Array of field names (without flexy_ prefix)
 */
private static function getViewColumns(): array
```

### Field Name Extraction
- View columns are named `flexy_{field_name}`
- Extract field names by removing `flexy_` prefix
- Filter out `model_type` and `model_id` columns

### Error Handling
- If view doesn't exist: return empty array (triggers view creation)
- If metadata query fails: log error and return empty array (safe fallback)

## Open Questions
None - design is fully specified.

