# Query Integration

## Purpose
FlexyField seamlessly integrates with Laravel's Eloquent query builder, allowing developers to query models by their flexy field values using standard Eloquent methods. This is achieved through a database pivot view and global scopes that automatically join the view to model queries.

## Requirements

### Requirement: Eloquent Query Builder Integration
The system SHALL enable querying models by flexy field values using Eloquent's where methods.

#### Scenario: Query by flexy field using where clause
- **WHEN** a query uses where('flexy_fieldname', 'value')
- **THEN** the query SHALL filter models by that flexy field value
- **AND** results SHALL only include models where the field matches the value

#### Scenario: Query by flexy field using dynamic where methods
- **WHEN** a query uses whereFlexyFieldname('value')
- **THEN** the query SHALL filter models by that flexy field value
- **AND** results SHALL match the where('flexy_fieldname', 'value') behavior

#### Scenario: Query with comparison operators
- **WHEN** a query uses where('flexy_price', '>', 100)
- **THEN** the query SHALL filter models using the comparison operator
- **AND** numeric comparisons SHALL work correctly on typed columns

### Requirement: Global Scope for View Joining
The system SHALL automatically join the pivot view to model queries via a global scope.

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
The system SHALL provide multiple ways to access flexy field values.

#### Scenario: Values accessible via flexy magic accessor
- **WHEN** accessing $model->flexy->field_name
- **THEN** the value SHALL be retrieved from the ff_values table
- **AND** the correct typed value SHALL be returned

#### Scenario: Values accessible via model attributes
- **WHEN** accessing $model->flexy_field_name
- **THEN** the value SHALL be retrieved from the joined pivot view
- **AND** the value type SHALL match the storage type

#### Scenario: All flexy fields are loaded with model
- **WHEN** a model is loaded from the database
- **THEN** all flexy field values SHALL be joined via the pivot view
- **AND** no additional queries SHALL be needed to access flexy fields

### Requirement: Pivot View Management
The system SHALL maintain a database view that makes flexy fields queryable.

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
- **WHEN** a field is added to any model
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
