## MODIFIED Requirements
### Requirement: EAV Storage Tables
The system SHALL provide database tables for storing field definitions and values using an EAV pattern with Sets.

#### Scenario: Set definitions are stored
- **WHEN** a set is created for a model
- **THEN** it SHALL be stored in the ff_sets table
- **AND** the set SHALL include model_type, code, label, description, metadata, and is_default flag

#### Scenario: Field definitions are stored within sets
- **WHEN** a field is added to a set
- **THEN** it SHALL be stored in the ff_set_fields table
- **AND** the field SHALL include code, name, type, sort, validation_rules, validation_messages, and metadata

#### Scenario: Field values are stored polymorphically
- **WHEN** a flexy field value is saved
- **THEN** it SHALL be stored in the ff_values table
- **AND** the value SHALL be associated with the correct model via model_type and model_id
- **AND** the value SHALL be associated with the set via set_code
- **AND** the value SHALL be stored in the appropriate typed column

## MODIFIED Requirements
### Requirement: Database Migration
The migration SHALL create the necessary database tables and pivot view without unnecessary operations or invalid code.

#### Scenario: Migration creates tables cleanly
- **WHEN** the FlexyField migration runs
- **THEN** it SHALL create ff_sets, ff_set_fields, and ff_values tables
- **AND** it SHALL add set_code column to model tables
- **AND** it SHALL create the pivot view
- **AND** it SHALL NOT contain meaningless comments or invalid SQL
- **AND** it SHALL NOT execute transactions without proper begin/commit pairs
- **AND** it SHALL NOT create the ff_shapes table

#### Scenario: Initial view creation is efficient
- **WHEN** the migration creates the initial pivot view
- **THEN** it SHALL create an empty view using dropAndCreatePivotView()
- **AND** it SHALL NOT create dummy data just for view creation
- **AND** it SHALL NOT perform unnecessary database operations

## MODIFIED Requirements
### Requirement: Model Integration
The system SHALL integrate with Laravel Eloquent models through traits and contracts with Sets.

#### Scenario: Models can use Flexy trait
- **WHEN** a model uses the Flexy trait
- **AND** implements FlexyModelContract
- **THEN** the model SHALL have access to flexy field functionality
- **AND** the model SHALL support set assignment via set_code column

#### Scenario: Flexy fields are accessible via magic accessor
- **WHEN** accessing $model->flexy
- **THEN** a Flexy model instance SHALL be returned
- **AND** dynamic field assignment SHALL be enabled via $model->flexy->name
- **AND** only fields from the assigned set SHALL be accessible

## MODIFIED Requirements
### Requirement: Field Value Persistence
The system SHALL persist flexy field values when models are saved, scoped to sets.

#### Scenario: Dirty flexy fields are saved
- **WHEN** a model with dirty flexy fields is saved
- **AND** the model has an assigned set_code
- **THEN** each dirty field SHALL be persisted to ff_values table
- **AND** existing values SHALL be updated via updateOrCreate
- **AND** the set_code SHALL be stored with each value
- **AND** the pivot view SHALL be recreated to include new fields

#### Scenario: Model deletion removes field values
- **WHEN** a model is deleted
- **THEN** all associated flexy field values SHALL be deleted from ff_values
- **AND** the ff_values records SHALL be removed by model_type and model_id

#### Scenario: Set assignment is required
- **WHEN** a model attempts to save flexy field values
- **AND** the model has no set_code assigned
- **THEN** SetNotFoundException SHALL be thrown
- **AND** no values SHALL be persisted

## MODIFIED Requirements
### Requirement: Pivot View for Querying
The system SHALL maintain a database view that pivots EAV data into a queryable format, filtering by set_code when applicable.

#### Scenario: Pivot view includes all fields
- **WHEN** the pivot view is created
- **THEN** it SHALL include columns for all distinct field names
- **AND** each field SHALL be prefixed with flexy_
- **AND** the view SHALL join values from ff_values table using the name column
- **AND** the view SHALL filter by model_type

#### Scenario: View is recreated when schema changes
- **WHEN** new fields are added to any set
- **THEN** the pivot view SHALL be dropped and recreated
- **AND** all existing and new fields SHALL be included in the view

## REMOVED Requirements
### Requirement: Data Migration from Shapes to Field Sets
**Reason**: The legacy shapes system is completely removed. No migration path is needed as shapes have been deprecated and should have been migrated already.

**Migration**: Users with existing shapes should manually migrate data before upgrading, or use a custom migration script if needed.

## MODIFIED Requirements
### Requirement: Foreign Key Cascading
The system SHALL handle cascading deletions when sets or fields are removed.

#### Scenario: Deleting set cascades to fields
- **WHEN** a set is deleted
- **THEN** all SetField records for the set SHALL be cascade deleted via foreign key
- **AND** no orphaned field records SHALL remain

#### Scenario: Deleting set nullifies model references
- **WHEN** a set is deleted
- **THEN** all model instances with set_code SHALL have it set to NULL via foreign key
- **AND** accessing flexy fields on these instances SHALL throw SetNotFoundException

