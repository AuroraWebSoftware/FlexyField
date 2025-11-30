# Design: Refactor FieldSet to Schema and Simplify Database Naming

## Context
The FlexyField package currently has confusing naming conventions that create cognitive overhead. The most critical issue is that `ff_field_sets` and `ff_set_fields` differ only in word order, making them easily confused. Additionally, column naming is inconsistent (`set_code` vs `field_set_code`, `metadata` vs `field_metadata`), and the composite key design prevents proper foreign key constraints.

## Goals
- Use industry-standard "Schema" terminology for clarity
- Eliminate table name confusion with clearly distinct names
- Standardize column naming across tables
- Enable proper foreign key constraints with schema_id columns
- Remove deprecated shapes system completely
- Maintain database integrity through proper migration

## Non-Goals
- Changing the underlying architecture or data model
- Modifying field type system or validation logic
- Changing the EAV storage pattern

## Decisions

### Decision: Rename FieldSet to FieldSchema
**Rationale**: "Schema" is industry-standard terminology for defining data structure. The new table names (`ff_schemas`, `ff_schema_fields`, `ff_field_values`) are clearly distinct and impossible to confuse.

**Alternatives considered**:
- Keep `FieldSet`: Causes confusion with `ff_field_sets` vs `ff_set_fields`
- Use `Set`: Still causes some confusion (e.g., `ff_sets` vs `ff_set_fields`)
- Use `FieldGroup`: Not industry-standard terminology
- Use bare `Schema`: Chosen `FieldSchema` to maintain consistency with package naming and avoid conflicts

### Decision: Standardize Column Naming
**Rationale**: Consistent naming improves maintainability. Avoid word repetition within table context (e.g., no `field_name` in a table called `ff_schema_fields`). Use consistent reference names across tables.

**Column mappings**:

`ff_schemas` table:
- `schema_code` (was `set_code`): Consistent with new terminology
- `metadata` unchanged: Already consistent

`ff_schema_fields` table:
- `schema_code` (was `set_code`): Consistent reference to parent schema
- `schema_id` NEW: Foreign key to `ff_schemas.id` for proper constraints
- `name` (was `field_name`): Avoid repetition - "field" is implied by table name
- `type` (was `field_type`): Avoid repetition - "field" is implied by table name
- `metadata` (was `field_metadata`): Consistent with `ff_schemas.metadata`

`ff_field_values` table:
- `name` (was `field_name`): Avoid repetition - "field" is implied by table name
- `schema_code` (was `field_set_code`): Consistent with new terminology
- `schema_id` NEW: Foreign key to `ff_schemas.id` for proper constraints

### Decision: Add Proper Foreign Key Constraints
**Rationale**: The current composite key design (`model_type`, `schema_code`) prevents foreign key constraints in PostgreSQL. Adding `schema_id` columns enables proper database-level referential integrity.

**Implementation**:
- Add `schema_id` to `ff_schema_fields` with FK to `ff_schemas.id` (CASCADE on delete)
- Add `schema_id` to `ff_field_values` with FK to `ff_schemas.id` (SET NULL on delete)
- Keep `schema_code` columns for flexibility and backward compatibility
- Update application logic to maintain both `schema_id` and `schema_code`

### Decision: Remove Shapes System Completely
**Rationale**: Shapes are deprecated and replaced by schemas (formerly field sets). Keeping them creates confusion and maintenance burden.

**Migration**: Users with existing shapes should have already migrated. If not, they can manually migrate data before upgrading.

## Risks / Trade-offs

### Risk: Breaking Changes Require User Updates
**Mitigation**:
- Clear migration guide in CHANGELOG
- Comprehensive documentation updates
- Database migration handles all renames and FK additions automatically
- Version bump to signal major breaking change

### Risk: Column Renames in Large Databases
**Mitigation**:
- Migration uses ALTER TABLE statements (fast metadata-only operations in most databases)
- Tested on both MySQL and PostgreSQL
- Users can test migration on staging first
- Migration is reversible

### Risk: Loss of Shapes Migration Path
**Mitigation**:
- Shapes are already deprecated
- Users should have migrated by now
- Manual migration scripts can be provided if needed

### Risk: Dual Column Maintenance (schema_id and schema_code)
**Trade-off**: Maintaining both `schema_id` (for FK) and `schema_code` (for flexibility) adds slight complexity but provides:
- Database-level referential integrity via schema_id
- Flexibility for cross-database references via schema_code
- Easier migration path (keep existing schema_code logic working)

## Migration Plan

### Database Migration (in order)
1. Rename `ff_field_sets` → `ff_schemas`
2. Rename `ff_set_fields` → `ff_schema_fields`
3. Rename `ff_values` → `ff_field_values`
4. Rename column `ff_schemas.set_code` → `schema_code`
5. Add `ff_schema_fields.schema_id` (nullable initially)
6. Populate `ff_schema_fields.schema_id` from join with `ff_schemas`
7. Make `ff_schema_fields.schema_id` NOT NULL
8. Add FK constraint `ff_schema_fields.schema_id` → `ff_schemas.id` (CASCADE on delete)
9. Rename `ff_schema_fields.set_code` → `schema_code`
10. Rename `ff_schema_fields.field_name` → `name`
11. Rename `ff_schema_fields.field_type` → `type`
12. Rename `ff_schema_fields.field_metadata` → `metadata`
13. Add `ff_field_values.schema_id` (nullable)
14. Populate `ff_field_values.schema_id` from join with `ff_schemas`
15. Add FK constraint `ff_field_values.schema_id` → `ff_schemas.id` (SET NULL on delete)
16. Rename `ff_field_values.field_set_code` → `schema_code`
17. Rename `ff_field_values.field_name` → `name`
18. Drop `ff_shapes` table if exists
19. Recreate pivot view with new table/column names

### Code Migration
1. Rename model files: `FieldSet.php` → `FieldSchema.php`, `SetField.php` → `SchemaField.php`, `Value.php` → `FieldValue.php`
2. Update model class names and table references
3. Update model relationships to use both schema_id and schema_code
4. Rename exception files and classes
5. Update all methods in Flexy trait (renames and column references)
6. Update FlexyField.php view generation logic
7. Update all test files
8. Update all documentation

### Rollback Plan
- Keep old migration files for reference
- Provide reverse migration file that undoes all changes
- Code rollback via git revert
- Tag release before migration for easy rollback point

## Open Questions
- None - all decisions are clear

