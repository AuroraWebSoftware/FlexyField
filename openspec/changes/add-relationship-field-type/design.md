# Design: Relationship Field Type

## Context
FlexyField currently supports storing primitive values but lacks the ability to reference other Eloquent models. This design document outlines the technical approach for adding relationship support while maintaining consistency with existing patterns and ensuring type safety.

## Goals / Non-Goals

### Goals
- Enable storing references to other Eloquent models in flexy fields
- Support one-to-one and one-to-many relationship patterns
- Maintain type safety and validation
- Integrate seamlessly with existing query capabilities
- Preserve backward compatibility

### Non-Goals
- Many-to-many relationships (would require junction table, out of scope)
- Polymorphic relationships beyond basic model references (complexity not needed initially)
- Automatic relationship loading via Eloquent relationships (flexy fields remain separate from model relationships)
- Relationship constraints at database level (validation handled at application level)

## Decisions

### Decision: Storage Approach
**What**: Store relationship references using `value_related_model_type` and `value_related_model_id` columns in `ff_values` table.

**Why**: 
- Consistent with existing polymorphic pattern (model_type/model_id already used)
- Allows querying via pivot view
- Simple to implement and understand
- No additional tables required

**Alternatives considered**:
- Separate `ff_relationships` table: More normalized but adds complexity and joins
- JSON storage: Loses queryability and type safety
- Single column with JSON: Similar issues as pure JSON approach

### Decision: Relationship Type Configuration
**What**: Store relationship metadata in `SetField.field_metadata` JSON column, including:
- `related_model`: Fully qualified model class name
- `relationship_type`: 'one-to-one' or 'one-to-many'
- `cascade_delete`: Boolean flag for cascade behavior

**Why**:
- Reuses existing metadata column (no schema changes to ff_set_fields)
- Flexible for future relationship options
- Allows per-field configuration

**Alternatives considered**:
- Separate columns in `ff_set_fields`: More explicit but requires migration
- Hardcoded in code: Less flexible, harder to configure

### Decision: Type Detection
**What**: When an Eloquent model instance is assigned to a flexy field, detect it and store as RELATIONSHIP type.

**Why**:
- Automatic type detection matches existing pattern
- Developer-friendly API (just assign model instance)
- Type safety enforced through field set definitions

**Alternatives considered**:
- Explicit type declaration required: More verbose, less intuitive
- String-based references: Loses type safety and model instance benefits

### Decision: Retrieval API
**What**: Provide `getRelatedModel()` method on Value model and automatic model resolution when accessing flexy field.

**Why**:
- Returns actual Eloquent model instances (not just IDs)
- Consistent with Laravel patterns
- Handles null references gracefully

**Alternatives considered**:
- Return only IDs: Less useful, requires manual resolution
- Always eager load: Performance concerns with many relationships

### Decision: Query Integration
**What**: Support querying by related model ID via pivot view, with optional filtering by related model type.

**Why**:
- Maintains consistency with existing query patterns
- Enables filtering products by category, users by manager, etc.
- Works with existing global scope

**Alternatives considered**:
- Separate query methods: Inconsistent with existing API
- No query support: Limits usefulness of relationships

### Decision: Cascade Delete Behavior
**What**: Optional cascade delete configured per field via metadata. When enabled, deleting a model deletes related flexy field values.

**Why**:
- Flexible per-field configuration
- Prevents orphaned references
- Optional to avoid unintended data loss

**Alternatives considered**:
- Always cascade: Too aggressive, may delete unintended data
- Never cascade: Leaves orphaned references
- Database-level foreign keys: Complex with polymorphic relationships

## Risks / Trade-offs

### Risk: Performance with Many Relationships
**Mitigation**: 
- Lazy loading by default (only load when accessed)
- Consider eager loading helpers if needed
- Index on (value_related_model_type, value_related_model_id)

### Risk: Orphaned References
**Mitigation**:
- Validation on save ensures referenced model exists
- Optional cascade delete for cleanup
- Documentation on best practices

### Risk: Type Safety
**Mitigation**:
- Field set definitions enforce related_model type
- Validation checks model class exists and is Eloquent model
- Type hints in code

### Risk: Query Complexity
**Mitigation**:
- Pivot view handles relationship columns like other fields
- Existing query patterns work unchanged
- Document relationship query patterns

## Migration Plan

1. **Add columns to ff_values table**:
   - `value_related_model_type` VARCHAR(255) NULL
   - `value_related_model_id` BIGINT UNSIGNED NULL
   - Add index on (value_related_model_type, value_related_model_id)

2. **Update pivot view**:
   - Include relationship fields in view generation
   - Handle NULL relationship values

3. **Backward compatibility**:
   - New columns are nullable
   - Existing values unaffected
   - No breaking changes to API

4. **Rollback**:
   - Migration can be rolled back
   - Remove columns and update view
   - Existing data preserved (only new relationship fields affected)

## Open Questions

1. Should we support querying by related model attributes (e.g., `where('flexy_category', 'name', 'Electronics')`)? 
   - **Decision**: Not in initial implementation - query by ID is sufficient, can be added later if needed

2. Should relationship fields support eager loading?
   - **Decision**: Yes, via optional helper methods, but not automatic to avoid N+1 issues

3. How to handle soft deletes on related models?
   - **Decision**: Validation checks if model exists (respects soft deletes), but doesn't automatically nullify - explicit handling recommended

