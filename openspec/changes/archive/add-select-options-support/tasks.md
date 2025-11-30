# Tasks: Add Select Options Support

- [ ] Update `SchemaField` model <!-- id: 1 -->
    - [ ] Add `getOptions(): array` helper method <!-- id: 2 -->
    - [ ] Add `isMultiSelect(): bool` helper method <!-- id: 3 -->
    - [ ] Update `getValidationRulesArray()` to inject validation rules based on options <!-- id: 4 -->
- [ ] Add Tests <!-- id: 5 -->
    - [ ] Create `tests/Feature/SelectOptionsTest.php` <!-- id: 6 -->
    - [ ] Test single select validation (success/failure) <!-- id: 7 -->
    - [ ] Test multi-select validation (success/failure) <!-- id: 8 -->
    - [ ] Test key-value options validation <!-- id: 9 -->
- [ ] Update Documentation <!-- id: 10 -->
    - [ ] Update `README.md` with Select Options usage example <!-- id: 11 -->
    - [ ] Update `docs/BEST_PRACTICES.md` with recommendations for using options <!-- id: 12 -->
- [ ] Update UI Components <!-- id: 13 -->
    - [ ] Update `resources/boost/guidelines/core.blade.php` to reflect new select capabilities <!-- id: 14 -->
