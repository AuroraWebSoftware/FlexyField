
## MODIFIED Requirements
### Requirement: Schema Creation and Management
The system SHALL allow creating and managing schemas for model types.

#### Scenario: Schema is created for a model type
- **WHEN** createSchema() is called with schema_code, label, and model_type
- **THEN** a FieldSchema record SHALL be created in ff_schemas table
- **AND** the schema SHALL be scoped to the specified model_type
- **AND** the schema_code SHALL be unique within the model_type
- **AND** the schema SHALL include label, description, and metadata fields

#### Scenario: Default schema is designated
- **WHEN** a schema is created with is_default = true
- **THEN** the schema SHALL be marked as the default schema for the model type
- **AND** new model instances SHALL automatically be assigned to this schema
- **AND** only one schema per model_type SHALL be marked as default

#### Scenario: Schema is retrieved
- **WHEN** getSchema() is called with a schema_code
- **THEN** the corresponding FieldSchema SHALL be returned
- **AND** the schema SHALL include all metadata and configuration

#### Scenario: All schemas for a model are listed
- **WHEN** getAllSchemas() is called on a model
- **THEN** all FieldSchema records for that model_type SHALL be returned
- **AND** schemas SHALL be ordered by label

#### Scenario: Schema is deleted
- **WHEN** deleteSchema() is called with a schema_code
- **AND** no model instances are assigned to the schema
- **THEN** the FieldSchema record SHALL be deleted
- **AND** all associated SchemaField records SHALL be cascade deleted via foreign key

#### Scenario: Schema deletion is prevented when in use
- **WHEN** deleteSchema() is called for a schema
- **AND** model instances exist with schema_code referencing the schema
- **THEN** SchemaInUseException SHALL be thrown
- **AND** the schema SHALL NOT be deleted

## MODIFIED Requirements
### Requirement: Field Management Within Schemas
The system SHALL allow adding and managing fields within schemas.

#### Scenario: Field is added to schema
- **WHEN** addFieldToSchema() is called with schema_code, name, and type
- **THEN** a SchemaField record SHALL be created in ff_schema_fields
- **AND** the field SHALL be scoped to the specified schema_code
- **AND** the field's schema_id SHALL reference the parent schema's id via FK
- **AND** name SHALL be unique within the schema
- **AND** the field SHALL include validation_rules, sort order, and metadata

#### Scenario: Field is removed from schema
- **WHEN** removeFieldFromSchema() is called with schema_code and name
- **THEN** the SchemaField record SHALL be deleted
- **AND** existing FieldValue records for that field SHALL remain in ff_field_values
- **AND** future assignments to that field SHALL be rejected for instances using the schema

#### Scenario: All fields for a schema are retrieved
- **WHEN** getFieldsForSchema() is called with a schema_code
- **THEN** all SchemaField records for the schema SHALL be returned
- **AND** fields SHALL be ordered by sort order

## MODIFIED Requirements
### Requirement: Model Instance Assignment
The system SHALL allow assigning model instances to specific schemas.

#### Scenario: Model instance is assigned to schema
- **WHEN** assignToSchema() is called on a model instance with schema_code
- **THEN** the instance's schema_code column SHALL be set to the schema_code
- **AND** the instance's schema_id column SHALL be set to the schema's id
- **AND** the schema_code SHALL reference a valid FieldSchema for the model_type
- **AND** only fields from the assigned schema SHALL be accessible

#### Scenario: Model instance schema is retrieved
- **WHEN** getSchemaCode() is called on a model instance
- **THEN** the current schema_code value SHALL be returned
- **AND** null SHALL be returned if no schema is assigned

#### Scenario: Available fields are retrieved for instance
- **WHEN** getAvailableFields() is called on a model instance
- **THEN** all SchemaField records for the instance's schema_code SHALL be returned
- **AND** fields SHALL be ordered by sort order

## MODIFIED Requirements
### Requirement: Schema Metadata
The system SHALL support rich metadata for schemas.

#### Scenario: Schema has metadata
- **WHEN** a schema is created with metadata array
- **THEN** the metadata SHALL be stored as JSON
- **AND** metadata MAY include icon, color, description, or custom properties
- **AND** metadata SHALL be retrievable with the schema

#### Scenario: Field has metadata
- **WHEN** a field is added with metadata array
- **THEN** the metadata SHALL be stored as JSON in the metadata column (not field_metadata)
- **AND** metadata MAY include help text, placeholder, or custom properties
- **AND** metadata SHALL be retrievable with the field definition

## MODIFIED Requirements
### Requirement: Foreign Key Integrity
The system SHALL maintain referential integrity using proper foreign key constraints.

#### Scenario: Schema field references schema via FK
- **WHEN** a SchemaField is created
- **THEN** its schema_id SHALL reference a valid ff_schemas.id via FK constraint
- **AND** deleting the parent schema SHALL cascade delete all related SchemaField records
- **AND** orphan SchemaField records SHALL NOT exist in the database

#### Scenario: Field value references schema via FK
- **WHEN** a FieldValue is created with a schema
- **THEN** its schema_id SHALL reference a valid ff_schemas.id via FK constraint (nullable)
- **AND** deleting the schema SHALL set schema_id to NULL in related FieldValue records
- **AND** the FieldValue SHALL remain in the database (not cascade deleted)

## MODIFIED Requirements
### Requirement: Edge Case Handling
The system SHALL handle edge cases gracefully with appropriate errors and warnings.

#### Scenario: Concurrent schema creation with same code
- **WHEN** two users create schemas with identical model_type and schema_code simultaneously
- **THEN** the database unique constraint SHALL prevent duplicates
- **AND** the second request SHALL receive a unique constraint violation error

#### Scenario: Delete schema that is in use
- **WHEN** deleteSchema() is called for a schema
- **AND** model instances reference the schema via schema_code
- **THEN** SchemaInUseException SHALL be thrown
- **AND** the exception message SHALL include the count of instances using the schema

#### Scenario: Assign non-existent schema to model
- **WHEN** assignToSchema() is called with a non-existent schema_code
- **THEN** a database foreign key constraint violation SHALL occur
- **AND** the model's schema_code SHALL NOT be updated

#### Scenario: Assign schema from different model type
- **WHEN** assignToSchema() is called with a schema_code
- **AND** the schema belongs to a different model_type
- **THEN** SchemaNotFoundException SHALL be thrown
- **AND** the exception message SHALL indicate model type mismatch

#### Scenario: Change schema after setting field values
- **WHEN** a model has field values in one schema
- **AND** the model is assigned to a different schema
- **THEN** the old field values SHALL remain in ff_field_values but become inaccessible
- **AND** only fields from the new schema SHALL be accessible

#### Scenario: Metadata contains invalid JSON
- **WHEN** createSchema() is called with metadata that cannot be JSON encoded
- **THEN** an exception SHALL be thrown before database storage
- **AND** no schema record SHALL be created

#### Scenario: Delete default schema
- **WHEN** deleteSchema() is called for a schema where is_default=true
- **AND** no model instances reference the schema
- **THEN** the schema SHALL be deleted successfully
- **AND** the model_type SHALL have no default schema
- **AND** new model instances SHALL have null schema_code
