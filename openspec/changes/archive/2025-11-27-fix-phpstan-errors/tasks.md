## 1. Fix Contract Type Annotations
- [x] 1.1 Add generic types to `BelongsTo` return type in `FlexyModelContract::fieldSet()`
- [x] 1.2 Add value type specifications to `$metadata` parameter in `FlexyModelContract::createFieldSet()`
- [x] 1.3 Add value type specifications to `$fieldMetadata` parameter in `FlexyModelContract::addFieldToSet()`
- [x] 1.4 Add value type specifications to `$validationMessages` parameter in `FlexyModelContract::addFieldToSet()`

## 2. Fix Exception Type Annotations
- [x] 2.1 Add value type specification to `$availableFields` parameter in `FieldNotInSetException::forField()`

## 3. Fix FlexyField Type Annotations
- [x] 3.1 Add value type specification to `$fieldNames` parameter in `FlexyField::recreateViewIfNeeded()`

## 4. Fix FieldSet Model Type Annotations
- [x] 4.1 Add `@property` PHPDoc annotations for `$is_default`, `$model_type`, and `$set_code` properties
- [x] 4.2 Add generic type to `HasMany` return type in `FieldSet::fields()`
- [x] 4.3 Add return type and parameter type to `FieldSet::scopeForModel()`
- [x] 4.4 Add return type and parameter type to `FieldSet::scopeDefault()`
- [x] 4.5 Fix `getUsageCount()` method to properly type the model class parameter

## 5. Fix SetField Model Type Annotations
- [x] 5.1 Add `@property` PHPDoc annotation for `$field_type` property
- [x] 5.2 Add generic type to `BelongsTo` return type in `SetField::fieldSet()`
- [x] 5.3 Add parameter type to `SetField::validateFieldType()`
- [x] 5.4 Add value type specification to return type of `SetField::getValidationRulesArray()`

## 6. Fix Value Model Type Annotations
- [x] 6.1 Add generic type to `BelongsTo` return type in `Value::fieldSet()`

## 7. Validation
- [x] 7.1 Run PHPStan analysis: `vendor/bin/phpstan analyse`
- [x] 7.2 Verify all 23 errors are resolved
- [x] 7.3 Ensure no new errors are introduced
- [x] 7.4 Run test suite to ensure no runtime regressions: `vendor/bin/pest`
- [x] 7.5 Verify code style: `vendor/bin/pint --test`

