# Implementation Tasks

## 1. Remove Schema Tracking Table
- [ ] 1.1 Remove `ff_view_schema` table creation from migration
- [ ] 1.2 Remove `ff_view_schema` table drop from migration down() method
- [ ] 1.3 Create migration to drop table for existing installations (if needed)

## 2. Implement Metadata-Based Field Detection
- [ ] 2.1 Create `getViewColumns()` method for MySQL (using information_schema)
- [ ] 2.2 Create `getViewColumns()` method for PostgreSQL (using information_schema)
- [ ] 2.3 Extract field names from column names (remove `flexy_` prefix)
- [ ] 2.4 Handle case when view doesn't exist yet

## 3. Update View Recreation Logic
- [ ] 3.1 Update `recreateViewIfNeeded()` to use metadata instead of tracking table
- [ ] 3.2 Remove tracking table insert operations
- [ ] 3.3 Update `forceRecreateView()` to remove tracking table operations
- [ ] 3.4 Ensure performance is acceptable (metadata queries should be fast)

## 4. Update Tests
- [ ] 4.1 Remove all `ff_view_schema` table references from tests
- [ ] 4.2 Update test assertions to work with metadata-based approach
- [ ] 4.3 Ensure all existing tests pass
- [ ] 4.4 Add test for metadata reading when view doesn't exist

## 5. Update Documentation
- [ ] 5.1 Remove `ff_view_schema` references from README.md
- [ ] 5.2 Update openspec/project.md to remove table description
- [ ] 5.3 Update docs/TROUBLESHOOTING.md to remove table-related issues
- [ ] 5.4 Update docs/DEPLOYMENT.md to remove table references
- [ ] 5.5 Update docs/PERFORMANCE.md to explain metadata-based approach

## 6. Migration Support
- [ ] 6.1 Add migration helper to drop table for existing installations
- [ ] 6.2 Document migration steps for users upgrading
- [ ] 6.3 Test migration on both MySQL and PostgreSQL

