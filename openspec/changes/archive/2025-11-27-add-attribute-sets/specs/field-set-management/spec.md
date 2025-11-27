# Field Set Management

## Purpose
Provides a flexible field set system that allows different instances of the same model to use different field configurations. Similar to Magento's field sets, this enables rich product catalogs where shoes, books, and electronics can each have their own specialized fields while sharing the same model class.

## ADDED Requirements

### Requirement: Field Set Creation and Management
The system SHALL allow creating and managing field sets for model types.

#### Scenario: Field set is created for a model type
- **WHEN** createFieldSet() is called with set_code, label, and model_type
- **THEN** a FieldSet record SHALL be created in ff_field_sets table
- **AND** the set SHALL be scoped to the specified model_type
- **AND** the set_code SHALL be unique within the model_type
- **AND** the set SHALL include label, description, and metadata fields

#### Scenario: Default field set is designated
- **WHEN** a field set is created with is_default = true
- **THEN** the set SHALL be marked as the default set for the model type
- **AND** new model instances SHALL automatically be assigned to this set
- **AND** only one set per model_type SHALL be marked as default

#### Scenario: Field set is retrieved
- **WHEN** getFieldSet() is called with a set_code
- **THEN** the corresponding FieldSet SHALL be returned
- **AND** the set SHALL include all metadata and configuration

#### Scenario: All field sets for a model are listed
- **WHEN** getAllFieldSets() is called on a model
- **THEN** all FieldSet records for that model_type SHALL be returned
- **AND** sets SHALL be ordered by label

#### Scenario: Field set is deleted
- **WHEN** deleteFieldSet() is called with a set_code
- **AND** no model instances are assigned to the set
- **THEN** the FieldSet record SHALL be deleted
- **AND** all associated fields SHALL be cascade deleted via foreign key

#### Scenario: Field set deletion is prevented when in use
- **WHEN** deleteFieldSet() is called for a set
- **AND** model instances exist with field_set_code referencing the set
- **THEN** FieldSetInUseException SHALL be thrown
- **AND** the set SHALL NOT be deleted

### Requirement: Field Management Within Sets
The system SHALL allow adding and managing fields within field sets.

#### Scenario: Field is added to field set
- **WHEN** addFieldToSet() is called with set_code, field_name, and field_type
- **THEN** a SetField record SHALL be created in ff_set_fields
- **AND** the field SHALL be scoped to the specified set_code
- **AND** field_name SHALL be unique within the set
- **AND** the field SHALL include validation_rules, sort order, and field_metadata

#### Scenario: Field is removed from field set
- **WHEN** removeFieldFromSet() is called with set_code and field_name
- **THEN** the SetField record SHALL be deleted
- **AND** existing values for that field SHALL remain in ff_values
- **AND** future assignments to that field SHALL be rejected for instances using the set

#### Scenario: All fields for a set are retrieved
- **WHEN** getFieldsForSet() is called with a set_code
- **THEN** all SetField records for the set SHALL be returned
- **AND** fields SHALL be ordered by sort order

### Requirement: Model Instance Assignment
The system SHALL allow assigning model instances to specific field sets.

#### Scenario: Model instance is assigned to field set
- **WHEN** assignToFieldSet() is called on a model instance with set_code
- **THEN** the instance's field_set_code column SHALL be set to the set_code
- **AND** the set_code SHALL reference a valid FieldSet for the model_type
- **AND** only fields from the assigned set SHALL be accessible

#### Scenario: Model instance field set is retrieved
- **WHEN** getFieldSetCode() is called on a model instance
- **THEN** the current field_set_code value SHALL be returned
- **AND** null SHALL be returned if no set is assigned

#### Scenario: Available fields are retrieved for instance
- **WHEN** getAvailableFields() is called on a model instance
- **THEN** all SetField records for the instance's field_set_code SHALL be returned
- **AND** fields SHALL be ordered by sort order

### Requirement: Field Set Metadata
The system SHALL support rich metadata for field sets.

#### Scenario: Field set has metadata
- **WHEN** a field set is created with metadata array
- **THEN** the metadata SHALL be stored as JSON
- **AND** metadata MAY include icon, color, description, or custom properties
- **AND** metadata SHALL be retrievable with the field set

#### Scenario: Field has metadata
- **WHEN** a field is added with field_metadata array
- **THEN** the metadata SHALL be stored as JSON
- **AND** metadata MAY include help text, placeholder, or custom properties
- **AND** metadata SHALL be retrievable with the field definition

### Requirement: Edge Case Handling
The system SHALL handle edge cases gracefully with appropriate errors and warnings.

#### Scenario: Concurrent field set creation with same set_code
- **WHEN** two users create field sets with identical model_type and set_code simultaneously
- **THEN** the database unique constraint SHALL prevent duplicates
- **AND** the second request SHALL receive a unique constraint violation error

#### Scenario: Delete field set that is in use
- **WHEN** deleteFieldSet() is called for a set
- **AND** model instances reference the set via field_set_code
- **THEN** FieldSetInUseException SHALL be thrown
- **AND** the exception message SHALL include the count of instances using the set

#### Scenario: Assign non-existent field set to model
- **WHEN** assignToFieldSet() is called with a non-existent set_code
- **THEN** a database foreign key constraint violation SHALL occur
- **AND** the model's field_set_code SHALL NOT be updated

#### Scenario: Assign field set from different model type
- **WHEN** assignToFieldSet() is called with a set_code
- **AND** the set belongs to a different model_type
- **THEN** FieldSetNotFoundException SHALL be thrown
- **AND** the exception message SHALL indicate model type mismatch

#### Scenario: Change field set after setting field values
- **WHEN** a model has field values in one field set
- **AND** the model is assigned to a different field set
- **THEN** the old field values SHALL remain in ff_values but become inaccessible
- **AND** only fields from the new field set SHALL be accessible

#### Scenario: Metadata contains invalid JSON
- **WHEN** createFieldSet() is called with metadata that cannot be JSON encoded
- **THEN** an exception SHALL be thrown before database storage
- **AND** no field set record SHALL be created

#### Scenario: Delete default field set
- **WHEN** deleteFieldSet() is called for a set where is_default=true
- **AND** no model instances reference the set
- **THEN** the set SHALL be deleted successfully
- **AND** the model_type SHALL have no default field set
- **AND** new model instances SHALL have null field_set_code
