# Query Integration

## Purpose
FlexyField integrates dynamic fields with Laravel's Eloquent query builder, enabling filtering, ordering, and searching on flexy fields as if they were native database columns. With field sets, queries can filter by field set context.

## MODIFIED Requirements

### Requirement: Query Scope Integration
The system SHALL provide automatic query scopes for flexy fields with field set context.

#### Scenario: Global scope joins pivot view with set context
- **WHEN** a model using Flexy trait is queried
- **THEN** a global scope SHALL left join ff_values_pivot_view
- **AND** the join SHALL filter by model_type
- **AND** the join SHALL include model_id matching
- **AND** flexy fields SHALL be queryable using flexy_ prefix

#### Scenario: Queries include field set context
- **WHEN** a query is executed on a model with flexy fields
- **THEN** results SHALL include models regardless of field_set_code
- **AND** flexy field values SHALL be scoped to each instance's field set
- **AND** cross-set field queries SHALL work correctly

### Requirement: Dynamic Where Clauses
The system SHALL support querying flexy fields using standard Eloquent where methods.

#### Scenario: Where clause on flexy field
- **WHEN** where('flexy_fieldname', 'value') is called
- **THEN** the query SHALL filter by the flexy_fieldname column in the pivot view
- **AND** the results SHALL include only models matching the condition
- **AND** models from different field sets with the same field SHALL be included

#### Scenario: Dynamic where method on flexy field
- **WHEN** whereFlexyFieldname('value') is called
- **THEN** the query SHALL filter by flexy_fieldname
- **AND** the results SHALL include only models matching the condition

#### Scenario: Complex where conditions on flexy fields
- **WHEN** multiple where clauses are chained on flexy fields
- **THEN** all conditions SHALL be applied
- **AND** the query SHALL combine conditions correctly (AND/OR logic)

### Requirement: Ordering by Flexy Fields
The system SHALL support ordering query results by flexy field values.

#### Scenario: Order by flexy field
- **WHEN** orderBy('flexy_fieldname', 'asc') is called
- **THEN** the query results SHALL be ordered by flexy_fieldname values
- **AND** null values SHALL be handled according to database default behavior
- **AND** ordering SHALL work across different field sets

#### Scenario: Multiple order by clauses
- **WHEN** multiple orderBy() calls include flexy fields
- **THEN** all ordering SHALL be applied in sequence
- **AND** flexy and native fields MAY be mixed in ordering

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
- **WHEN** with('attributeSet') is called on a model query
- **THEN** the FieldSet relation SHALL be eager loaded
- **AND** the field set's fields SHALL be available
- **AND** no additional queries SHALL be executed per model
