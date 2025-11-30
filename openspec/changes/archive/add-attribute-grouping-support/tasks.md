# Tasks: Attribute Grouping Support

## Implementation
- [ ] Update `src/Models/SchemaField.php`
    - [ ] Add `getGroup(): ?string` method
    - [ ] Add `hasGroup(): bool` method
- [ ] Update `src/Models/FieldSchema.php`
    - [ ] Add `getFieldsGrouped(): Collection` method

## Testing
- [ ] Create `tests/Feature/AttributeGroupingTest.php`
    - [ ] Test assigning groups via `addFieldToSchema`
    - [ ] Test `getGroup()` and `hasGroup()` helpers
    - [ ] Test `getFieldsGrouped()` returns correct structure
    - [ ] Test mixed grouped and ungrouped fields
    - [ ] Test empty/null groups

## Quality Assurance
- [ ] Run `phpstan analyse` (Larastan) - Ensure strict type safety
- [ ] Run `pint` - Ensure code style compliance
- [ ] Verify no regression in existing tests

## Documentation
- [ ] Update `README.md` with grouping examples
- [ ] Update `docs/BEST_PRACTICES.md`
