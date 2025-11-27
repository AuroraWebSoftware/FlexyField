# Query Integration

## Purpose
FlexyField integrates dynamic fields with Laravel's Eloquent query builder, enabling filtering, ordering, and searching on flexy fields as if they were native database columns. With field sets, queries can filter by field set context.

## MODIFIED Requirements

### Requirement: Eloquent Query Builder Integration
The system SHALL enable querying models by flexy field values using Eloquent's where methods, with support for field set context.

#### Scenario: Query by flexy field using where clause
- **WHEN** a query uses where('flexy_fieldname', 'value')
- **THEN** the query SHALL filter models by that flexy field value
- **AND** results SHALL only include models where the field matches the value
- **AND** models from different field sets with the same field SHALL be included

#### Scenario: Query by flexy field using dynamic where methods
- **WHEN** a query uses whereFlexyFieldname('value')
- **THEN** the query SHALL filter models by that flexy field value
- **AND** results SHALL match the where('flexy_fieldname', 'value') behavior

#### Scenario: Query with comparison operators
- **WHEN** a query uses where('flexy_price', '>', 100)
- **THEN** the query SHALL filter models using the comparison operator
- **AND** numeric comparisons SHALL work correctly on typed columns

### Requirement: Global Scope for View Joining
The system SHALL automatically join the pivot view to model queries via a global scope, with field set context support.

#### Scenario: Pivot view is joined automatically
- **WHEN** a model query is executed
- **AND** the model uses the Flexy trait
- **THEN** the ff_values_pivot_view SHALL be left joined automatically
- **AND** all flexy fields SHALL be accessible as $model->flexy_fieldname
- **AND** queries SHALL include models regardless of field_set_code

#### Scenario: View join uses model type filtering
- **WHEN** the pivot view is joined
- **THEN** it SHALL filter by the model's model_type
- **AND** only values for the queried model type SHALL be included
- **AND** flexy field values SHALL be scoped to each instance's field set

#### Scenario: Global scope can be disabled
- **WHEN** a query uses withoutGlobalScope('flexy')
- **THEN** the pivot view SHALL NOT be joined
- **AND** flexy fields SHALL NOT be accessible via attributes

## ADDED Requirements

### Requirement: Field Set Filtering
The system SHALL allow querying models by their assigned field set.

#### Scenario: Filter by field set code
- **WHEN** whereFieldSet('set_code') is called
- **THEN** the query SHALL filter by field_set_code column
- **AND** only models assigned to the specified set SHALL be returned

#### Scenario: Filter by multiple field sets
- **WHEN** whereFieldSetIn(['set1', 'set2']) is called
- **THEN** the query SHALL filter by field_set_code using IN clause
- **AND** models assigned to any of the specified sets SHALL be returned

#### Scenario: Query models without field set
- **WHEN** whereFieldSetNull() is called
- **THEN** the query SHALL filter for null field_set_code
- **AND** only unassigned models SHALL be returned

### Requirement: Cross-Field-Set Queries
The system SHALL handle queries that span multiple field sets.

#### Scenario: Query field present in multiple sets
- **WHEN** where('flexy_color', 'red') is called
- **AND** 'color' field exists in multiple field sets
- **THEN** the query SHALL return models from all sets containing the field
- **AND** models SHALL match regardless of their field_set_code

#### Scenario: Query field not in model's set returns correctly
- **WHEN** where('flexy_fieldname', 'value') is called
- **AND** the queried models are assigned to a set without that field
- **THEN** those models SHALL NOT be returned
- **AND** only models with the field in their set SHALL match

### Requirement: Eager Loading with Field Sets
The system SHALL support efficient eager loading of flexy fields with field set context.

#### Scenario: Flexy fields are eager loaded with set context
- **WHEN** a collection of models is loaded
- **THEN** flexy field values SHALL be eager loaded from ff_values
- **AND** the eager load SHALL filter by model_type and model_id
- **AND** N+1 query problems SHALL be avoided

#### Scenario: Field set definitions are eager loadable
- **WHEN** with('fieldSet') is called on a model query
- **THEN** the FieldSet relation SHALL be eager loaded
- **AND** the field set's fields SHALL be available
- **AND** no additional queries SHALL be executed per model
