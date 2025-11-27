## RENAMED Requirements
- FROM: `### Requirement: Field Set Creation and Management`
- TO: `### Requirement: Set Creation and Management`

## MODIFIED Requirements
### Requirement: Set Creation and Management
The system SHALL allow creating and managing sets for model types.

#### Scenario: Set is created for a model type
- **WHEN** createSet() is called with code, label, and model_type
- **THEN** a Set record SHALL be created in ff_sets table
- **AND** the set SHALL be scoped to the specified model_type
- **AND** the code SHALL be unique within the model_type
- **AND** the set SHALL include label, description, and metadata fields

#### Scenario: Default set is designated
- **WHEN** a set is created with is_default = true
- **THEN** the set SHALL be marked as the default set for the model type
- **AND** new model instances SHALL automatically be assigned to this set
- **AND** only one set per model_type SHALL be marked as default

#### Scenario: Set is retrieved
- **WHEN** getSet() is called with a code
- **THEN** the corresponding Set SHALL be returned
- **AND** the set SHALL include all metadata and configuration

#### Scenario: All sets for a model are listed
- **WHEN** getAllSets() is called on a model
- **THEN** all Set records for that model_type SHALL be returned
- **AND** sets SHALL be ordered by label

#### Scenario: Set is deleted
- **WHEN** deleteSet() is called with a code
- **AND** no model instances are assigned to the set
- **THEN** the Set record SHALL be deleted
- **AND** all associated fields SHALL be cascade deleted via foreign key

#### Scenario: Set deletion is prevented when in use
- **WHEN** deleteSet() is called for a set
- **AND** model instances exist with set_code referencing the set
- **THEN** SetInUseException SHALL be thrown
- **AND** the set SHALL NOT be deleted

## MODIFIED Requirements
### Requirement: Field Management Within Sets
The system SHALL allow adding and managing fields within sets.

#### Scenario: Field is added to set
- **WHEN** addFieldToSet() is called with code, name, and type
- **THEN** a SetField record SHALL be created in ff_set_fields
- **AND** the field SHALL be scoped to the specified code
- **AND** name SHALL be unique within the set
- **AND** the field SHALL include validation_rules, sort order, and metadata

#### Scenario: Field is removed from set
- **WHEN** removeFieldFromSet() is called with code and name
- **THEN** the SetField record SHALL be deleted
- **AND** existing values for that field SHALL remain in ff_values
- **AND** future assignments to that field SHALL be rejected for instances using the set

#### Scenario: All fields for a set are retrieved
- **WHEN** getFieldsForSet() is called with a code
- **THEN** all SetField records for the set SHALL be returned
- **AND** fields SHALL be ordered by sort order

## MODIFIED Requirements
### Requirement: Model Instance Assignment
The system SHALL allow assigning model instances to specific sets.

#### Scenario: Model instance is assigned to set
- **WHEN** assignToSet() is called on a model instance with code
- **THEN** the instance's set_code column SHALL be set to the code
- **AND** the code SHALL reference a valid Set for the model_type
- **AND** only fields from the assigned set SHALL be accessible

#### Scenario: Model instance set is retrieved
- **WHEN** getSetCode() is called on a model instance
- **THEN** the current set_code value SHALL be returned
- **AND** null SHALL be returned if no set is assigned

#### Scenario: Available fields are retrieved for instance
- **WHEN** getAvailableFields() is called on a model instance
- **THEN** all SetField records for the instance's set_code SHALL be returned
- **AND** fields SHALL be ordered by sort order

## MODIFIED Requirements
### Requirement: Set Metadata
The system SHALL support rich metadata for sets.

#### Scenario: Set has metadata
- **WHEN** a set is created with metadata array
- **THEN** the metadata SHALL be stored as JSON
- **AND** metadata MAY include icon, color, description, or custom properties
- **AND** metadata SHALL be retrievable with the set

#### Scenario: Field has metadata
- **WHEN** a field is added with metadata array
- **THEN** the metadata SHALL be stored as JSON
- **AND** metadata MAY include help text, placeholder, or custom properties
- **AND** metadata SHALL be retrievable with the field definition

## MODIFIED Requirements
### Requirement: Edge Case Handling
The system SHALL handle edge cases gracefully with appropriate errors and warnings.

#### Scenario: Concurrent set creation with same code
- **WHEN** two users create sets with identical model_type and code simultaneously
- **THEN** the database unique constraint SHALL prevent duplicates
- **AND** the second request SHALL receive a unique constraint violation error

#### Scenario: Delete set that is in use
- **WHEN** deleteSet() is called for a set
- **AND** model instances reference the set via set_code
- **THEN** SetInUseException SHALL be thrown
- **AND** the exception message SHALL include the count of instances using the set

#### Scenario: Assign non-existent set to model
- **WHEN** assignToSet() is called with a non-existent code
- **THEN** a database foreign key constraint violation SHALL occur
- **AND** the model's set_code SHALL NOT be updated

#### Scenario: Assign set from different model type
- **WHEN** assignToSet() is called with a code
- **AND** the set belongs to a different model_type
- **THEN** SetNotFoundException SHALL be thrown
- **AND** the exception message SHALL indicate model type mismatch

#### Scenario: Change set after setting field values
- **WHEN** a model has field values in one set
- **AND** the model is assigned to a different set
- **THEN** the old field values SHALL remain in ff_values but become inaccessible
- **AND** only fields from the new set SHALL be accessible

#### Scenario: Metadata contains invalid JSON
- **WHEN** createSet() is called with metadata that cannot be JSON encoded
- **THEN** an exception SHALL be thrown before database storage
- **AND** no set record SHALL be created

#### Scenario: Delete default set
- **WHEN** deleteSet() is called for a set where is_default=true
- **AND** no model instances reference the set
- **THEN** the set SHALL be deleted successfully
- **AND** the model_type SHALL have no default set
- **AND** new model instances SHALL have null set_code

