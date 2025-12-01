# Tasks: File Field Type Support

## Implementation
- [ ] Create `config/flexyfield.php`
    - [ ] Define default disk and path
- [ ] Update `src/Enums/FlexyFieldType.php`
    - [ ] Add `FILE` case
- [ ] Create `src/Services/FileHandler.php`
    - [ ] Implement `upload`
    - [ ] Implement `delete`
    - [ ] Implement `getUrl`
- [ ] Update `src/Traits/Flexy.php`
    - [ ] Update `__set` to handle `UploadedFile`
    - [ ] Update `__set` to delete old file on update
    - [ ] Update `bootFlexy` (deleted event) to cleanup files
    - [ ] Add `getFlexyFileUrl(field)` helper
- [ ] Update `src/FlexyField.php`
    - [ ] Update pivot view logic to handle FILE type (map to value_string)

## Testing
- [ ] Create `tests/Feature/FileFieldTest.php`
    - [ ] Test file upload
    - [ ] Test file replacement (cleanup)
    - [ ] Test model deletion (cleanup)
    - [ ] Test custom disk/path metadata
    - [ ] Test validation rules
    - [ ] Test URL generation

## Quality Assurance
- [ ] Run `phpstan analyse`
- [ ] Run `pint`
- [ ] Run full test suite

## Documentation
- [ ] Update `README.md`
    - [ ] Add File Field section
    - [ ] Document configuration
- [ ] Update `docs/BEST_PRACTICES.md`
