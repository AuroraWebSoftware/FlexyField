# Design: View Recreation Optimization

## Context
FlexyField uses a database view (`ff_values_pivot_view`) to enable efficient querying of dynamic fields via Eloquent's query builder. Currently, this view is recreated on every model save that has dirty flexy fields, causing severe performance issues in production environments with frequent updates.

### Stakeholders
- Package users deploying in high-traffic production environments
- Developers performing bulk data operations
- Database administrators managing FlexyField installations

### Constraints
- Must maintain backward compatibility (no breaking changes)
- Must work with both MySQL 8.0+ and PostgreSQL 15+
- Must not introduce data inconsistencies
- View must always reflect actual schema accurately

## Goals / Non-Goals

### Goals
- Reduce view recreation frequency by 90%+ for typical workloads
- Only recreate view when schema changes (new fields added)
- Maintain automatic view synchronization
- Provide manual override for maintenance scenarios
- Support batch operations efficiently

### Non-Goals
- Removing the view entirely (core feature)
- Async/queue-based view updates (deferred)
- Materialized views (PostgreSQL-specific, deferred)
- Column-level change detection (deferred)

## Decisions

### Decision 1: Schema Tracking Table
**What**: Add `ff_view_schema` table to track fields currently in view

**Why**:
- Enables comparison between current schema and new fields
- Simple to implement and maintain
- Works identically on MySQL and PostgreSQL
- Adds minimal storage overhead

**Alternatives Considered**:
1. Parse view definition from database metadata
   - Rejected: Complex, database-specific, fragile
2. Store schema in cache
   - Rejected: Cache invalidation issues, not persistent
3. No tracking, always recreate
   - Rejected: Current approach, causes performance issues

### Decision 2: Change Detection Strategy
**What**: Detect schema changes by comparing dirty field names against ff_view_schema

**Why**:
- Accurate detection of new fields
- Low overhead (simple array diff operation)
- Fails safe (recreates if uncertainty)

**Trade-offs**:
- Deleted fields not automatically removed from view (acceptable - rare operation)
- Requires forceRecreateView() for cleanup (documented)

### Decision 3: Batch Mode API
**What**: Provide `withoutViewUpdates()` closure-based helper

**Why**:
- Clean API for bulk operations
- Automatic cleanup (closure ensures re-enable)
- Similar to Laravel's `Model::withoutEvents()`

**Example**:
```php
FlexyField::withoutViewUpdates(function () {
    foreach ($products as $product) {
        $product->flexy->price = $newPrice;
        $product->save();
    }
}); // View recreated once at end
```

### Decision 4: Maintenance Command
**What**: Add `flexyfield:rebuild-view` Artisan command

**Why**:
- Provides manual recovery mechanism
- Useful for debugging and maintenance
- Standard Laravel CLI pattern

## Risks / Trade-offs

### Risk: Schema tracking gets out of sync
**Mitigation**:
- Provide forceRecreateView() for recovery
- Add validation in tests
- Monitor view recreation logs

### Risk: Performance regression in edge cases
**Mitigation**:
- Comprehensive benchmarking
- Performance tests in CI/CD
- Document performance characteristics

### Risk: Database-specific behavior differences
**Mitigation**:
- Test on both MySQL and PostgreSQL
- Add PostgreSQL to CI/CD matrix

## Migration Plan

### For New Installations
1. Run `php artisan migrate` (includes new ff_view_schema table)
2. No additional steps required

### For Existing Installations
1. Run new migration (creates ff_view_schema table)
2. Run `php artisan flexyfield:rebuild-view` to populate schema tracking
3. Verify with `DB::table('ff_view_schema')->count()`
4. No code changes required

### Rollback Plan
1. Revert migration (drops ff_view_schema table)
2. Code reverts to unconditional recreation
3. No data loss (ff_values unchanged)

## Open Questions
None - design is fully specified.
