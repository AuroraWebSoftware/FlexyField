## REMOVED Requirements
### Requirement: Data Migration from Shapes to Field Sets
The system SHALL provide a migration path from the legacy ff_shapes table to field sets.

#### Scenario: Legacy shapes are migrated to default field sets
- **WHEN** the field set migration is executed
- **AND** ff_shapes table contains shape definitions
- **THEN** a default field set SHALL be created for each model_type
- **AND** all shapes for a model_type SHALL be migrated to ff_set_fields
- **AND** existing ff_values SHALL be updated with field_set_code from model instances

#### Scenario: Model instances are assigned to default sets
- **WHEN** the field set migration is executed
- **THEN** all existing model instances SHALL have field_set_code set to 'default'
- **AND** the assignment SHALL be performed for all models using Flexy trait

#### Scenario: Legacy shapes table is dropped
- **WHEN** the migration is completed
- **THEN** the ff_shapes table SHALL be dropped
- **AND** no data loss SHALL occur during the migration

**Reason**: The legacy `ff_shapes` table and migration command have been removed from the codebase. Field Sets are now the only supported system for defining flexy field schemas. Users who were using shapes should have already migrated to field sets using the migration command in previous versions.

**Migration**: Users upgrading from versions that used `ff_shapes` must:
1. Ensure they have already run `php artisan flexyfield:migrate-shapes` to migrate their data to field sets
2. Manually drop the `ff_shapes` table from their database if it still exists: `DROP TABLE IF EXISTS ff_shapes;`
3. Verify all their data is properly migrated to field sets before upgrading

