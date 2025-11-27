## ADDED Requirements

### Requirement: Relationship Field Querying
The system SHALL support querying models by their relationship field values, enabling filtering by related model IDs and types.

#### Scenario: Query by related model ID
- **WHEN** a query uses where('flexy_category_id', $categoryId)
- **THEN** the query SHALL filter models where the relationship field references that model ID
- **AND** results SHALL only include models with matching relationship values
- **AND** the query SHALL work across all field sets

#### Scenario: Query by related model instance
- **WHEN** a query uses where('flexy_category_id', $category->id)
- **THEN** the query SHALL filter models by the model's ID
- **AND** it SHALL work the same as querying by ID directly

#### Scenario: Query by related model type
- **WHEN** a query uses a scope to filter by related model type
- **THEN** the query SHALL filter models where relationship fields reference models of that type
- **AND** results SHALL include models with any relationship field referencing the specified type

#### Scenario: Query relationship field with null values
- **WHEN** a query uses whereNull('flexy_category_id')
- **THEN** the query SHALL filter models where the relationship field is NULL
- **AND** results SHALL include models without that relationship value

#### Scenario: Query relationship field with multiple IDs
- **WHEN** a query uses whereIn('flexy_category_id', [$id1, $id2])
- **THEN** the query SHALL filter models where relationship field references any of the specified IDs
- **AND** results SHALL include models matching any of the IDs

## MODIFIED Requirements

### Requirement: Pivot View for Querying
The system SHALL maintain a database view that pivots EAV data into a queryable format, filtering by field_set_code when applicable. The view SHALL include relationship fields for querying.

#### Scenario: Pivot view includes all fields
- **WHEN** the pivot view is created
- **THEN** it SHALL include columns for all distinct field names
- **AND** each field SHALL be prefixed with flexy_
- **AND** relationship fields SHALL be included as flexy_{field_name}_id columns
- **AND** the view SHALL join values from ff_values table
- **AND** the view SHALL filter by model_type

#### Scenario: View is recreated when schema changes
- **WHEN** new fields are added to any field set
- **THEN** the pivot view SHALL be dropped and recreated
- **AND** all existing and new fields SHALL be included in the view
- **AND** relationship fields SHALL be included with _id suffix for querying

### Requirement: Field Value Retrieval
The system SHALL provide multiple ways to access flexy field values, scoped to the assigned field set. Relationship fields SHALL return model instances.

#### Scenario: Values accessible via flexy magic accessor
- **WHEN** accessing $model->flexy->field_name
- **THEN** the value SHALL be retrieved from the ff_values table
- **AND** primitive values SHALL be returned as their native types
- **AND** relationship values SHALL be returned as Eloquent model instances
- **AND** the value SHALL be filtered by the model's field_set_code
- **AND** the correct typed value SHALL be returned

#### Scenario: Values accessible via model attributes
- **WHEN** accessing $model->flexy_field_name
- **THEN** the value SHALL be retrieved from the joined pivot view
- **AND** primitive values SHALL match the storage type
- **AND** relationship values SHALL be accessible as flexy_{field_name}_id (the ID only)

#### Scenario: All flexy fields are loaded with model
- **WHEN** a model is loaded from the database
- **THEN** all flexy field values for the assigned field set SHALL be joined via the pivot view
- **AND** relationship field IDs SHALL be available via pivot view columns
- **AND** relationship model instances SHALL be lazy-loaded when accessed via flexy accessor
- **AND** no additional queries SHALL be needed to access flexy field IDs

### Requirement: Pivot View Management
The system SHALL maintain a database view that makes flexy fields queryable across all field sets. The view SHALL handle relationship fields appropriately.

#### Scenario: View creation generates columns for all fields
- **WHEN** dropAndCreatePivotView() is called
- **THEN** the view SHALL be dropped if it exists
- **AND** a new view SHALL be created with columns for all distinct field names
- **AND** each primitive field column SHALL be prefixed with flexy_
- **AND** each relationship field SHALL have a flexy_{field_name}_id column for querying
- **AND** relationship columns SHALL use value_related_model_id from ff_values

#### Scenario: View handles multiple model types
- **WHEN** the pivot view is created
- **THEN** it SHALL include values for all model types
- **AND** filtering by model_type SHALL separate model-specific fields
- **AND** relationship fields SHALL work across all model types

#### Scenario: View columns use correct field names
- **WHEN** a field is added to any field set
- **THEN** the view SHALL include a column named flexy_{field_name} for primitive fields
- **AND** the view SHALL include a column named flexy_{field_name}_id for relationship fields
- **AND** the column SHALL aggregate values using MAX() function
- **AND** NULL values SHALL be used for models without that field

