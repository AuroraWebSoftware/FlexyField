# Query Integration Spec Changes

## ADDED Requirements

### Requirement: View Schema Tracking
The system SHALL track which fields are currently included in the pivot view using a dedicated schema tracking table.

#### Scenario: Schema tracking table stores field names
- **WHEN** the system is initialized
- **THEN** the ff_view_schema table SHALL exist
- **AND** it SHALL have columns: id, field_name (unique), added_at

#### Scenario: New fields are added to schema tracking
- **WHEN** a new flexy field is saved for the first time
- **THEN** the field name SHALL be inserted into ff_view_schema
- **AND** the added_at timestamp SHALL be recorded

### Requirement: Conditional View Recreation
The system SHALL only recreate the pivot view when the schema changes (new fields added), not when values change.

#### Scenario: View is NOT recreated for value updates
- **WHEN** a model is saved with changes to existing flexy fields
- **THEN** the system SHALL check if the fields exist in ff_view_schema
- **AND** if all fields already exist, view recreation SHALL be skipped

#### Scenario: View IS recreated for new fields
- **WHEN** a model is saved with a new flexy field not in ff_view_schema
- **THEN** the system SHALL add the new field to ff_view_schema
- **AND** the system SHALL recreate the pivot view
- **AND** the new field SHALL be available for querying

#### Scenario: Multiple value updates avoid redundant recreation
- **WHEN** 1000 models are updated with the same field
- **THEN** the view SHALL be recreated at most once (for the first save)
- **AND** subsequent 999 saves SHALL NOT trigger recreation

### Requirement: Manual View Maintenance
The system SHALL provide mechanisms for manual view recreation and schema synchronization.

#### Scenario: Force rebuild command recreates view
- **WHEN** the flexyfield:rebuild-view command is executed
- **THEN** the ff_view_schema table SHALL be rebuilt from actual ff_values data
- **AND** the pivot view SHALL be recreated
- **AND** success confirmation SHALL be displayed

#### Scenario: Force rebuild synchronizes out-of-sync schema
- **WHEN** ff_view_schema is out of sync with actual fields
- **AND** forceRecreateView() is called
- **THEN** all existing field names SHALL be read from ff_values
- **AND** ff_view_schema SHALL be repopulated accurately
- **AND** the view SHALL reflect all actual fields

## ADDED Requirements

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
