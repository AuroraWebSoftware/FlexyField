# Design: Add Select Options Support

## Goal
Enable restricting field values to a predefined set of options (single or multi-select) using metadata, enforcing validation at the model level.

## Technical Approach

### 1. Metadata Structure
The `metadata` JSON column in `ff_schema_fields` will store:
```json
{
    "options": ["Red", "Blue", "Green"],
    "multiple": true
}
```
OR key-value pairs:
```json
{
    "options": {"r": "Red", "b": "Blue"},
    "multiple": false
}
```

### 2. SchemaField Model Updates
- **`getOptions(): array`**: Returns the `options` array from metadata. Returns empty array if not set.
- **`isMultiSelect(): bool`**: Returns `true` if `multiple` key in metadata is true.
- **`getValidationRulesArray(): array`**:
    - Call parent/existing logic to get base rules.
    - If `getOptions()` is not empty:
        - If `isMultiSelect()` is true:
            - Add `array` rule.
            - Add `Rule::in(array_keys($options))` (if assoc) or `Rule::in($options)` (if indexed) applied to `*` (wildcard) or via `Rule::forEach`.
            - *Note:* Since `Validator` validates the field value, if it's an array, we need `['field' => 'array', 'field.*' => 'in:options']`.
            - However, `Flexy` trait uses `Validator::make($data, [$field => $rules])`.
            - So for multi-select, the rules for `$field` should be `['array']`. And we might need to add a separate rule for `$field.*`.
            - *Constraint:* `getValidationRulesArray` returns a list of rules for the field itself.
            - *Solution:* Use a Closure rule or `Rule::forEach` if supported by the context, or simply `Rule::in` if the value is scalar.
            - For array values, standard Laravel validation for `field.*` is usually defined as a separate key in the rules array.
            - *Problem:* `Flexy` trait currently only pulls rules for the field name: `$rules = [$field => $validationRules];`.
            - *Refinement:* We might need to update `Flexy` trait to handle nested rules if we want to use standard Laravel `field.*` syntax.
            - *Alternative:* Use a custom Closure rule that checks if the value is an array and all items are in options. This keeps `Flexy` trait unchanged.

### 3. Validation Logic (Closure Rule)
To avoid modifying `Flexy` trait's rule construction logic significantly, we will append a Closure rule in `getValidationRulesArray`:

```php
if ($this->hasOptions()) {
    $options = $this->getOptions();
    $allowedValues = array_is_list($options) ? $options : array_keys($options);
    
    if ($this->isMultiSelect()) {
        $rules[] = 'array';
        $rules[] = function ($attribute, $value, $fail) use ($allowedValues) {
            if (!is_array($value)) return; // 'array' rule handles this
            foreach ($value as $item) {
                if (!in_array($item, $allowedValues)) {
                    $fail("The selected $attribute is invalid.");
                }
            }
        };
    } else {
        $rules[] = Rule::in($allowedValues);
    }
}
```

### 4. Storage
- **Single Select:** Stored as usual in `value_string`, `value_int`, etc.
- **Multi Select:** Stored in `value_json`. The `Flexy` trait must handle assigning array values to `value_json` column automatically if the field type is `JSON`.
- *Requirement:* Users must define the field type as `JSON` if they want `multiple: true`. We should enforce or document this.

## Corner Cases
- **Type Mismatch:** If `multiple: true` but field type is `STRING`, `Flexy` trait might try to store array in string column (error).
    - *Mitigation:* `Flexy` trait logic for `JSON` type handles arrays. If type is `STRING`, it expects string.
    - *Decision:* If `isMultiSelect()` is true, we should probably force the value to be stored in `value_json` regardless of declared type, OR strictly require the schema definition to be `FlexyFieldType::JSON`. The latter is cleaner.

