## MODIFIED Requirements

### Requirement: EAV Storage Tables
The system SHALL provide database tables for storing field definitions and values using an EAV pattern with Field Sets.

#### Scenario: Field set definitions are stored
- **WHEN** a field set is created for a model
- **THEN** it SHALL be stored in the ff_field_sets table
- **AND** the field set SHALL include model_type, set_code, label, description, metadata, and is_default flag

#### Scenario: Field definitions are stored within sets
- **WHEN** a field is added to a field set
- **THEN** it SHALL be stored in the ff_set_fields table
- **AND** the field SHALL include set_code, field_name, field_type, sort, validation_rules, validation_messages, and field_metadata

#### Scenario: Field values are stored polymorphically
- **WHEN** a flexy field value is saved
- **THEN** it SHALL be stored in the ff_values table
- **AND** the value SHALL be associated with the correct model via model_type and model_id
- **AND** the value SHALL be associated with the field set via field_set_code
- **AND** the value SHALL be stored in the appropriate typed column

### Requirement: Database Migration
The migration SHALL create the necessary database tables and pivot view without unnecessary operations or invalid code.

#### Scenario: Migration creates tables cleanly
- **WHEN** the FlexyField migration runs
- **THEN** it SHALL create ff_field_sets, ff_set_fields, and ff_values tables
- **AND** it SHALL add field_set_code column to model tables
- **AND** it SHALL create the pivot view
- **AND** it SHALL NOT contain meaningless comments or invalid SQL
- **AND** it SHALL NOT execute transactions without proper begin/commit pairs

#### Scenario: Initial view creation is efficient
- **WHEN** the migration creates the initial pivot view
- **THEN** it SHALL create an empty view using dropAndCreatePivotView()
- **AND** it SHALL NOT create dummy data just for view creation
- **AND** it SHALL NOT perform unnecessary database operations

### Requirement: Model Integration
The system SHALL integrate with Laravel Eloquent models through traits and contracts with Field Sets.

#### Scenario: Models can use Flexy trait
- **WHEN** a model uses the Flexy trait
- **AND** implements FlexyModelContract
- **THEN** the model SHALL have access to flexy field functionality
- **AND** the model SHALL support field set assignment via field_set_code column

#### Scenario: Flexy fields are accessible via magic accessor
- **WHEN** accessing $model->flexy
- **THEN** a Flexy model instance SHALL be returned
- **AND** dynamic field assignment SHALL be enabled via $model->flexy->field_name
- **AND** only fields from the assigned field set SHALL be accessible

### Requirement: Field Value Persistence
The system SHALL persist flexy field values when models are saved, scoped to field sets.

#### Scenario: Dirty flexy fields are saved
- **WHEN** a model with dirty flexy fields is saved
- **AND** the model has an assigned field_set_code
- **THEN** each dirty field SHALL be persisted to ff_values table
- **AND** existing values SHALL be updated via updateOrCreate
- **AND** the field_set_code SHALL be stored with each value
- **AND** the pivot view SHALL be recreated to include new fields

#### Scenario: Model deletion removes field values
- **WHEN** a model is deleted
- **THEN** all associated flexy field values SHALL be deleted from ff_values
- **AND** the ff_values records SHALL be removed by model_type and model_id

#### Scenario: Field set assignment is required
- **WHEN** a model attempts to save flexy field values
- **AND** the model has no field_set_code assigned
- **THEN** FieldSetNotFoundException SHALL be thrown
- **AND** no values SHALL be persisted

### Requirement: Pivot View for Querying
The system SHALL maintain a database view that pivots EAV data into a queryable format, filtering by field_set_code when applicable.

#### Scenario: Pivot view includes all fields
- **WHEN** the pivot view is created
- **THEN** it SHALL include columns for all distinct field names
- **AND** each field SHALL be prefixed with flexy_
- **AND** the view SHALL join values from ff_values table
- **AND** the view SHALL filter by model_type

#### Scenario: View is recreated when schema changes
- **WHEN** new fields are added to any field set
- **THEN** the pivot view SHALL be dropped and recreated
- **AND** all existing and new fields SHALL be included in the view

