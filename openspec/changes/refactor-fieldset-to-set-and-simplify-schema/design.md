# Design: Refactor FieldSet to Set and Simplify Schema

## Context
The FlexyField package currently uses verbose naming conventions that add unnecessary complexity. The term "FieldSet" is redundant, and database column names are longer than necessary. Additionally, the legacy shapes system is deprecated but still present in the codebase, creating maintenance overhead.

## Goals
- Simplify API by renaming `FieldSet` to `Set`
- Shorten database column names for better readability
- Remove deprecated shapes system completely
- Maintain backward compatibility through migration path (for database only, not code)

## Non-Goals
- Changing the underlying architecture or data model
- Modifying field type system or validation logic
- Changing the EAV storage pattern

## Decisions

### Decision: Rename FieldSet to Set
**Rationale**: "Set" is clearer and more concise. A "set" already implies a collection of fields, making "FieldSet" redundant.

**Alternatives considered**:
- Keep `FieldSet`: Too verbose
- Use `FieldGroup`: Similar verbosity issue
- Use `Schema`: Too generic, conflicts with database schema concept

### Decision: Shorten Column Names
**Rationale**: Shorter names improve readability and reduce typing. The context (table name) already provides sufficient information.

**Column mappings**:
- `ff_sets.code` (was `set_code`): Clear in context of sets table
- `ff_set_fields.name` (was `field_name`): Clear in context of fields table
- `ff_set_fields.type` (was `field_type`): Clear in context of fields table
- `ff_set_fields.metadata` (was `field_metadata`): Clear in context of fields table
- `ff_values.name` (was `field_name`): Clear in context of values table
- `ff_values.set_code` (was `field_set_code`): References the set code from `ff_sets.code`

### Decision: Remove Shapes System Completely
**Rationale**: Shapes are deprecated and replaced by field sets. Keeping them creates confusion and maintenance burden.

**Migration**: Users with existing shapes should have already migrated. If not, they can manually migrate data before upgrading.

### Decision: Keep Table Name `ff_set_fields`
**Rationale**: While we could rename to `ff_fields`, `ff_set_fields` clearly indicates the relationship to sets and avoids potential conflicts with generic "fields" terminology.

## Risks / Trade-offs

### Risk: Breaking Changes Require User Updates
**Mitigation**: 
- Clear migration guide in CHANGELOG
- Comprehensive documentation updates
- Database migration handles column renames automatically

### Risk: Column Renames in Large Databases
**Mitigation**: 
- Migration uses ALTER TABLE statements
- Tested on both MySQL and PostgreSQL
- Users can test migration on staging first

### Risk: Loss of Shapes Migration Path
**Mitigation**: 
- Shapes are already deprecated
- Users should have migrated by now
- Manual migration scripts can be provided if needed

## Migration Plan

### Database Migration
1. Rename `ff_field_sets` table to `ff_sets`
2. Rename `set_code` column to `code` in `ff_sets`
3. Rename columns in `ff_set_fields`: `field_name` → `name`, `field_type` → `type`, `field_metadata` → `metadata`
4. Update foreign key references
5. Rename columns in `ff_values`: `field_name` → `name`, `field_set_code` → `set_code`
6. Update foreign key references
7. Drop `ff_shapes` table if it exists
8. Recreate pivot view with new column names

### Code Migration
1. Update all model class names and references
2. Update all method names in Flexy trait
3. Update all exception class names
4. Update all test files
5. Update all documentation

### Rollback Plan
- Keep old migration files for reference
- Database rollback would require reverse ALTER TABLE statements
- Code rollback would require reverting commits

## Open Questions
- None - all decisions are clear

