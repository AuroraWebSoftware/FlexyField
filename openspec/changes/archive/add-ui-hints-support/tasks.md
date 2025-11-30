# Tasks: UI Hints Support

## Database Migration
- [ ] Create migration to add `label` column
- [ ] Add `label` to `$fillable` in SchemaField model

## Model Updates
- [ ] Update `src/Models/SchemaField.php`
    - [ ] Add `getLabel(): string` method (fallback to name)
    - [ ] Add `getPlaceholder(): ?string` method
    - [ ] Add `getHint(): ?string` method

## Testing
- [ ] Create `tests/Feature/UIHintsTest.php`
    - [ ] Test label storage and retrieval
    - [ ] Test label fallback to name
    - [ ] Test placeholder storage and retrieval
    - [ ] Test hint storage and retrieval
    - [ ] Test null/empty values

## Quality Assurance
- [ ] Run `phpstan analyse`
- [ ] Run `pint`
- [ ] Run full test suite

## Documentation Updates
- [ ] Update `README.md`
    - [ ] Add UI Hints section with usage examples
    - [ ] Show label, placeholder, and hint usage
- [ ] Update `docs/BEST_PRACTICES.md`
    - [ ] Add best practices for label naming
    - [ ] Add guidelines for placeholder and hint text
- [ ] Update `resources/boost/guidelines/core.blade.php`
    - [ ] Add UI Hints code snippets
    - [ ] Document label fallback behavior
- [ ] Update OpenSpec documentation
    - [ ] Update `openspec/project.md` if needed
    - [ ] Update metadata structure documentation
