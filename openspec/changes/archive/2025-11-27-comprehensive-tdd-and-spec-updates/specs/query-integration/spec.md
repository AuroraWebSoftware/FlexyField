## ADDED Requirements

### Requirement: Query Edge Cases
The system SHALL handle query edge cases correctly.

#### Scenario: Query non-existent field returns empty or null
- **WHEN** a query uses where('flexy_nonexistent', 'value')
- **THEN** the query SHALL execute without error
- **AND** results SHALL be empty (no matches)

#### Scenario: Query with null values
- **WHEN** a query uses whereNull('flexy_fieldname')
- **THEN** the query SHALL filter models where the field is NULL
- **AND** results SHALL include models without that field value

#### Scenario: Cross-field-set queries work correctly
- **WHEN** multiple field sets have fields with the same name
- **AND** a query filters by that field name
- **THEN** results SHALL include models from all field sets with matching values

#### Scenario: Order by flexy field works correctly
- **WHEN** a query uses orderBy('flexy_fieldname', 'asc')
- **THEN** results SHALL be ordered by the flexy field value
- **AND** ordering SHALL work across different field sets

## MODIFIED Requirements

### Requirement: Eloquent Query Builder Integration
The system SHALL enable querying models by flexy field values using Eloquent's where methods, with support for field set filtering.

#### Scenario: Query by flexy field using where clause
- **WHEN** a query uses where('flexy_fieldname', 'value')
- **THEN** the query SHALL filter models by that flexy field value
- **AND** results SHALL only include models where the field matches the value
- **AND** the query SHALL work across all field sets

#### Scenario: Query by flexy field using dynamic where methods
- **WHEN** a query uses whereFlexyFieldname('value')
- **THEN** the query SHALL filter models by that flexy field value
- **AND** results SHALL match the where('flexy_fieldname', 'value') behavior

#### Scenario: Query with comparison operators
- **WHEN** a query uses where('flexy_price', '>', 100)
- **THEN** the query SHALL filter models using the comparison operator
- **AND** numeric comparisons SHALL work correctly on typed columns

#### Scenario: Query by field set code
- **WHEN** a query uses whereFieldSet('set_code')
- **THEN** the query SHALL filter models by field_set_code
- **AND** results SHALL only include models assigned to that field set

#### Scenario: Query by multiple field sets
- **WHEN** a query uses whereFieldSetIn(['set1', 'set2'])
- **THEN** the query SHALL filter models by multiple field_set_code values
- **AND** results SHALL include models from any of the specified sets

#### Scenario: Query models without field set
- **WHEN** a query uses whereFieldSetNull()
- **THEN** the query SHALL filter models where field_set_code is NULL
- **AND** results SHALL only include unassigned models

### Requirement: Global Scope for View Joining
The system SHALL automatically join the pivot view to model queries via a global scope, filtering by model_type.

#### Scenario: Pivot view is joined automatically
- **WHEN** a model query is executed
- **AND** the model uses the Flexy trait
- **THEN** the ff_values_pivot_view SHALL be left joined automatically
- **AND** all flexy fields SHALL be accessible as $model->flexy_fieldname

#### Scenario: View join uses model type filtering
- **WHEN** the pivot view is joined
- **THEN** it SHALL filter by the model's model_type
- **AND** only values for the queried model type SHALL be included

#### Scenario: Global scope can be disabled
- **WHEN** a query uses withoutGlobalScope('flexy')
- **THEN** the pivot view SHALL NOT be joined
- **AND** flexy fields SHALL NOT be accessible via attributes

### Requirement: Field Value Retrieval
The system SHALL provide multiple ways to access flexy field values, scoped to the assigned field set.

#### Scenario: Values accessible via flexy magic accessor
- **WHEN** accessing $model->flexy->field_name
- **THEN** the value SHALL be retrieved from the ff_values table
- **AND** the value SHALL be filtered by the model's field_set_code
- **AND** the correct typed value SHALL be returned

#### Scenario: Values accessible via model attributes
- **WHEN** accessing $model->flexy_field_name
- **THEN** the value SHALL be retrieved from the joined pivot view
- **AND** the value type SHALL match the storage type

#### Scenario: All flexy fields are loaded with model
- **WHEN** a model is loaded from the database
- **THEN** all flexy field values for the assigned field set SHALL be joined via the pivot view
- **AND** no additional queries SHALL be needed to access flexy fields

### Requirement: Pivot View Management
The system SHALL maintain a database view that makes flexy fields queryable across all field sets.

#### Scenario: View creation generates columns for all fields
- **WHEN** dropAndCreatePivotView() is called
- **THEN** the view SHALL be dropped if it exists
- **AND** a new view SHALL be created with columns for all distinct field names
- **AND** each field column SHALL be prefixed with flexy_

#### Scenario: View handles multiple model types
- **WHEN** the pivot view is created
- **THEN** it SHALL include values for all model types
- **AND** filtering by model_type SHALL separate model-specific fields

#### Scenario: View columns use correct field names
- **WHEN** a field is added to any field set
- **THEN** the view SHALL include a column named flexy_{field_name}
- **AND** the column SHALL aggregate values using MAX() function
- **AND** NULL values SHALL be used for models without that field

### Requirement: Database Compatibility
The system SHALL support both MySQL and PostgreSQL for view creation.

#### Scenario: MySQL view syntax is used
- **WHEN** the database connection is MySQL
- **THEN** MySQL-specific view creation syntax SHALL be used
- **AND** the view SHALL use CONCAT() for column aliasing

#### Scenario: PostgreSQL view syntax is used
- **WHEN** the database connection is PostgreSQL
- **THEN** PostgreSQL-specific view creation syntax SHALL be used
- **AND** the view SHALL use || operator for string concatenation

#### Scenario: View recreation is idempotent
- **WHEN** dropAndCreatePivotView() is called multiple times
- **THEN** the view SHALL be dropped and recreated each time
- **AND** no errors SHALL occur from attempting to drop non-existent views

### Requirement: Batch Operation Support
The system SHALL provide an API for batch operations that defers view recreation until completion.

#### Scenario: Batch mode defers view updates
- **WHEN** withoutViewUpdates() closure is executed
- **THEN** view recreation SHALL be disabled during closure execution
- **AND** view SHALL be recreated once after closure completes
- **AND** errors SHALL re-enable updates automatically

#### Scenario: Batch mode handles errors gracefully
- **WHEN** an exception occurs inside withoutViewUpdates() closure
- **THEN** view updates SHALL be re-enabled
- **AND** the exception SHALL be re-thrown
- **AND** the system SHALL remain in a consistent state

