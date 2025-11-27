# Change: Add Relationship Field Type

## Why
Currently, FlexyField supports storing primitive values (strings, integers, decimals, dates, booleans, JSON) but cannot store references to other Eloquent models. This limitation prevents use cases where a flexy field needs to reference another model, such as:
- A product's related category or brand
- A user's assigned manager or team
- A post's featured image or author
- A task's parent task or project

Adding relationship support would enable FlexyField to handle these common scenarios while maintaining type safety and query integration.

## What Changes
- **ADDED**: New `RELATIONSHIP` field type in `FlexyFieldType` enum
- **ADDED**: Database columns `value_related_model_type` and `value_related_model_id` in `ff_values` table
- **ADDED**: Support for storing model references in flexy fields (one-to-one, one-to-many relationships)
- **ADDED**: Relationship retrieval methods that return Eloquent model instances
- **ADDED**: Query support for filtering by related models
- **ADDED**: Validation to ensure referenced models exist
- **ADDED**: Cascade delete options for relationship fields
- **MODIFIED**: Type detection logic to recognize Eloquent model instances
- **MODIFIED**: Pivot view to include relationship fields for querying
- **MODIFIED**: Field set field definitions to support relationship configuration

## Impact
- **Affected specs**: 
  - `type-system` - New relationship type and type detection
  - `dynamic-field-storage` - New storage columns and persistence logic
  - `query-integration` - Relationship querying capabilities
- **Affected code**:
  - `src/Enums/FlexyFieldType.php` - Add RELATIONSHIP case
  - `src/Traits/Flexy.php` - Type detection, storage, and retrieval logic
  - `src/Models/Value.php` - Relationship accessor methods
  - `database/migrations/create_flexyfield_table.php` - Add relationship columns
  - `src/FlexyField.php` - Pivot view updates for relationships
  - `src/Models/SetField.php` - Relationship metadata storage
- **Breaking changes**: None (additive feature)
- **Migration required**: Yes - adds new columns to `ff_values` table

