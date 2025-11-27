# Attribute Set Management

## Purpose
Provides a flexible attribute set system that allows different instances of the same model to use different field configurations. Similar to Magento's attribute sets, this enables rich product catalogs where shoes, books, and electronics can each have their own specialized fields while sharing the same model class.

## ADDED Requirements

### Requirement: Attribute Set Creation and Management
The system SHALL allow creating and managing attribute sets for model types.

#### Scenario: Attribute set is created for a model type
- **WHEN** createAttributeSet() is called with set_code, label, and model_type
- **THEN** an AttributeSet record SHALL be created in ff_attribute_sets table
- **AND** the set SHALL be scoped to the specified model_type
- **AND** the set_code SHALL be unique within the model_type
- **AND** the set SHALL include label, description, and metadata fields

#### Scenario: Default attribute set is designated
- **WHEN** an attribute set is created with is_default = true
- **THEN** the set SHALL be marked as the default set for the model type
- **AND** new model instances SHALL automatically be assigned to this set
- **AND** only one set per model_type SHALL be marked as default

#### Scenario: Attribute set is retrieved
- **WHEN** getAttributeSet() is called with a set_code
- **THEN** the corresponding AttributeSet SHALL be returned
- **AND** the set SHALL include all metadata and configuration

#### Scenario: All attribute sets for a model are listed
- **WHEN** getAllAttributeSets() is called on a model
- **THEN** all AttributeSet records for that model_type SHALL be returned
- **AND** sets SHALL be ordered by label

#### Scenario: Attribute set is deleted
- **WHEN** deleteAttributeSet() is called with a set_code
- **AND** no model instances are assigned to the set
- **THEN** the AttributeSet record SHALL be deleted
- **AND** all associated fields SHALL be cascade deleted via foreign key

#### Scenario: Attribute set deletion is prevented when in use
- **WHEN** deleteAttributeSet() is called for a set
- **AND** model instances exist with attribute_set_code referencing the set
- **THEN** AttributeSetInUseException SHALL be thrown
- **AND** the set SHALL NOT be deleted

### Requirement: Field Management Within Sets
The system SHALL allow adding and managing fields within attribute sets.

#### Scenario: Field is added to attribute set
- **WHEN** addFieldToSet() is called with set_code, field_name, and field_type
- **THEN** an AttributeSetField record SHALL be created in ff_attribute_set_fields
- **AND** the field SHALL be scoped to the specified set_code
- **AND** field_name SHALL be unique within the set
- **AND** the field SHALL include validation_rules, sort order, and field_metadata

#### Scenario: Field is removed from attribute set
- **WHEN** removeFieldFromSet() is called with set_code and field_name
- **THEN** the AttributeSetField record SHALL be deleted
- **AND** existing values for that field SHALL remain in ff_values
- **AND** future assignments to that field SHALL be rejected for instances using the set

#### Scenario: All fields for a set are retrieved
- **WHEN** getFieldsForSet() is called with a set_code
- **THEN** all AttributeSetField records for the set SHALL be returned
- **AND** fields SHALL be ordered by sort order

### Requirement: Model Instance Assignment
The system SHALL allow assigning model instances to specific attribute sets.

#### Scenario: Model instance is assigned to attribute set
- **WHEN** assignToAttributeSet() is called on a model instance with set_code
- **THEN** the instance's attribute_set_code column SHALL be set to the set_code
- **AND** the set_code SHALL reference a valid AttributeSet for the model_type
- **AND** only fields from the assigned set SHALL be accessible

#### Scenario: Model instance attribute set is retrieved
- **WHEN** getAttributeSetCode() is called on a model instance
- **THEN** the current attribute_set_code value SHALL be returned
- **AND** null SHALL be returned if no set is assigned

#### Scenario: Available fields are retrieved for instance
- **WHEN** getAvailableFields() is called on a model instance
- **THEN** all AttributeSetField records for the instance's attribute_set_code SHALL be returned
- **AND** fields SHALL be ordered by sort order

### Requirement: Attribute Set Metadata
The system SHALL support rich metadata for attribute sets.

#### Scenario: Attribute set has metadata
- **WHEN** an attribute set is created with metadata array
- **THEN** the metadata SHALL be stored as JSON
- **AND** metadata MAY include icon, color, description, or custom properties
- **AND** metadata SHALL be retrievable with the attribute set

#### Scenario: Field has metadata
- **WHEN** a field is added with field_metadata array
- **THEN** the metadata SHALL be stored as JSON
- **AND** metadata MAY include help text, placeholder, or custom properties
- **AND** metadata SHALL be retrievable with the field definition
