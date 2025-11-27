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

## Edge Cases and Error Handling

### 1. Concurrent Modifications

**Edge Case**: Two users modify the same field set simultaneously
- **Detection**: Use Laravel's optimistic locking (updated_at comparison)
- **Behavior**: Last write wins (database-level constraint enforcement)
- **Test**: Simulate concurrent createFieldSet() calls with same set_code
- **Expected**: Second call fails with unique constraint violation

**Edge Case**: User deletes field set while another assigns model to it
- **Detection**: Foreign key constraint checks
- **Behavior**: Assignment fails with FieldSetNotFoundException
- **Test**: Delete field set in one transaction, assign in another
- **Expected**: Foreign key constraint prevents orphan reference

### 2. Field Set Deletion Scenarios

**Edge Case**: Delete field set with 1000+ model instances assigned
- **Prevention**: Application-level check counts instances before delete
- **Behavior**: Throw FieldSetInUseException with count
- **Test**: Create 1000 models, attempt delete
- **Expected**: Exception with "Field set in use by 1000 instances"

**Edge Case**: Force delete field set (bypass application check)
- **Behavior**: Foreign key ON DELETE SET NULL sets all instances to null
- **Side Effect**: All instances lose field set assignment
- **Test**: Direct DB delete, verify instances have null field_set_code
- **Expected**: Null field_set_code, FieldSetNotFoundException on field access

**Edge Case**: Delete default field set
- **Behavior**: Allowed if no instances assigned
- **Side Effect**: model_type has no default set
- **Test**: Delete default set, create new model instance
- **Expected**: New instance has null field_set_code (must manually assign)

### 3. Field Set Assignment Edge Cases

**Edge Case**: Assign non-existent field set to model
- **Behavior**: Foreign key constraint violation
- **Test**: assignToFieldSet('nonexistent')
- **Expected**: Database exception (FK violation)

**Edge Case**: Assign field set from different model_type
- **Prevention**: Application-level validation checks model_type match
- **Behavior**: Throw FieldSetNotFoundException
- **Test**: Product::assignToFieldSet('category_electronics')
- **Expected**: Exception: "Field set not found for model type Product"

**Edge Case**: Assign field set to unsaved model
- **Behavior**: Works, but field values cannot be saved until model saved
- **Test**: $model = new Product(); $model->assignToFieldSet('footwear');
- **Expected**: field_set_code set, but flexy values fail until model has ID

**Edge Case**: Change field set after setting field values
- **Behavior**: Old field values remain in ff_values with old field_set_code
- **Side Effect**: Values from old set become inaccessible
- **Test**: Set values, change field set, access old values
- **Expected**: Old values return null, new field set fields accessible

### 4. Field Value Edge Cases

**Edge Case**: Set field not in assigned field set
- **Behavior**: Throw FieldNotInSetException during save
- **Test**: assignToFieldSet('footwear'); $model->flexy->isbn = '123';
- **Expected**: Exception: "Field 'isbn' not in field set 'footwear'"

**Edge Case**: Set field with null value
- **Behavior**: Allowed, stores null in appropriate value_* column
- **Test**: $model->flexy->color = null; $model->save();
- **Expected**: Value record exists with all value_* columns null

**Edge Case**: Set field with empty string
- **Behavior**: Stores empty string in value_string
- **Validation**: Depends on validation rules (e.g., 'required' fails)
- **Test**: $model->flexy->color = ''; with 'required' validation
- **Expected**: ValidationException

**Edge Case**: Set field with value exceeding column length
- **Behavior**: Database truncates or throws error
- **Prevention**: Validation rule 'max:500' recommended
- **Test**: Set 1000-char string with validation_rules='max:500'
- **Expected**: ValidationException before database error

**Edge Case**: Access field before model saved
- **Behavior**: Returns in-memory value if set, null otherwise
- **Test**: $model = new Product(); $model->flexy->color = 'Red'; echo $model->flexy->color;
- **Expected**: Returns 'Red' from memory, not database

### 5. Query Edge Cases

**Edge Case**: Query field that exists in multiple field sets
- **Behavior**: Returns all models with that field value across all sets
- **Test**: Product::where('flexy_color', 'Red')->get();
- **Expected**: Returns footwear AND clothing products with color=Red

**Edge Case**: Query field that doesn't exist in any field set
- **Behavior**: No results (pivot view has no column)
- **Test**: Product::where('flexy_nonexistent', 'value')->get();
- **Expected**: Empty collection (or SQL error if column doesn't exist in view)

**Edge Case**: Query with null field_set_code
- **Behavior**: Returns models without field set assignment
- **Test**: Product::whereFieldSetNull()->get();
- **Expected**: All products with field_set_code = NULL

**Edge Case**: Order by field with mixed types across sets
- **Behavior**: Database orders by string representation
- **Side Effect**: Unexpected order (e.g., "10" before "2")
- **Test**: Order by field that's INTEGER in one set, STRING in another
- **Expected**: String-based ordering (document this limitation)

### 6. Validation Edge Cases

**Edge Case**: Validation rules conflict with field type
- **Example**: FlexyFieldType::INTEGER with validation 'email'
- **Behavior**: Type detection takes precedence, stores as integer
- **Test**: Add INTEGER field with 'email' rule, set value '123'
- **Expected**: Validation fails (123 is not email)

**Edge Case**: Validation messages contain special characters
- **Behavior**: JSON encoded/decoded correctly
- **Test**: validation_messages = ['required' => 'Field "color" is required']
- **Expected**: Double quotes escaped, displays correctly

**Edge Case**: Very long validation rule string (>500 chars)
- **Behavior**: Database column truncates or errors
- **Prevention**: Validation during addFieldToSet()
- **Test**: Add field with 1000-char validation string
- **Expected**: Exception or truncation warning

### 7. Migration Edge Cases

**Edge Case**: Migration runs twice accidentally
- **Behavior**: Idempotent checks prevent duplicate data
- **Test**: Run migration, then run again
- **Expected**: Second run detects existing data, skips creation

**Edge Case**: ff_shapes has invalid model_type references
- **Behavior**: Migration creates field sets but logs warnings
- **Test**: Insert shape with model_type='NonExistent\\Model'
- **Expected**: Field set created, warning logged

**Edge Case**: Model instances have different model_type casing
- **Example**: 'App\\Models\\Product' vs 'app\\models\\product'
- **Behavior**: Treated as different types
- **Prevention**: Normalize to class name during migration
- **Test**: Mixed casing in ff_shapes
- **Expected**: Normalized to canonical class name

**Edge Case**: Rollback migration after models already created with field sets
- **Behavior**: Foreign keys prevent rollback
- **Solution**: Must manually delete or nullify model references first
- **Test**: Create models with field sets, attempt rollback
- **Expected**: Foreign key constraint error with instructions

### 8. Performance Edge Cases

**Edge Case**: Field set with 1000+ fields
- **Behavior**: Pivot view becomes very wide
- **Performance Impact**: Slower view recreation (30+ seconds)
- **Test**: Create field set with 1000 fields, recreate view
- **Expected**: Works but slow, document recommended limit (100 fields)

**Edge Case**: Model with 100,000+ instances changing field sets
- **Behavior**: Mass update of field_set_code
- **Performance Impact**: Long-running transaction
- **Test**: Update 100k models to new field set
- **Expected**: Works but slow (batch updates recommended)

**Edge Case**: Concurrent pivot view recreation
- **Behavior**: Last recreation wins
- **Side Effect**: Temporary inconsistency during recreation
- **Test**: Drop/create view while queries running
- **Expected**: Queries may fail during recreation window

### 9. Data Integrity Edge Cases

**Edge Case**: Manually modify field_set_code in database
- **Behavior**: Foreign key allows if references valid set
- **Side Effect**: Field access uses new set's field definitions
- **Test**: UPDATE models SET field_set_code='books' WHERE...
- **Expected**: Models switch sets, old field values inaccessible

**Edge Case**: field_set_code and ff_values.field_set_code mismatch
- **Prevention**: Application ensures sync during save
- **Behavior**: If mismatch occurs, model's field_set_code takes precedence
- **Test**: Manually create mismatch, load model
- **Expected**: Uses model's field_set_code for field definitions

**Edge Case**: Field type changed in field set
- **Example**: Change 'price' from DECIMAL to STRING
- **Behavior**: Existing values remain in old column
- **Side Effect**: Values appear null until migrated
- **Test**: Change field type, access existing values
- **Expected**: Returns null (old column not checked)

### 10. Metadata Edge Cases

**Edge Case**: Metadata JSON contains malicious script
- **Prevention**: Sanitize before rendering in UI
- **Behavior**: Stored as-is, XSS risk if not sanitized
- **Test**: Set metadata = ['icon' => '<script>alert(1)</script>']
- **Expected**: Stored correctly, but must escape when displaying

**Edge Case**: Metadata exceeds JSON column size
- **Behavior**: Database error or truncation
- **Prevention**: Validate size during createFieldSet()
- **Test**: Set 1MB metadata JSON
- **Expected**: Exception before database error

**Edge Case**: Metadata contains circular reference
- **Behavior**: json_encode fails
- **Prevention**: Validate before storing
- **Test**: $obj = new stdClass(); $obj->self = $obj; metadata = ['obj' => $obj]
- **Expected**: JSON encoding exception

## Test Coverage Requirements

### Unit Test Coverage: >95%
- All model methods
- All validation logic
- All exception scenarios
- Edge cases documented above

### Integration Test Coverage: 100%
- Field set CRUD operations
- Field assignment/removal
- Model-field set assignment
- Query operations across sets
- Migration scenarios

### Performance Benchmarks
- Field set with 100 fields: <100ms creation
- 1000 models assignment: <5s
- Cross-set query (3 sets): <200ms
- Pivot view recreation: <10s for 50 fields
