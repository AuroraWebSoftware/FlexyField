# Design: Consolidate Edge Case Tests

## Context
The current test suite has grown organically with multiple "Edge Case" test files created during different development phases. This has resulted in:
- 5 separate EdgeCase* test files totaling ~73KB
- Overlapping test scenarios across files
- Unclear test organization (where should new edge case tests go?)
- Maintenance burden when updating shared functionality

## Problem Statement
**Current State:**
```
tests/Feature/
├── EdgeCaseTest.php              (18.4KB, 568 lines) - General
├── EdgeCaseValidationTest.php    (16.3KB, ~370 lines) - Validation
├── EdgeCaseAssignmentTest.php    (19.2KB, 638 lines) - Assignment
├── EdgeCaseSchemaTest.php        (7.6KB, ~250 lines) - Schema
├── EdgeCaseTypeSystemTest.php    (11.9KB, ~170 lines) - Types
└── SchemaValidationTest.php      (11.9KB) - Also validation!
└── SchemaAssignmentTest.php      (6.8KB) - Also assignment!
```

**Issues:**
1. Validation tests split between `EdgeCaseValidationTest` and `SchemaValidationTest`
2. Assignment tests split between `EdgeCaseAssignmentTest` and `SchemaAssignmentTest`
3. Unclear naming: "EdgeCase" doesn't indicate what's being tested
4. Potential test duplication (needs analysis to confirm)

## Consolidation Strategy

### Phase 1: Analysis
Create test scenario matrix:
```
| Scenario | EdgeCaseValidationTest | SchemaValidationTest | Duplicate? |
|----------|------------------------|----------------------|------------|
| ...      | ...                    | ...                  | ...        |
```

### Phase 2: Consolidation Mapping
```
EdgeCaseValidationTest → SchemaValidationTest
  ↳ Organize by: Required fields, Type validation, Custom rules, Edge cases

EdgeCaseAssignmentTest → SchemaAssignmentTest
  ↳ Organize by: Schema assignment, Reassignment, Null handling, Edge cases

EdgeCaseTypeSystemTest → TypeSystemTest (NEW)
  ↳ Organize by: STRING, INTEGER, DECIMAL, BOOLEAN, DATE, DATETIME, JSON

EdgeCaseSchemaTest → SchemaEdgeCaseTest (RENAME)
  ↳ Keep as-is, just rename for consistency

EdgeCaseTest → Distribute
  ↳ Schema behavior → SchemaEdgeCaseTest
  ↳ Validation → SchemaValidationTest
  ↳ Types → TypeSystemTest
  ↳ Accessor → FlexyAccessorTest
```

### Phase 3: File Organization
**Target Structure:**
```
tests/Feature/
├── TypeSystemTest.php           (NEW) - All type casting tests
├── SchemaValidationTest.php     (EXPANDED) - All validation tests
├── SchemaAssignmentTest.php     (EXPANDED) - All assignment tests
├── SchemaEdgeCaseTest.php       (RENAMED) - Schema edge cases
└── [Delete 4-5 EdgeCase* files]
```

## Risk Mitigation

### Risk 1: Test Coverage Loss
**Mitigation:**
- Map ALL scenarios before deleting any file
- Run tests after each consolidation step
- Compare test counts before/after (expect 258+ passing)
- Use git to track removed tests

### Risk 2: Test Duplication
**Mitigation:**
- Identify duplicates during mapping phase
- Keep most comprehensive version
- Document removed duplicates in commit message

### Risk 3: Unclear Test Location
**Mitigation:**
- Document test organization in test README
- Use clear test file naming (functionality-based, not "EdgeCase")
- Group tests by functionality within files

## Out of Scope
- Refactoring test logic (only moving/organizing)
- Changing test assertions
- Adding new test scenarios (except for identified gaps)
- Performance optimization

## Success Criteria
- ✅ Reduce edge case test files from 5 to 0-1
- ✅ All tests passing (258+ tests)
- ✅ No coverage regression
- ✅ Clear test organization by functionality
- ✅ Test count maintained or increased

