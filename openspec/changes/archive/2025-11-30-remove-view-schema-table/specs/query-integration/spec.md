## MODIFIED Requirements

### Requirement: Pivot View Management
The system SHALL maintain a database view that makes flexy fields queryable across all field sets. The system SHALL detect new fields by reading view columns from database metadata (information_schema) instead of maintaining a separate tracking table.

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

#### Scenario: View recreation detects new fields from metadata
- **WHEN** recreateViewIfNeeded() is called with field names
- **THEN** the system SHALL read existing view columns from database metadata (information_schema)
- **AND** the system SHALL compare new fields against existing columns
- **AND** the view SHALL only be recreated if new fields are detected
- **AND** the system SHALL extract field names from column names (removing flexy_ prefix)

#### Scenario: View metadata reading handles non-existent view
- **WHEN** the pivot view does not exist yet
- **THEN** metadata reading SHALL return empty array
- **AND** view creation SHALL proceed normally

#### Scenario: View metadata reading works on MySQL
- **WHEN** the database connection is MySQL
- **THEN** metadata SHALL be read from INFORMATION_SCHEMA.COLUMNS
- **AND** the query SHALL filter by current database and view name
- **AND** field names SHALL be extracted correctly

#### Scenario: View metadata reading works on PostgreSQL
- **WHEN** the database connection is PostgreSQL
- **THEN** metadata SHALL be read from information_schema.columns
- **AND** the query SHALL filter by current schema and view name
- **AND** field names SHALL be extracted correctly

