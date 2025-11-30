# Tasks: Simple Localization Support

## Implementation
- [ ] Update `src/Models/SchemaField.php`
    - [ ] Add `isTranslatable(): bool` helper
- [ ] Update `src/Traits/Flexy.php`
    - [ ] Modify `__get` to handle localized retrieval
    - [ ] Modify `__set` to handle localized storage (merging)
    - [ ] Add `getTranslation(field, locale)` method
    - [ ] Add `getTranslations(field)` method
    - [ ] Add `setTranslation(field, locale, value)` method

## Testing
- [ ] Create `tests/Feature/SimpleLocalizationTest.php`
    - [ ] Test defining translatable field
    - [ ] Test setting/getting values in current locale
    - [ ] Test setting/getting specific locales
    - [ ] Test fallback locale behavior
    - [ ] Test bulk assignment (array)
    - [ ] Test validation (required, string constraints)

## Quality Assurance
- [ ] Run `phpstan analyse` (Larastan)
- [ ] Run `pint`

## Documentation
- [ ] Update `README.md` with localization examples
- [ ] Update `docs/BEST_PRACTICES.md`
