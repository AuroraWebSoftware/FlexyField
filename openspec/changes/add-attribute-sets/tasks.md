# Implementation Tasks

## Phase 1: Database Schema and Migrations (Priority: Critical)

- [ ] Create migration for ff_attribute_sets table
  - Add id, model_type, set_code, label, description, metadata (json), is_default (boolean), timestamps
  - Add unique constraint on (model_type, set_code)
  - Add indexes on model_type and is_default

- [ ] Create migration for ff_attribute_set_fields table
  - Add id, set_code, field_name, field_type, sort, validation_rules, validation_messages (json), field_metadata (json), timestamps
  - Add unique constraint on (set_code, field_name)
  - Add indexes on set_code and field_name
  - Add foreign key: set_code REFERENCES ff_attribute_sets(set_code) ON DELETE CASCADE ON UPDATE CASCADE

- [ ] Create migration to add attribute_set_code column to ff_values
  - Add attribute_set_code column (string, nullable)
  - Add index on attribute_set_code
  - Keep existing unique constraint on (model_type, model_id, field_name)

- [ ] Create helper migration trait for models using Flexy
  - Provide AddAttributeSetCodeColumn trait
  - Include attribute_set_code column (string, nullable)
  - Include index on attribute_set_code
  - Include foreign key: attribute_set_code REFERENCES ff_attribute_sets(set_code) ON DELETE SET NULL ON UPDATE CASCADE
  - Document usage in README

- [ ] Create data migration script for shapes to attribute sets
  - Identify all distinct model_types from ff_shapes
  - For each model_type, create default attribute set with set_code="default", is_default=true
  - Migrate all ff_shapes records to ff_attribute_set_fields with set_code="default"
  - Update all model instances with attribute_set_code="default"
  - Update all ff_values with attribute_set_code="default"
  - Drop ff_shapes table after verification

- [ ] Update ff_values_pivot_view recreation logic
  - Modify FlexyField::dropAndCreatePivotView() to handle attribute set context
  - Ensure view includes all fields across all attribute sets
  - Test view with multiple attribute sets

## Phase 2: Core Models (Priority: Critical)

- [ ] Create AttributeSet model (src/Models/AttributeSet.php)
  - Define table, guarded=[], casts
  - Add fields() HasMany relationship to AttributeSetField
  - Add validation in boot() for unique is_default per model_type
  - Add scope for filtering by model_type

- [ ] Create AttributeSetField model (src/Models/AttributeSetField.php)
  - Define table, guarded=[], casts
  - Add attributeSet() BelongsTo relationship
  - Add validation for valid field_type enum values

- [ ] Update Value model
  - Add attribute_set_code to fillable (or keep guarded=[])
  - Update queries to include attribute_set_code context

## Phase 3: New Exceptions (Priority: High)

- [ ] Create AttributeSetNotFoundException exception
  - Extend base exception
  - Include set_code and model_type in message

- [ ] Create AttributeSetInUseException exception
  - Extend base exception
  - Include set_code and count of instances using it

- [ ] Create FieldNotInAttributeSetException exception
  - Extend base exception
  - Include field_name, set_code, and available fields

## Phase 4: Update Flexy Trait (Priority: Critical)

- [ ] Remove legacy shape methods from Flexy trait
  - Remove setFlexyShape() method
  - Remove getFlexyShape() method
  - Remove getAllFlexyShapes() method
  - Remove deleteFlexyShape() method
  - Remove static $hasShape property

- [ ] Add attribute set management methods to Flexy trait
  - Add createAttributeSet(string $setCode, string $label, ?string $description, ?array $metadata, bool $isDefault)
  - Add getAttributeSet(string $setCode): ?AttributeSet
  - Add getAllAttributeSets(): Collection
  - Add deleteAttributeSet(string $setCode): bool with in-use check

- [ ] Add field management methods to Flexy trait
  - Add addFieldToSet(string $setCode, string $fieldName, FlexyFieldType $fieldType, int $sort, ?string $validationRules, ?array $validationMessages, ?array $fieldMetadata)
  - Add removeFieldFromSet(string $setCode, string $fieldName): bool
  - Add getFieldsForSet(string $setCode): Collection

- [ ] Add instance methods to Flexy trait
  - Add assignToAttributeSet(string $setCode): void
  - Add getAttributeSetCode(): ?string
  - Add getAvailableFields(): Collection

- [ ] Update bootFlexy() method
  - Modify global scope to join with pivot view (no attribute_set_code filtering in global scope)
  - Update saving event to validate attribute set assignment exists
  - Update saving event to check field exists in instance's attribute set
  - Update validation logic to use AttributeSetField instead of Shape

- [ ] Update flexy() accessor
  - Ensure fields are filtered by instance's attribute_set_code
  - Return only fields from assigned attribute set

## Phase 5: Update FlexyModelContract (Priority: Critical)

- [ ] Remove legacy shape methods from FlexyModelContract interface
  - Remove setFlexyShape() signature
  - Remove getFlexyShape() signature
  - Remove getAllFlexyShapes() signature
  - Remove deleteFlexyShape() signature
  - Remove hasShape() signature

- [ ] Add attribute set method signatures to FlexyModelContract
  - Add all new attribute set management method signatures
  - Add all new field management method signatures
  - Add all new instance method signatures

## Phase 6: Query Scopes and Integration (Priority: High)

- [ ] Add attribute set query scopes
  - Add whereAttributeSet(string $setCode) scope
  - Add whereAttributeSetIn(array $setCodes) scope
  - Add whereAttributeSetNull() scope

- [ ] Update global scope in bootFlexy()
  - Keep left join to pivot view without attribute_set_code filtering
  - Ensure cross-set queries work correctly
  - Handle null attribute_set_code gracefully

- [ ] Add eager loading support
  - Define attributeSet() BelongsTo relationship in Flexy trait
  - Test eager loading with with('attributeSet')
  - Ensure no N+1 queries for attribute set access

## Phase 7: Testing (Priority: Critical)

- [ ] Create test migrations for test models
  - Add attribute_set_code column to ExampleFlexyModel
  - Add attribute_set_code column to ExampleShapelyFlexyModel
  - Add foreign key constraints

- [ ] Write unit tests for AttributeSet model
  - Test creation, retrieval, deletion
  - Test is_default flag enforcement (only one per model_type)
  - Test metadata storage and retrieval
  - Test relationships to fields

- [ ] Write unit tests for AttributeSetField model
  - Test creation, retrieval, deletion
  - Test field metadata storage
  - Test relationships

- [ ] Write integration tests for attribute set assignment
  - Test assignToAttributeSet() on model instances
  - Test getAttributeSetCode() returns correct value
  - Test getAvailableFields() returns correct fields
  - Test field access restricted to assigned set

- [ ] Write integration tests for validation
  - Test validation rules from AttributeSetField are applied
  - Test FieldNotInAttributeSetException when field not in set
  - Test AttributeSetNotFoundException when no set assigned

- [ ] Write integration tests for queries
  - Test whereAttributeSet() filters correctly
  - Test whereAttributeSetIn() with multiple sets
  - Test cross-attribute-set queries
  - Test ordering by flexy fields across sets
  - Test eager loading with attribute sets

- [ ] Write migration tests
  - Test shapes to attribute sets migration preserves data
  - Test all model instances assigned to default set
  - Test ff_values updated with attribute_set_code="default"
  - Test no data loss during migration
  - Test rollback works correctly

- [ ] Update existing test suites
  - Refactor tests using old shape API to new attribute set API
  - Ensure all tests pass with new architecture
  - Remove tests for deprecated functionality

## Phase 8: Documentation (Priority: Medium)

- [ ] Update README.md
  - Remove old setFlexyShape() examples
  - Add attribute set quick start guide
  - Document new API methods with examples
  - Add migration guide from shapes to attribute sets

- [ ] Create UPGRADE.md guide
  - Document breaking changes clearly
  - Provide step-by-step migration instructions
  - Include code examples for old vs new API
  - Document database migration steps

- [ ] Update inline documentation
  - Add PHPDoc blocks to all new methods
  - Document exceptions thrown by each method
  - Add examples in doc blocks

- [ ] Create example implementations
  - Add example for e-commerce product attribute sets
  - Show multi-set usage (footwear vs books)

## Phase 9: Validation and Polish (Priority: High)

- [ ] Run static analysis
  - Execute phpstan analyse
  - Fix any type errors or warnings
  - Ensure no undefined methods

- [ ] Run code style checks
  - Execute ./vendor/bin/pint
  - Fix any formatting issues
  - Ensure PSR-12 compliance

- [ ] Run full test suite
  - Execute ./vendor/bin/pest
  - Ensure 100% of tests pass
  - Check test coverage for new code

- [ ] Test matrix validation
  - Test with PHP 8.2 and 8.3
  - Test with Laravel 10.x and 11.x
  - Test with prefer-stable and prefer-lowest dependencies
  - Ensure all combinations pass

- [ ] Performance testing
  - Benchmark queries with multiple attribute sets (10 sets, 20 fields each)
  - Test pivot view recreation performance
  - Optimize if bottlenecks found

## Phase 10: Final Integration (Priority: Medium)

- [ ] Update service provider if needed
  - Register new models
  - Add any configuration options
  - Update published migrations

- [ ] Review and finalize exception messages
  - Ensure all error messages are clear and actionable
  - Include helpful context in exceptions
  - Test exception scenarios

- [ ] Final code review
  - Review all new code for consistency
  - Check for security vulnerabilities
  - Ensure proper error handling everywhere
  - Verify no deprecated code remains

## Dependencies and Parallelization

**Can be done in parallel:**
- Phase 1 (Migrations) and Phase 3 (Exceptions)
- Phase 2 (Models) and Phase 5 (Contract updates)

**Must be sequential:**
- Phase 1 → Phase 2 (models need tables)
- Phase 2 → Phase 4 (trait needs models)
- Phase 4 → Phase 6 (scopes need updated trait)
- Phase 4 → Phase 7 (tests need working implementation)
- Phase 7 → Phase 9 (validation needs passing tests)

**Critical path:**
Phase 1 → Phase 2 → Phase 4 → Phase 5 → Phase 6 → Phase 7 → Phase 9 → Phase 10
