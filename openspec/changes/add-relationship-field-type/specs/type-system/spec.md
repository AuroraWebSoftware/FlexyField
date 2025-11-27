## ADDED Requirements

### Requirement: Relationship Field Type
The system SHALL support storing references to other Eloquent models as a flexy field type, enabling one-to-one and one-to-many relationship patterns.

#### Scenario: Relationship type is supported
- **WHEN** an Eloquent model instance is assigned to a flexy field
- **THEN** it SHALL be stored as RELATIONSHIP type
- **AND** the related model's class name SHALL be stored in value_related_model_type
- **AND** the related model's ID SHALL be stored in value_related_model_id
- **AND** the value SHALL be retrieved as the Eloquent model instance

#### Scenario: Relationship type detection
- **WHEN** a model instance (e.g., `$category`) is assigned to a flexy field
- **THEN** the system SHALL automatically detect it as RELATIONSHIP type
- **AND** it SHALL extract the model's class name and ID
- **AND** it SHALL store both values in the relationship columns

#### Scenario: Relationship field requires configuration
- **WHEN** a relationship field is added to a field set
- **THEN** it SHALL require related_model metadata (fully qualified class name)
- **AND** it SHALL optionally specify relationship_type ('one-to-one' or 'one-to-many')
- **AND** it SHALL optionally specify cascade_delete behavior

#### Scenario: Invalid model assignment throws exception
- **WHEN** a non-Eloquent object is assigned to a relationship field
- **THEN** it SHALL throw an appropriate exception
- **AND** the exception message SHALL indicate the expected type

#### Scenario: Null relationship values are handled
- **WHEN** null is assigned to a relationship field
- **THEN** both value_related_model_type and value_related_model_id SHALL be NULL
- **AND** the value SHALL be retrieved as null

## MODIFIED Requirements

### Requirement: Type Detection and Storage
The system SHALL correctly detect and store PHP values in their appropriate typed columns, maintaining type fidelity through the save/retrieve cycle. The system SHALL also detect Eloquent model instances and store them as relationship references.

#### Scenario: Boolean false is stored and retrieved correctly
- **WHEN** a flexy field is set to boolean false
- **THEN** it SHALL be stored in value_boolean column
- **AND** it SHALL be retrieved as boolean false (not integer 0)

#### Scenario: Boolean true is stored correctly
- **WHEN** a flexy field is set to boolean true
- **THEN** it SHALL be stored in value_boolean column
- **AND** it SHALL be retrieved as boolean true (not integer 1)

#### Scenario: Integer zero is stored correctly
- **WHEN** a flexy field is set to integer 0
- **THEN** it SHALL be stored in value_int column
- **AND** it SHALL be retrieved as integer 0 (not boolean false)

#### Scenario: Float values are stored as decimal
- **WHEN** a flexy field is set to a float value (e.g., 19.99)
- **THEN** it SHALL be stored in value_decimal column
- **AND** it SHALL maintain precision when retrieved

#### Scenario: Numeric strings preserve leading zeros
- **WHEN** a flexy field is set to a numeric string with leading zeros (e.g., "00123")
- **THEN** it SHALL be stored in value_string column
- **AND** it SHALL preserve the exact string value including leading zeros

#### Scenario: Eloquent model instances are stored as relationships
- **WHEN** a flexy field is set to an Eloquent model instance
- **THEN** it SHALL be stored in value_related_model_type and value_related_model_id columns
- **AND** it SHALL be retrieved as the same model instance (or null if deleted)
- **AND** the model class SHALL match the field's related_model configuration

#### Scenario: Unsupported types throw exception
- **WHEN** a flexy field is set to an unsupported type (e.g., resource, closure)
- **THEN** it SHALL throw FlexyFieldTypeNotAllowedException
- **AND** the exception message SHALL include the actual type name

#### Scenario: Null values are handled correctly
- **WHEN** a flexy field is set to null
- **THEN** all value_* columns SHALL be NULL
- **AND** the value SHALL be retrieved as null

### Requirement: Type Safety in Storage
The system SHALL ensure that only one typed column is populated per value record. For relationship fields, the relationship columns are considered as a single unit.

#### Scenario: Only one typed column is populated
- **WHEN** a value is stored
- **THEN** exactly one value_* column set SHALL contain data
- **AND** all other typed columns SHALL be NULL
- **AND** for relationship values, both value_related_model_type and value_related_model_id SHALL be populated together

#### Scenario: String values default to value_string
- **WHEN** type cannot be determined definitively
- **THEN** the value SHALL be stored as a string
- **AND** it SHALL use the value_string column

