# Query Integration

## Purpose
FlexyField seamlessly integrates with Laravel's Eloquent query builder, allowing developers to query models by their flexy field values using standard Eloquent methods. This is achieved through a database pivot view and global scopes that automatically join the view to model queries.
## Requirements
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

### Requirement: Pivot View Management
The system SHALL maintain a database view that makes flexy fields queryable across all schemas.

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
- **WHEN** a field is added to any schema
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

### Requirement: Schema Filtering
The system SHALL allow querying models by their assigned schema.

#### Scenario: Filter by schema code
- **WHEN** whereSchema('schema_code') is called
- **THEN** the query SHALL filter by schema_code column
- **AND** only models assigned to the specified schema SHALL be returned

#### Scenario: Filter by multiple schemas
- **WHEN** whereInSchema(['schema1', 'schema2']) is called
- **THEN** the query SHALL filter by schema_code using IN clause
- **AND** models assigned to any of the specified schemas SHALL be returned

#### Scenario: Query models without schema
- **WHEN** whereDoesntHaveSchema() is called
- **THEN** the query SHALL filter for null schema_code
- **AND** only unassigned models SHALL be returned

### Requirement: Cross-Schema Queries
The system SHALL handle queries that span multiple schemas.

#### Scenario: Query field present in multiple schemas
- **WHEN** where('flexy_color', 'red') is called
- **AND** 'color' field exists in multiple schemas
- **THEN** the query SHALL return models from all schemas containing the field
- **AND** models SHALL match regardless of their schema_code

#### Scenario: Query field not in model's schema returns correctly
- **WHEN** where('flexy_fieldname', 'value') is called
- **AND** the queried models are assigned to a schema without that field
- **THEN** those models SHALL NOT be returned
- **AND** only models with the field in their schema SHALL match

### Requirement: Eager Loading with Schemas
The system SHALL support efficient eager loading of flexy fields with schema context.

#### Scenario: Flexy fields are eager loaded with schema context
- **WHEN** a collection of models is loaded
- **THEN** flexy field values SHALL be eager loaded from ff_field_values
- **AND** the eager load SHALL filter by model_type and model_id
- **AND** N+1 query problems SHALL be avoided

#### Scenario: Schema definitions are eager loadable
- **WHEN** with('schema') is called on a model query
- **THEN** the FieldSchema relation SHALL be eager loaded
- **AND** the schema's fields SHALL be available
- **AND** no additional queries SHALL be executed per model

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

#### Scenario: Cross-schema queries work correctly
- **WHEN** multiple schemas have fields with the same name
- **AND** a query filters by that field name
- **THEN** results SHALL include models from all schemas with matching values

#### Scenario: Order by flexy field works correctly
- **WHEN** a query uses orderBy('flexy_fieldname', 'asc')
- **THEN** results SHALL be ordered by the flexy field value
- **AND** ordering SHALL work across different schemas

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

#### Scenario: Cross-schema queries work correctly
- **WHEN** multiple schemas have fields with the same name
- **AND** a query filters by that field name
- **THEN** results SHALL include models from all schemas with matching values

#### Scenario: Order by flexy field works correctly
- **WHEN** a query uses orderBy('flexy_fieldname', 'asc')
- **THEN** results SHALL be ordered by the flexy field value
- **AND** ordering SHALL work across different schemas

