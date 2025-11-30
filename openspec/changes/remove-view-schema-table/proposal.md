# Change: Remove View Schema Tracking Table

## Why
The `ff_view_schema` table was added to track which fields exist in the pivot view to optimize view recreation. However, this information can be obtained directly from the database metadata (information_schema), eliminating the need for a separate tracking table. This simplifies the database schema, removes potential synchronization issues, and reduces maintenance overhead.

## What Changes
- Remove `ff_view_schema` table from migration
- Implement metadata-based field detection using information_schema queries
- Update `recreateViewIfNeeded()` to read view columns from database metadata instead of tracking table
- Update `forceRecreateView()` to remove schema tracking table operations
- Remove all references to `ff_view_schema` table in code and tests
- **BREAKING**: Database migration change - existing installations need to drop the table

## Impact
- Affected specs: query-integration
- Affected code:
  - `database/migrations/create_flexyfield_table.php` (remove table creation)
  - `src/FlexyField.php` (update recreateViewIfNeeded, forceRecreateView methods)
  - `tests/Unit/FlexyFieldTest.php` (remove table references)
  - `tests/Feature/ViewRecreationPerformanceTest.php` (remove table references)
  - Documentation files (README.md, docs/*) (update references)
- Database changes: Remove `ff_view_schema` table
- Breaking changes: **BREAKING** - Existing installations must drop the table manually or via migration

