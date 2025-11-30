## Context
FlexyField currently supports dynamic fields with metadata storage, but lacks standardized UI hints for better user experience. Field names are often technical (e.g., `battery_capacity_mah`) and not user-friendly. Users need guidance, placeholders, and readable labels in the UI to enter data correctly.

The existing `metadata` JSON column in `ff_schema_fields` table provides a natural place to store UI hints without requiring database schema changes.

## Goals / Non-Goals
- Goals:
  - Provide standardized UI hints (label, placeholder, hint) for fields
  - Make field definitions more user-friendly
  - Maintain backward compatibility with existing metadata
  - Add helper methods to SchemaField model for easy access

- Non-Goals:
  - Create a new UI framework or frontend components
  - Change the underlying database schema
  - Implement complex UI rendering logic

## Decisions
- Decision: Use the existing `metadata` JSON column in `ff_schema_fields` table
  - Rationale: No database changes needed, maintains backward compatibility
  - Alternatives considered: New dedicated columns (rejected due to schema changes)

- Decision: Standardize on three UI hint keys: `label`, `placeholder`, `hint`
  - Rationale: Covers the most common UI guidance needs
  - Alternatives considered: More extensive UI metadata (rejected for simplicity)

- Decision: Add helper methods to SchemaField model
  - Rationale: Provides convenient access to UI hints
  - Alternatives considered: Static utility functions (rejected for OOP consistency)

## Risks / Trade-offs
- Risk: Metadata key collisions with existing implementations
  - Mitigation: Use descriptive key names and document them clearly
  
- Trade-off: Limited to three UI hint types
  - Rationale: Keeps implementation simple while covering most use cases
  - Future extension: Additional keys can be added as needed

## Migration Plan
1. Add helper methods to SchemaField model
2. Update documentation with examples of UI hints usage
3. No database migration needed (uses existing metadata column)
4. Backward compatibility maintained - existing fields without UI hints continue to work

## Open Questions
- Should we add validation for UI hint values (e.g., max length for labels)?
- Should we provide default values when UI hints are not specified?
