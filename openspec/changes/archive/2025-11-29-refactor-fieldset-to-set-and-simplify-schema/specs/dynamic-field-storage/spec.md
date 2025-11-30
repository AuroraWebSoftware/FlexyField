## MODIFIED Requirements
### Requirement: EAV Storage Tables
The system SHALL provide database tables for storing field definitions and values using an EAV pattern with Schemas.

#### Scenario: Schema definitions are stored
- **WHEN** a schema is created for a model
- **THEN** it SHALL be stored in the ff_schemas table
- **AND** the schema SHALL include model_type, schema_code, label, description, metadata, and is_default flag

#### Scenario: Field definitions are stored within schemas
- **WHEN** a field is added to a schema
- **THEN** it SHALL be stored in the ff_schema_fields table
- **AND** the field SHALL include schema_code, schema_id (FK), name, type, sort, validation_rules, validation_messages, and metadata
- **AND** the schema_id SHALL reference ff_schemas.id via foreign key constraint

#### Scenario: Field values are stored polymorphically
- **WHEN** a flexy field value is saved
- **THEN** it SHALL be stored in the ff_field_values table
- **AND** the value SHALL be associated with the correct model via model_type and model_id
- **AND** the value SHALL be associated with the schema via schema_code and schema_id
- **AND** the value SHALL be stored in the appropriate typed column (value_string, value_int, etc.)

## MODIFIED Requirements
### Requirement: Database Migration
The migration SHALL create the necessary database tables and pivot view with proper foreign key constraints.

#### Scenario: Migration creates tables cleanly
- **WHEN** the FlexyField migration runs
- **THEN** it SHALL create ff_schemas, ff_schema_fields, and ff_field_values tables
- **AND** it SHALL add schema_code column to model tables
- **AND** it SHALL create proper foreign key constraints (schema_id → id)
- **AND** it SHALL create the pivot view
- **AND** it SHALL NOT contain meaningless comments or invalid SQL
- **AND** it SHALL NOT execute transactions without proper begin/commit pairs
- **AND** it SHALL NOT create the ff_shapes table

#### Scenario: Initial view creation is efficient
- **WHEN** the migration creates the initial pivot view
- **THEN** it SHALL create an empty view using dropAndCreatePivotView()
- **AND** it SHALL use the ff_field_values table name (not ff_values)
- **AND** it SHALL use the name column (not field_name)
- **AND** it SHALL NOT create dummy data just for view creation
- **AND** it SHALL NOT perform unnecessary database operations

## MODIFIED Requirements
### Requirement: Model Integration
The system SHALL integrate with Laravel Eloquent models through traits and contracts with Schemas.

#### Scenario: Models can use Flexy trait
- **WHEN** a model uses the Flexy trait
- **AND** implements FlexyModelContract
- **THEN** the model SHALL have access to flexy field functionality
- **AND** the model SHALL support schema assignment via schema_code column

#### Scenario: Flexy fields are accessible via magic accessor
- **WHEN** accessing $model->flexy
- **THEN** a Flexy model instance SHALL be returned
- **AND** dynamic field assignment SHALL be enabled via $model->flexy->name
- **AND** only fields from the assigned schema SHALL be accessible

## MODIFIED Requirements
### Requirement: Field Value Persistence
The system SHALL persist flexy field values when models are saved, scoped to schemas.

#### Scenario: Dirty flexy fields are saved
- **WHEN** a model with dirty flexy fields is saved
- **AND** the model has an assigned schema_code
- **THEN** each dirty field SHALL be persisted to ff_field_values table
- **AND** existing values SHALL be updated via updateOrCreate
- **AND** both schema_code and schema_id SHALL be stored with each value
- **AND** the pivot view SHALL be recreated to include new fields

#### Scenario: Model deletion removes field values
- **WHEN** a model is deleted
- **THEN** all associated flexy field values SHALL be deleted from ff_field_values
- **AND** the ff_field_values records SHALL be removed by model_type and model_id

#### Scenario: Schema assignment is required
- **WHEN** a model attempts to save flexy field values
- **AND** the model has no schema_code assigned
- **THEN** SchemaNotFoundException SHALL be thrown
- **AND** no values SHALL be persisted

## MODIFIED Requirements
### Requirement: Pivot View for Querying
The system SHALL maintain a database view that pivots EAV data into a queryable format, filtering by schema_code when applicable.

#### Scenario: Pivot view includes all fields
- **WHEN** the pivot view is created
- **THEN** it SHALL include columns for all distinct field names from ff_field_values
- **AND** each field SHALL be prefixed with flexy_
- **AND** the view SHALL join values from ff_field_values table using the name column
- **AND** the view SHALL filter by model_type

#### Scenario: View is recreated when schema changes
- **WHEN** new fields are added to any schema
- **THEN** the pivot view SHALL be dropped and recreated
- **AND** all existing and new fields SHALL be included in the view



## MODIFIED Requirements
### Requirement: Foreign Key Cascading
The system SHALL handle cascading deletions and nullifications using proper database foreign key constraints.

#### Scenario: Deleting schema cascades to schema fields
- **WHEN** a schema is deleted from ff_schemas
- **THEN** all SchemaField records SHALL be cascade deleted via FK constraint on schema_id
- **AND** no orphaned field records SHALL remain in ff_schema_fields

#### Scenario: Deleting schema nullifies field value references
- **WHEN** a schema is deleted from ff_schemas
- **THEN** all FieldValue records SHALL have schema_id set to NULL via FK constraint
- **AND** schema_code SHALL remain for reference but schema relationship will be broken
- **AND** accessing flexy fields on these instances SHALL handle null schema gracefully

#### Scenario: FK constraints prevent invalid references
- **WHEN** attempting to create a SchemaField with non-existent schema_id
- **THEN** the database SHALL reject the insert via FK constraint
- **AND** an appropriate database error SHALL be raised
