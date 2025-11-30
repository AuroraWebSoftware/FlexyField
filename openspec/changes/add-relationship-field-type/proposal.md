# Change: Add Relationship Field Type

## Why
Currently, FlexyField supports storing primitive values (strings, integers, decimals, dates, booleans, JSON) but cannot store references to other Eloquent models. This limitation prevents use cases where a flexy field needs to reference another model, such as:
- A product's related category or brand
- A user's assigned manager or team
- A post's featured image or author
- A task's parent task or project

Adding relationship support would enable FlexyField to handle these common scenarios while maintaining type safety and query integration.

## What Changes
- **Enum Update:** Add `RELATIONSHIP` field type in `FlexyFieldType` enum.
- **Database Schema:** Add `value_related_model_type` (string) and `value_related_model_id` (string/int) columns to `ff_field_values` table.
- **Metadata Configuration:**
    - Require `target_model` (e.g., `App\Models\User::class`) in the schema field metadata to enforce type safety.
    - Support polymorphic relationships if `target_model` is not specified or set to `*` (optional, but strict mode preferred).
- **Eager Loading Strategy (N+1 Solution):**
    - Implement a `loadFlexyRelations(array $fieldNames)` method in `Flexy` trait.
    - This method will collect all IDs for the specified fields from the loaded models and perform a batch query (WhereIn) to load related models, mapping them back to the flexy fields in memory.
    - Avoids N+1 queries when listing models with relationship fields.
- **Pivot View Strategy:**
    - The pivot view will expose `flexy_{field}_id` and `flexy_{field}_type` columns.
    - **Crucial:** The view will NOT perform joins to related tables to avoid massive performance degradation. Joins should be handled at the application query level if needed.
- **Validation:** Ensure referenced models exist in the database before saving.
- **Cascade Delete:** Implement options in metadata (e.g., `cascade: true`) to handle behavior when the related model is deleted (set null or delete value).

## Impact
- **Affected specs**:
  - `type-system`: New relationship type and type detection
  - `dynamic-field-storage`: New storage columns and persistence logic
  - `query-integration`: Relationship querying capabilities
- **Affected code**:
  - `src/Enums/FlexyFieldType.php`: Add RELATIONSHIP case
  - `src/Traits/Flexy.php`: Type detection, storage, retrieval, and **eager loading logic**
  - `src/Models/Value.php`: Relationship accessor methods
  - `database/migrations/create_flexyfield_table.php`: Add relationship columns
  - `src/FlexyField.php`: Pivot view updates (add ID/Type columns)
  - `src/Models/SetField.php`: Relationship metadata storage
- **Breaking changes**: None (additive feature)
- **Migration required**: Yes - adds new columns to `ff_field_values` table. **Note:** This table can be large, so the migration might take time on production datasets.
