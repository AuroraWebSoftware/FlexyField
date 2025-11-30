## MODIFIED Requirements
### Requirement: Eloquent Query Builder Integration
The system SHALL enable querying models by flexy field values using Eloquent's where methods, with support for schema filtering.

#### Scenario: Query by flexy field using where clause
- **WHEN** a query uses where('flexy_fieldname', 'value')
- **THEN** the query SHALL filter models by that flexy field value
- **AND** results SHALL only include models where the field matches the value
- **AND** the query SHALL work across all schemas

#### Scenario: Query by flexy field using dynamic where methods
- **WHEN** a query uses whereFlexyFieldname('value')
- **THEN** the query SHALL filter models by that flexy field value
- **AND** results SHALL match the where('flexy_fieldname', 'value') behavior

#### Scenario: Query with comparison operators
- **WHEN** a query uses where('flexy_price', '>', 100)
- **THEN** the query SHALL filter models using the comparison operator
- **AND** numeric comparisons SHALL work correctly on typed columns

#### Scenario: Query by schema code
- **WHEN** a query uses whereSchema('schema_code')
- **THEN** the query SHALL filter models by schema_code
- **AND** results SHALL only include models assigned to that schema

#### Scenario: Query by multiple schemas
- **WHEN** a query uses whereInSchema(['schema1', 'schema2'])
- **THEN** the query SHALL filter models by multiple schema_code values
- **AND** results SHALL include models from any of the specified schemas

#### Scenario: Query models without schema
- **WHEN** a query uses whereDoesntHaveSchema()
- **THEN** the query SHALL filter models where schema_code is NULL
- **AND** results SHALL only include unassigned models

## MODIFIED Requirements
### Requirement: Field Value Retrieval
The system SHALL provide multiple ways to access flexy field values, scoped to the assigned schema.

#### Scenario: Values accessible via flexy magic accessor
- **WHEN** accessing $model->flexy->name
- **THEN** the value SHALL be retrieved from the ff_field_values table
- **AND** the value SHALL be filtered by the model's schema_code
- **AND** the correct typed value SHALL be returned

#### Scenario: Values accessible via model attributes
- **WHEN** accessing $model->flexy_name
- **THEN** the value SHALL be retrieved from the joined pivot view
- **AND** the value type SHALL match the storage type

#### Scenario: All flexy fields are loaded with model
- **WHEN** a model is loaded from the database
- **THEN** all flexy field values for the assigned schema SHALL be joined via the pivot view
- **AND** no additional queries SHALL be needed to access flexy fields
