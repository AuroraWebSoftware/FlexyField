# Design: UI Hints Support

## Goal
Provide user-friendly labels, placeholders, and hints for fields to improve UI/UX.

## Technical Approach

### 1. Label Column (New Database Column)
Add a dedicated `label` column to `ff_schema_fields` table.

**Reason:** Label is a fundamental field property, used in exports, APIs, and validation messages.

**Migration:**
```php
Schema::table('ff_schema_fields', function (Blueprint $table) {
    $table->string('label')->nullable()->after('name');
});
```

### 2. Metadata for UI Decoration
Store optional UI hints in `metadata` JSON column:
- `placeholder`: Input placeholder text
- `hint`: Help text or tooltip

**Format:**
```json
{
  "placeholder": "Enter value in mAh",
  "hint": "Max 5000mAh"
}
```

### 3. SchemaField Model Updates
Add helper methods:
- `getLabel(): string` - Returns `label` or falls back to `name`
- `getPlaceholder(): ?string` - Returns placeholder from metadata
- `getHint(): ?string` - Returns hint from metadata

### 4. Default Behavior
If `label` is null, fallback to `name` field for backward compatibility.

## Constraints
- `label` is optional (nullable)
- `placeholder` and `hint` are optional metadata
- No validation changes required

## Database Schema
```sql
ff_schema_fields:
  - name (slug)
  - label (display name) ← NEW COLUMN
  - metadata (JSON: {placeholder, hint, ...})
```
