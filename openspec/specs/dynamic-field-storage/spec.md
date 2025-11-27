# Dynamic Field Storage

## Purpose
FlexyField implements an Entity-Attribute-Value (EAV) pattern to enable dynamic field management for Laravel Eloquent models without requiring database schema modifications. This capability handles the storage, retrieval, and management of flexible field values using a polymorphic storage system.

## Requirements

### Requirement: EAV Storage Tables
The system SHALL provide database tables for storing field definitions and values using an EAV pattern.

#### Scenario: Shape definitions are stored
- **WHEN** a shape is defined for a model
- **THEN** it SHALL be stored in the ff_shapes table
- **AND** the shape SHALL include model_type, field_name, field_type, sort_order, validation_rule, and validation_messages

#### Scenario: Field values are stored polymorphically
- **WHEN** a flexy field value is saved
- **THEN** it SHALL be stored in the ff_values table
- **AND** the value SHALL be associated with the correct model via model_type and model_id
- **AND** the value SHALL be stored in the appropriate typed column

### Requirement: Database Migration
The system SHALL provide migrations to create necessary database tables and views.

#### Scenario: Migration creates required tables
- **WHEN** migrations are executed
- **THEN** ff_shapes table SHALL be created with appropriate schema
- **AND** ff_values table SHALL be created with polymorphic relationship columns
- **AND** ff_values_pivot_view SHALL be created for efficient querying

#### Scenario: Migration is reversible
- **WHEN** migration rollback is executed
- **THEN** all FlexyField tables and views SHALL be dropped
- **AND** the database SHALL return to its previous state

### Requirement: Model Integration
The system SHALL integrate with Laravel Eloquent models through traits and contracts.

#### Scenario: Models can use Flexy trait
- **WHEN** a model uses the Flexy trait
- **AND** implements FlexyModelContract
- **THEN** the model SHALL have access to flexy field functionality
- **AND** the model SHALL support dynamic field assignment

#### Scenario: Flexy fields are accessible via magic accessor
- **WHEN** accessing $model->flexy
- **THEN** a Flexy model instance SHALL be returned
- **AND** dynamic field assignment SHALL be enabled via $model->flexy->field_name

### Requirement: Field Value Persistence
The system SHALL persist flexy field values when models are saved.

#### Scenario: Dirty flexy fields are saved
- **WHEN** a model with dirty flexy fields is saved
- **THEN** each dirty field SHALL be persisted to ff_values table
- **AND** existing values SHALL be updated via updateOrCreate
- **AND** the pivot view SHALL be recreated to include new fields

#### Scenario: Model deletion removes field values
- **WHEN** a model is deleted
- **THEN** all associated flexy field values SHALL be deleted from ff_values
- **AND** the ff_values records SHALL be removed by model_type and model_id

### Requirement: Pivot View for Querying
The system SHALL maintain a database view that pivots EAV data into a queryable format.

#### Scenario: Pivot view includes all fields
- **WHEN** the pivot view is created
- **THEN** it SHALL include columns for all distinct field names
- **AND** each field SHALL be prefixed with flexy_
- **AND** the view SHALL join values from ff_values table

#### Scenario: View is recreated when schema changes
- **WHEN** new fields are added to any model
- **THEN** the pivot view SHALL be dropped and recreated
- **AND** all existing and new fields SHALL be included in the view
