## ADDED Requirements

### Requirement: Relationship Value Storage
The system SHALL store relationship references in dedicated columns, enabling flexy fields to reference other Eloquent models.

#### Scenario: Relationship values are stored in dedicated columns
- **WHEN** a relationship value is saved
- **THEN** it SHALL be stored in value_related_model_type column (model class name)
- **AND** it SHALL be stored in value_related_model_id column (model ID)
- **AND** all other value_* columns SHALL be NULL
- **AND** the value SHALL be associated with the correct model via model_type and model_id

#### Scenario: Relationship retrieval returns model instance
- **WHEN** a relationship field value is retrieved
- **THEN** it SHALL return the Eloquent model instance
- **AND** the model SHALL be loaded from the database using stored type and ID
- **AND** if the related model is deleted, it SHALL return null

#### Scenario: Relationship validation ensures model exists
- **WHEN** a relationship value is saved
- **AND** the field set defines a related_model
- **THEN** the system SHALL validate that the assigned model class matches related_model
- **AND** the system SHALL validate that the referenced model exists in the database
- **AND** if validation fails, an exception SHALL be thrown

#### Scenario: Cascade delete removes relationship values
- **WHEN** a related model is deleted
- **AND** a relationship field has cascade_delete enabled in metadata
- **THEN** all flexy field values referencing that model SHALL be deleted
- **AND** the cascade SHALL only affect fields with cascade_delete enabled

#### Scenario: Relationship metadata is stored in field set
- **WHEN** a relationship field is added to a field set
- **THEN** the field_metadata SHALL include related_model (fully qualified class name)
- **AND** the field_metadata MAY include relationship_type ('one-to-one' or 'one-to-many')
- **AND** the field_metadata MAY include cascade_delete (boolean)

## MODIFIED Requirements

### Requirement: Field Value Persistence
The system SHALL persist flexy field values when models are saved, scoped to field sets. The system SHALL handle both primitive values and relationship references.

#### Scenario: Dirty flexy fields are saved
- **WHEN** a model with dirty flexy fields is saved
- **AND** the model has an assigned field_set_code
- **THEN** each dirty field SHALL be persisted to ff_values table
- **AND** primitive values SHALL be stored in appropriate typed columns
- **AND** relationship values SHALL be stored in value_related_model_type and value_related_model_id
- **AND** existing values SHALL be updated via updateOrCreate
- **AND** the field_set_code SHALL be stored with each value
- **AND** the pivot view SHALL be recreated to include new fields

#### Scenario: Model deletion removes field values
- **WHEN** a model is deleted
- **THEN** all associated flexy field values SHALL be deleted from ff_values
- **AND** the ff_values records SHALL be removed by model_type and model_id
- **AND** cascade delete SHALL be triggered for relationship fields with cascade_delete enabled

#### Scenario: Field set assignment is required
- **WHEN** a model attempts to save flexy field values
- **AND** the model has no field_set_code assigned
- **THEN** FieldSetNotFoundException SHALL be thrown
- **AND** no values SHALL be persisted

### Requirement: Database Migration
The migration SHALL create the necessary database tables and pivot view without unnecessary operations or invalid code. The migration SHALL include relationship storage columns.

#### Scenario: Migration creates tables cleanly
- **WHEN** the FlexyField migration runs
- **THEN** it SHALL create ff_field_sets, ff_set_fields, and ff_values tables
- **AND** it SHALL add field_set_code column to model tables
- **AND** it SHALL add value_related_model_type and value_related_model_id columns to ff_values
- **AND** it SHALL create index on (value_related_model_type, value_related_model_id)
- **AND** it SHALL create the pivot view
- **AND** it SHALL NOT contain meaningless comments or invalid SQL
- **AND** it SHALL NOT execute transactions without proper begin/commit pairs

#### Scenario: Initial view creation is efficient
- **WHEN** the migration creates the initial pivot view
- **THEN** it SHALL create an empty view using dropAndCreatePivotView()
- **AND** it SHALL NOT create dummy data just for view creation
- **AND** it SHALL NOT perform unnecessary database operations

