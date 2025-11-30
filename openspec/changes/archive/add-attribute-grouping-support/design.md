# Design: Attribute Grouping Support

## Goal
Allow fields within a schema to be grouped logically (e.g., "Technical Specs", "Dimensions", "General") to improve UI organization in PIM/CRM applications.

## Technical Approach

### 1. Metadata Storage
We will use the existing `metadata` JSON column in the `ff_schema_fields` table. No database schema changes are required.

**Structure:**
```json
{
  "group": "Technical Specs",
  "options": [...] // existing feature
}
```

### 2. SchemaField Model Updates
Add helper methods to `src/Models/SchemaField.php` to easily access group information.

- `getGroup(): ?string`: Returns the group name or null if not grouped.
- `hasGroup(): bool`: Returns true if the field belongs to a group.

### 3. FieldSchema Model Updates
Add a helper method to `src/Models/FieldSchema.php` to retrieve fields organized by group.

- `getFieldsGrouped(): Collection`: Returns a collection where keys are group names (or "default") and values are collections of `SchemaField` objects.

### 4. API / Usage
The `addFieldToSchema` method already accepts `fieldMetadata`. We will simply document and standardize the usage of the `group` key.

```php
Product::addFieldToSchema(
    schemaCode: 'electronics',
    fieldName: 'voltage',
    fieldType: FlexyFieldType::STRING,
    fieldMetadata: ['group' => 'Power Specs']
);
```

## Constraints
- Group names are simple strings.
- If `group` is missing or null, the field belongs to the "default" or "ungrouped" section.
- Implementation must be backward compatible.

## Architecture
- **Clean & Simple:** Logic resides in Model accessors/helpers. No complex services or traits needed.
- **Performance:** Grouping happens in memory (Collection) after fetching fields. Since schema fields are usually limited (<100), this is performant.
