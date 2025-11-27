# Implementation Tasks

## Phase 1: Database Schema and Migrations (Priority: Critical)

- [x] Create migration for ff_field_sets table
  - Add id, model_type, set_code, label, description, metadata (json), is_default (boolean), timestamps
  - Add unique constraint on (model_type, set_code)
  - Add indexes on model_type and is_default

- [x] Create migration for ff_set_fields table
  - Add id, set_code, field_name, field_type, sort, validation_rules, validation_messages (json), field_metadata (json), timestamps
  - Add unique constraint on (set_code, field_name)
  - Add indexes on set_code and field_name
  - Add foreign key: set_code REFERENCES ff_field_sets(set_code) ON DELETE CASCADE ON UPDATE CASCADE

- [x] Create migration to add field_set_code column to ff_values
  - Add field_set_code column (string, nullable)
  - Add index on field_set_code
  - Keep existing unique constraint on (model_type, model_id, field_name)

- [x] Create helper migration trait for models using Flexy
  - Provide AddFieldSetCodeColumn trait
  - Include field_set_code column (string, nullable)
  - Include index on field_set_code
  - Include foreign key: field_set_code REFERENCES ff_field_sets(set_code) ON DELETE SET NULL ON UPDATE CASCADE
  - Document usage in README

- [x] Create data migration script for shapes to field sets
  - Identify all distinct model_types from ff_shapes
  - For each model_type, create default field set with set_code="default", is_default=true
  - Migrate all ff_shapes records to ff_set_fields with set_code="default"
  - Update all model instances with field_set_code="default"
  - Update all ff_values with field_set_code="default"
  - Drop ff_shapes table after verification

- [x] Update ff_values_pivot_view recreation logic
  - Modify FlexyField::dropAndCreatePivotView() to handle field set context
  - Ensure view includes all fields across all field sets
  - Test view with multiple field sets

## Phase 2: Core Models (Priority: Critical)

- [x] Create FieldSet model (src/Models/FieldSet.php)
  - Define table, guarded=[], casts
  - Add fields() HasMany relationship to SetField
  - Add validation in boot() for unique is_default per model_type
  - Add scope for filtering by model_type

- [x] Create SetField model (src/Models/SetField.php)
  - Define table, guarded=[], casts
  - Add fieldSet() BelongsTo relationship
  - Add validation for valid field_type enum values

- [x] Update Value model
  - Add field_set_code to fillable (or keep guarded=[])
  - Update queries to include field_set_code context

## Phase 3: New Exceptions (Priority: High)

- [x] Create FieldSetNotFoundException exception
  - Extend base exception
  - Include set_code and model_type in message

- [x] Create FieldSetInUseException exception
  - Extend base exception
  - Include set_code and count of instances using it

- [x] Create FieldNotInSetException exception
  - Extend base exception
  - Include field_name, set_code, and available fields

## Phase 4: Update Flexy Trait (Priority: Critical)

- [x] Remove legacy shape methods from Flexy trait
  - Remove setFlexyShape() method
  - Remove getFlexyShape() method
  - Remove getAllFlexyShapes() method
  - Remove deleteFlexyShape() method
  - Remove static $hasShape property

- [x] Add field set management methods to Flexy trait
  - Add createFieldSet(string $setCode, string $label, ?string $description, ?array $metadata, bool $isDefault)
  - Add getFieldSet(string $setCode): ?FieldSet
  - Add getAllFieldSets(): Collection
  - Add deleteFieldSet(string $setCode): bool with in-use check

- [x] Add field management methods to Flexy trait
  - Add addFieldToSet(string $setCode, string $fieldName, FlexyFieldType $fieldType, int $sort, ?string $validationRules, ?array $validationMessages, ?array $fieldMetadata)
  - Add removeFieldFromSet(string $setCode, string $fieldName): bool
  - Add getFieldsForSet(string $setCode): Collection

- [x] Add instance methods to Flexy trait
  - Add assignToFieldSet(string $setCode): void
  - Add getFieldSetCode(): ?string
  - Add getAvailableFields(): Collection

- [x] Update bootFlexy() method
  - Modify global scope to join with pivot view (no field_set_code filtering in global scope)
  - Update saving event to validate field set assignment exists
  - Update saving event to check field exists in instance's field set
  - Update validation logic to use SetField instead of Shape

- [x] Update flexy() accessor
  - Ensure fields are filtered by instance's field_set_code
  - Return only fields from assigned field set

## Phase 5: Update FlexyModelContract (Priority: Critical)

- [x] Remove legacy shape methods from FlexyModelContract interface
  - Remove setFlexyShape() signature
  - Remove getFlexyShape() signature
  - Remove getAllFlexyShapes() signature
  - Remove deleteFlexyShape() signature
  - Remove hasShape() signature

- [x] Add field set method signatures to FlexyModelContract
  - Add all new field set management method signatures
  - Add all new field management method signatures
  - Add all new instance method signatures

## Phase 6: Query Scopes and Integration (Priority: High)

- [x] Add field set query scopes
  - Add whereFieldSet(string $setCode) scope
  - Add whereFieldSetIn(array $setCodes) scope
  - Add whereFieldSetNull() scope

- [x] Update global scope in bootFlexy()
  - Keep left join to pivot view without field_set_code filtering
  - Ensure cross-set queries work correctly
  - Handle null field_set_code gracefully

- [x] Add eager loading support
  - Define fieldSet() BelongsTo relationship in Flexy trait
  - Test eager loading with with('attributeSet')
  - Ensure no N+1 queries for field set access

## Phase 7: Testing (Priority: Critical)

- [x] Create test migrations for test models
  - Add field_set_code column to ExampleFlexyModel
  - Add field_set_code column to ExampleShapelyFlexyModel
  - Add foreign key constraints

- [x] Write unit tests for FieldSet model
  - Test creation, retrieval, deletion
  - Test is_default flag enforcement (only one per model_type)
  - Test metadata storage and retrieval
  - Test relationships to fields

- [x] Write unit tests for SetField model
  - Test creation, retrieval, deletion
  - Test field metadata storage
  - Test relationships

- [x] Write integration tests for field set assignment
  - Test assignToFieldSet() on model instances
  - Test getFieldSetCode() returns correct value
  - Test getAvailableFields() returns correct fields
  - Test field access restricted to assigned set

- [x] Write integration tests for validation
  - Test validation rules from SetField are applied
  - Test FieldNotInSetException when field not in set
  - Test FieldSetNotFoundException when no set assigned

- [x] Write integration tests for queries
  - Test whereFieldSet() filters correctly
  - Test whereFieldSetIn() with multiple sets
  - Test cross-field-set queries
  - Test ordering by flexy fields across sets
  - Test eager loading with field sets

- [x] Write migration tests
  - Test shapes to field sets migration preserves data
  - Test all model instances assigned to default set
  - Test ff_values updated with field_set_code="default"
  - Test no data loss during migration
  - Test rollback works correctly

- [x] Update existing test suites
  - Refactor tests using old shape API to new field set API
  - Ensure all tests pass with new architecture
  - Remove tests for deprecated functionality

- [x] Write edge case tests - Concurrent Modifications
  - Test concurrent createFieldSet() with same set_code (expect unique constraint error)
  - Test concurrent field set deletion while model assigns to it (expect FK error)
  - Test concurrent field additions to same set (expect optimistic locking)

- [x] Write edge case tests - Field Set Deletion
  - Test delete field set with 1000+ instances (expect FieldSetInUseException with count)
  - Test force delete via DB (verify instances get NULL field_set_code)
  - Test delete default field set (verify new instances get NULL)
  - Test access fields after field set deleted (expect FieldSetNotFoundException)

- [x] Write edge case tests - Field Set Assignment
  - Test assign non-existent field set (expect FK constraint violation)
  - Test assign field set from different model_type (expect FieldSetNotFoundException)
  - Test assign field set to unsaved model (verify works but values fail)
  - Test change field set after setting values (verify old values inaccessible)
  - Test assign field set then delete it (verify FieldSetNotFoundException)

- [x] Write edge case tests - Field Values
  - Test set field not in assigned field set (expect FieldNotInSetException)
  - Test set field with null value (verify stores null correctly)
  - Test set field with empty string (verify validation if 'required')
  - Test set field exceeding max length (expect ValidationException)
  - Test access field before model saved (verify in-memory value returned)
  - Test set field with special characters (verify JSON encoding)

- [x] Write edge case tests - Queries
  - Test query field in multiple field sets (verify returns from all sets)
  - Test query non-existent field (verify empty or SQL error)
  - Test whereFieldSetNull() (verify returns unassigned models)
  - Test order by field with mixed types (verify string-based ordering)
  - Test cross-set queries with same field name (verify correct results)

- [x] Write edge case tests - Validation
  - Test INTEGER field with 'email' validation (expect validation failure)
  - Test validation messages with special chars (verify JSON escaping)
  - Test validation rule >500 chars (expect truncation or error)
  - Test custom validation messages display correctly
  - Test validation fails on null when required

- [x] Write edge case tests - Migration
  - Test migration runs twice (verify idempotent)
  - Test migration with invalid model_type (verify warning logged)
  - Test migration with mixed casing model_type (verify normalized)
  - Test rollback with FK constraints (verify error with instructions)
  - Test migration with 0 shapes (verify no-op)
  - Test migration with 10,000+ shapes (verify performance acceptable)

- [x] Write edge case tests - Performance
  - Test field set with 1000 fields (verify slow but works, <2min)
  - Test 100,000 models field set change (verify batch updates work)
  - Test concurrent pivot view recreation (verify queries fail gracefully)
  - Test query with 100 field sets (verify performance <1s)

- [x] Write edge case tests - Data Integrity
  - Test manual field_set_code DB modification (verify FK constraint)
  - Test mismatch between model and ff_values field_set_code (verify model wins)
  - Test field type change in set (verify old values become null)
  - Test orphan ff_values records (verify cleanup)

- [x] Write edge case tests - Metadata
  - Test metadata with XSS payload (verify stored but not executed)
  - Test metadata exceeding size (expect error)
  - Test metadata with circular reference (expect JSON exception)
  - Test metadata with unicode characters (verify stored correctly)

- [x] Write comprehensive integration test scenarios
  - Scenario: Create 3 field sets, 10 fields each, assign 1000 models, query
  - Scenario: Change field set on 500 models, verify old values inaccessible
  - Scenario: Delete field set, verify cascade delete of fields
  - Scenario: Concurrent users creating field sets and fields
  - Scenario: Import 10,000 products with different field sets via CSV

- [x] Write regression tests for known issues
  - Document any bugs found during development as regression tests
  - Test previously reported GitHub issues if any

- [x] Write test for code coverage edge cases
  - Test all exception throw paths
  - Test all validation failure paths
  - Test all null/empty/boundary conditions
  - Verify 95%+ code coverage

## Phase 8: Documentation (Priority: Medium)

- [x] Update README.md
  - Remove old setFlexyShape() examples
  - Add field set quick start guide
  - Document new API methods with examples
  - Add migration guide from shapes to field sets

- [x] Create UPGRADE.md guide
  - Document breaking changes clearly
  - Provide step-by-step migration instructions
  - Include code examples for old vs new API
  - Document database migration steps

- [x] Update inline documentation
  - Add PHPDoc blocks to all new methods
  - Document exceptions thrown by each method
  - Add examples in doc blocks

- [x] Create example implementations
  - Add example for e-commerce product field sets
  - Show multi-set usage (footwear vs books)

## Phase 9: Validation and Polish (Priority: High)

- [x] Run static analysis
  - Execute phpstan analyse
  - Fix any type errors or warnings
  - Ensure no undefined methods

- [x] Run code style checks
  - Execute ./vendor/bin/pint
  - Fix any formatting issues
  - Ensure PSR-12 compliance

- [x] Run full test suite
  - Execute ./vendor/bin/pest
  - Ensure 100% of tests pass
  - Check test coverage for new code

- [x] Test matrix validation
  - Test with PHP 8.2 and 8.3
  - Test with Laravel 10.x and 11.x
  - Test with prefer-stable and prefer-lowest dependencies
  - Ensure all combinations pass

- [x] Performance testing
  - Benchmark queries with multiple field sets (10 sets, 20 fields each)
  - Test pivot view recreation performance
  - Optimize if bottlenecks found

## Phase 10: Final Integration (Priority: Medium)

- [x] Update service provider if needed
  - Register new models
  - Add any configuration options
  - Update published migrations

- [x] Review and finalize exception messages
  - Ensure all error messages are clear and actionable
  - Include helpful context in exceptions
  - Test exception scenarios

- [x] Final code review
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
