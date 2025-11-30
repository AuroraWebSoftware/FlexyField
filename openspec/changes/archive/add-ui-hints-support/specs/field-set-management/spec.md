## ADDED Requirements
### Requirement: UI Hints for Fields
The system SHALL support UI hints for fields to provide better user experience in forms and interfaces.

#### Scenario: Field has label UI hint
- **WHEN** a field is created with metadata containing 'label' key
- **THEN** the label SHALL be stored in the metadata JSON column
- **AND** the label SHALL be retrievable via getLabel() method
- **AND** if no label is provided, the field name SHALL be used as fallback

#### Scenario: Field has placeholder UI hint
- **WHEN** a field is created with metadata containing 'placeholder' key
- **THEN** the placeholder text SHALL be stored in the metadata JSON column
- **AND** the placeholder SHALL be retrievable via getPlaceholder() method
- **AND** null SHALL be returned if no placeholder is provided

#### Scenario: Field has hint UI hint
- **WHEN** a field is created with metadata containing 'hint' key
- **THEN** the hint text SHALL be stored in the metadata JSON column
- **AND** the hint SHALL be retrievable via getHint() method
- **AND** null SHALL be returned if no hint is provided

#### Scenario: Field has multiple UI hints
- **WHEN** a field is created with metadata containing 'label', 'placeholder', and 'hint' keys
- **THEN** all UI hints SHALL be stored in the metadata JSON column
- **AND** each UI hint SHALL be retrievable via its respective method
- **AND** existing metadata SHALL be preserved alongside UI hints

## MODIFIED Requirements
### Requirement: Field has metadata
The system SHALL support rich metadata for fields, including UI hints for better user experience.

#### Scenario: Field is added with metadata array
- **WHEN** a field is added with metadata array
- **THEN** the metadata SHALL be stored as JSON in the metadata column
- **AND** metadata MAY include UI hints (label, placeholder, hint), help text, or custom properties
- **AND** metadata SHALL be retrievable with the field definition
- **AND** UI hints SHALL be accessible via dedicated getter methods
