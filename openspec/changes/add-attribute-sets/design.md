# Design: Field Set Architecture

## Architectural Overview

### Current Architecture
```
Model (Product) → ff_shapes (model_type, field_name, validation_rules)
                → ff_values (model_type, model_id, field_name, value_*)
```

All Product instances share the same field definitions from ff_shapes where model_type = 'Product'.

### New Architecture
```
Model (Product) → ff_field_sets (model_type, set_code, label, metadata)
                → ff_set_fields (set_code, field_name, field_type, validation_rules)
                → ff_values (model_type, model_id, field_name, value_*, field_set_code)
```

Each Product instance has a `field_set_code` column that determines which field definitions apply.

## Database Schema

### New Tables

#### ff_field_sets
Stores field set definitions per model type.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| model_type | string(255) | PHP class name (e.g., App\Models\Product) |
| set_code | string(100) | Unique identifier (e.g., 'footwear', 'books') |
| label | string(255) | Human-readable name (e.g., 'Footwear Fields') |
| description | text | Optional description (nullable) |
| metadata | json | Custom metadata (icon, color, etc.) (nullable) |
| is_default | boolean | Whether this is the default set for new instances (default: false) |
| created_at | timestamp | |
| updated_at | timestamp | |

**Indexes**:
- PRIMARY KEY (id)
- UNIQUE(model_type, set_code)
- INDEX(model_type)
- INDEX(is_default)

**Constraints**:
- Only one is_default=true per model_type (enforced in application logic)

#### ff_set_fields
Replaces ff_shapes with field set scoping.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| set_code | string(100) | Reference to ff_field_sets.set_code |
| field_name | string(255) | Field identifier |
| field_type | string(50) | FlexyFieldType enum value |
| sort | integer | Display order (default: 100) |
| validation_rules | string(500) | Laravel validation rules (nullable) |
| validation_messages | json | Custom error messages (nullable) |
| field_metadata | json | Custom field metadata (nullable) |
| created_at | timestamp | |
| updated_at | timestamp | |

**Indexes**:
- PRIMARY KEY (id)
- UNIQUE(set_code, field_name)
- INDEX(set_code)
- INDEX(field_name)

**Foreign Keys**:
- FOREIGN KEY (set_code) REFERENCES ff_field_sets(set_code) ON DELETE CASCADE ON UPDATE CASCADE

#### Modified Tables

**ff_values**: Add `field_set_code` column

| Change | Details |
|--------|---------|
| Add column | `field_set_code` VARCHAR(100) NULLABLE |
| Add index | INDEX(field_set_code) |
| Keep constraint | UNIQUE(model_type, model_id, field_name) |

**Models using Flexy trait**: Add `field_set_code` column

| Change | Details |
|--------|---------|
| Add column | `field_set_code` VARCHAR(100) NULLABLE |
| Add index | INDEX(field_set_code) |
| Foreign key | FOREIGN KEY (field_set_code) REFERENCES ff_field_sets(set_code) ON DELETE SET NULL ON UPDATE CASCADE |

## API Design

### New Methods in Flexy Trait

```php
// Field Set Management
public static function createFieldSet(
    string $setCode,
    string $label,
    ?string $description = null,
    ?array $metadata = [],
    bool $isDefault = false
): FieldSet;

public static function getFieldSet(string $setCode): ?FieldSet;

public static function getAllFieldSets(): Collection;

public static function deleteFieldSet(string $setCode): bool;

// Field Management
public static function addFieldToSet(
    string $setCode,
    string $fieldName,
    FlexyFieldType $fieldType,
    int $sort = 100,
    ?string $validationRules = null,
    ?array $validationMessages = null,
    ?array $fieldMetadata = []
): SetField;

public static function removeFieldFromSet(string $setCode, string $fieldName): bool;

public static function getFieldsForSet(string $setCode): Collection;

// Instance Methods
public function assignToFieldSet(string $setCode): void;

public function getFieldSetCode(): ?string;

public function getAvailableFields(): Collection; // Fields from assigned field set
```

### New Models

```php
// FieldSet.php
class FieldSet extends Model {
    protected $table = 'ff_field_sets';
    protected $guarded = [];
    protected $casts = [
        'metadata' => 'array',
        'is_default' => 'boolean'
    ];

    public function fields(): HasMany; // -> SetField
}

// SetField.php
class SetField extends Model {
    protected $table = 'ff_set_fields';
    protected $guarded = [];
    protected $casts = [
        'validation_messages' => 'array',
        'field_metadata' => 'array'
    ];

    public function fieldSet(): BelongsTo; // -> FieldSet
}
```

## Migration Strategy

### Phase 1: Schema Migration
1. Create new tables: ff_field_sets, ff_set_fields
2. Add field_set_code columns to models and ff_values
3. Add foreign key constraints
4. Keep ff_shapes table temporarily for data migration

### Phase 2: Data Migration
1. For each model_type in ff_shapes:
   - Create default field set with set_code: `default`
   - Set label: `{ModelName} Default`
   - Mark as is_default=true
   - Migrate all shapes to ff_set_fields for default set
2. Update all model instances: set field_set_code to 'default'
3. Update all ff_values records: set field_set_code from model's field_set_code

### Phase 3: Code Migration
1. Remove old shape methods from Flexy trait
2. Update FlexyModelContract interface
3. Update saving/validation logic to use field sets
4. Update query scopes to filter by field_set_code
5. Drop ff_shapes table

### Phase 4: View Recreation
1. Update ff_values_pivot_view to include field_set_code context
2. Ensure queries join correctly with field set filtering

## Performance Considerations

### Indexing Strategy
- Index field_set_code on model tables for fast set lookups
- Composite index on (set_code, field_name) for field retrieval
- Index on ff_values(field_set_code) for value queries
- Foreign keys automatically create indexes

### Query Optimization
- Eager load fields when loading field sets: `$set->load('fields')`
- Use Laravel's default query result cache (no custom caching)
- Avoid N+1 by using global scopes with left joins

### View Performance
- Pivot view may become wider with multiple field sets
- Keep field_set_code in join conditions
- Use existing MySQL query optimizer (no custom optimizations needed)

## Backward Compatibility (Breaking)

### Removed APIs
- `Model::setFlexyShape()` → Use `Model::addFieldToSet()`
- `Model::getFlexyShape()` → Use `Model::getFieldsForSet()`
- `Model::getAllFlexyShapes()` → Use `Model::getFieldsForSet()`
- `Model::deleteFlexyShape()` → Use `Model::removeFieldFromSet()`
- `Model::$hasShape` → Replaced with field set enforcement

### Migration Guide for Users
```php
// Old API
Product::setFlexyShape('color', FlexyFieldType::STRING, 1, 'required');
Product::setFlexyShape('size', FlexyFieldType::INTEGER, 2, 'numeric|min:20');

// New API
Product::createFieldSet('footwear', 'Footwear Fields', isDefault: true);
Product::addFieldToSet('footwear', 'color', FlexyFieldType::STRING, 1, 'required');
Product::addFieldToSet('footwear', 'size', FlexyFieldType::INTEGER, 2, 'numeric|min:20');

// Assign to model instance
$product = new Product();
$product->assignToFieldSet('footwear');
$product->flexy->color = 'Red';
$product->save();
```

## Error Handling

### New Exceptions
- `FieldSetNotFoundException`: When referencing non-existent field set
- `FieldSetInUseException`: When trying to delete set assigned to instances
- `FieldNotInSetException`: When setting field not in instance's field set

### Validation Behavior
- If field_set_code is null on instance, throw FieldSetNotFoundException
- If field not in instance's set, throw FieldNotInSetException
- Validation follows existing FlexyField validation logic

## Data Integrity

### Foreign Key Cascade Behavior

**ff_set_fields → ff_field_sets**:
- ON DELETE CASCADE: Deleting a field set deletes all its fields
- ON UPDATE CASCADE: Updating set_code updates all field references

**Models → ff_field_sets**:
- ON DELETE SET NULL: Deleting a field set sets model instances to null (prevents orphans)
- ON UPDATE CASCADE: Updating set_code updates all model references

### Orphan Prevention
- Cannot delete field set if any model instances reference it (application-level check)
- If set is deleted (via force), foreign key sets instances to null
- Null field_set_code triggers FieldSetNotFoundException on field access

## Security Considerations

- Use `$guarded = []` with mass assignment protection at controller level
- Validate set_code input (alphanumeric + hyphen/underscore only)
- Sanitize metadata JSON to prevent XSS in admin interfaces
- No special authorization layer (defer to application-level policies)

## Testing Strategy

- Unit tests for FieldSet and SetField models
- Integration tests for field set assignment
- Integration tests for validation with field sets
- Migration tests to ensure data integrity (zero data loss)
- Performance tests for queries with multiple field sets (100 sets, 50 fields each)

## Trade-offs

### Simplicity vs Features
✅ **Chosen**: Simple field sets without groups or conditionals
- Pros: Easy to understand, fast implementation, minimal complexity
- Cons: Less UI organization (can be added in v2)

❌ **Alternative**: Full Magento-style with groups and conditionals
- Pros: Rich feature set, better UX
- Cons: Higher complexity, longer development time

### Foreign Keys vs Application Logic
✅ **Chosen**: Database foreign keys with cascades
- Pros: Data integrity enforced at DB level, automatic cleanup
- Cons: Requires InnoDB/PostgreSQL, can't easily disable

❌ **Alternative**: Application-level integrity checks
- Pros: Database agnostic
- Cons: Risk of orphaned data, more code to maintain
