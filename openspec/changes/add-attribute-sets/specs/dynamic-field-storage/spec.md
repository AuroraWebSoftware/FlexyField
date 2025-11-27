# Dynamic Field Storage

## Purpose
FlexyField implements an Entity-Attribute-Value (EAV) pattern with field sets to enable dynamic field management for Laravel Eloquent models. This capability handles the storage, retrieval, and management of flexible field values scoped to field sets using a polymorphic storage system.

## MODIFIED Requirements

### Requirement: EAV Storage Tables
The system SHALL provide database tables for storing field set definitions, field definitions, and values using an EAV pattern.

#### Scenario: Field set definitions are stored
- **WHEN** a field set is defined for a model
- **THEN** it SHALL be stored in the ff_field_sets table
- **AND** the set SHALL include model_type, set_code, label, description, metadata, and is_default

#### Scenario: Field definitions are stored within field sets
- **WHEN** a field is defined for a field set
- **THEN** it SHALL be stored in the ff_set_fields table
- **AND** the field SHALL include set_code, field_name, field_type, sort, validation_rules, validation_messages, and field_metadata

#### Scenario: Field values are stored with field set context
- **WHEN** a flexy field value is saved
- **THEN** it SHALL be stored in the ff_values table
- **AND** the value SHALL include field_set_code from the model instance
- **AND** the value SHALL be associated with the correct model via model_type and model_id
- **AND** the value SHALL be stored in the appropriate typed column

### Requirement: Database Migration
The system SHALL provide migrations to create necessary database tables and views.

#### Scenario: Migration creates required tables
- **WHEN** migrations are executed
- **THEN** ff_field_sets table SHALL be created with appropriate schema
- **AND** ff_set_fields table SHALL be created with set scoping
- **AND** ff_values table SHALL include field_set_code column
- **AND** ff_values_pivot_view SHALL be created for efficient querying with set context

#### Scenario: Foreign key constraints are created
- **WHEN** migrations are executed
- **THEN** ff_set_fields SHALL have foreign key to ff_field_sets(set_code) with CASCADE
- **AND** model tables SHALL have foreign key to ff_field_sets(set_code) with SET NULL

#### Scenario: Model tables include field set column
- **WHEN** migrations are executed for models using Flexy trait
- **THEN** an field_set_code column SHALL be added to model tables
- **AND** the column SHALL be indexed for performance

#### Scenario: Migration is reversible
- **WHEN** migration rollback is executed
- **THEN** all FlexyField tables, columns, and views SHALL be dropped
- **AND** the database SHALL return to its previous state

### Requirement: Model Integration
The system SHALL integrate with Laravel Eloquent models through traits and contracts.

#### Scenario: Models can use Flexy trait with field sets
- **WHEN** a model uses the Flexy trait
- **AND** implements FlexyModelContract
- **THEN** the model SHALL have access to field set functionality
- **AND** the model SHALL support field set assignment
- **AND** the model SHALL support dynamic field assignment scoped to its field set

#### Scenario: Flexy fields are accessible via magic accessor
- **WHEN** accessing $model->flexy
- **THEN** a Flexy model instance SHALL be returned
- **AND** dynamic field assignment SHALL be enabled via $model->flexy->field_name
- **AND** only fields from the instance's field set SHALL be accessible

### Requirement: Field Value Persistence
The system SHALL persist flexy field values with field set context when models are saved.

#### Scenario: Dirty flexy fields are saved with set context
- **WHEN** a model with dirty flexy fields is saved
- **THEN** each dirty field SHALL be persisted to ff_values table
- **AND** the field_set_code SHALL be included from the model instance
- **AND** existing values SHALL be updated via updateOrCreate
- **AND** the pivot view SHALL be recreated to include new fields

#### Scenario: Model deletion removes field values
- **WHEN** a model is deleted
- **THEN** all associated flexy field values SHALL be deleted from ff_values
- **AND** the ff_values records SHALL be removed by model_type and model_id

### Requirement: Pivot View for Querying
The system SHALL maintain a database view that pivots EAV data into a queryable format with field set context.

#### Scenario: Pivot view includes all fields across field sets
- **WHEN** the pivot view is created
- **THEN** it SHALL include columns for all distinct field names across all field sets
- **AND** each field SHALL be prefixed with flexy_
- **AND** the view SHALL join values from ff_values table
- **AND** the view SHALL include field_set_code context

#### Scenario: View is recreated when schema changes
- **WHEN** new fields are added to any field set
- **THEN** the pivot view SHALL be dropped and recreated
- **AND** all existing and new fields across all sets SHALL be included in the view

## REMOVED Requirements

### Requirement: Shape definitions are stored
This requirement is replaced by field set and field definitions.

### Requirement: Field groups are stored
This requirement is removed. Field groups are deferred to v2.

## ADDED Requirements

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

### Requirement: Foreign Key Cascading
The system SHALL handle cascading deletions when field sets or fields are removed.

#### Scenario: Deleting field set cascades to fields
- **WHEN** a field set is deleted
- **THEN** all SetField records for the set SHALL be cascade deleted via foreign key
- **AND** no orphaned field records SHALL remain

#### Scenario: Deleting field set nullifies model references
- **WHEN** a field set is deleted
- **THEN** all model instances with field_set_code SHALL have it set to NULL via foreign key
- **AND** accessing flexy fields on these instances SHALL throw FieldSetNotFoundException
