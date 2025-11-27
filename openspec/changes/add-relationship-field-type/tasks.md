## 1. Database Schema Updates
- [ ] 1.1 Add `value_related_model_type` and `value_related_model_id` columns to `ff_values` table migration
- [ ] 1.2 Add composite index on relationship columns for query performance
- [ ] 1.3 Update migration to handle existing installations (nullable columns)

## 2. Type System Updates
- [ ] 2.1 Add `RELATIONSHIP` case to `FlexyFieldType` enum
- [ ] 2.2 Update type detection logic in `Flexy` trait to recognize Eloquent model instances
- [ ] 2.3 Add validation to ensure assigned model is a valid Eloquent model instance
- [ ] 2.4 Update type storage logic to handle relationship values

## 3. Storage Implementation
- [ ] 3.1 Update `Value::updateOrCreate()` logic to store relationship type and ID
- [ ] 3.2 Implement relationship value retrieval in `Flexy` trait accessor
- [ ] 3.3 Add `getRelatedModel()` method to `Value` model
- [ ] 3.4 Handle null relationship values gracefully

## 4. Field Set Configuration
- [ ] 4.1 Update `addFieldToSet()` to accept relationship metadata (related_model, relationship_type, cascade_delete)
- [ ] 4.2 Add validation for relationship field definitions
- [ ] 4.3 Store relationship metadata in `SetField.field_metadata` JSON column
- [ ] 4.4 Add helper methods to retrieve relationship configuration

## 5. Query Integration
- [ ] 5.1 Update pivot view generation to include relationship fields
- [ ] 5.2 Support querying by related model ID via `where('flexy_fieldname', $modelId)`
- [ ] 5.3 Add query scope for filtering by related model type
- [ ] 5.4 Update global scope to handle relationship columns in pivot view

## 6. Validation and Error Handling
- [ ] 6.1 Validate that referenced model exists when saving relationship field
- [ ] 6.2 Validate that related_model in field metadata is a valid Eloquent model class
- [ ] 6.3 Add exception for invalid relationship assignments
- [ ] 6.4 Handle deleted related models (orphaned references)

## 7. Cascade Delete Support
- [ ] 7.1 Implement cascade delete logic when related model is deleted
- [ ] 7.2 Add event listener for model deletion to clean up relationship fields
- [ ] 7.3 Make cascade delete optional per field via metadata
- [ ] 7.4 Test cascade delete behavior

## 8. Testing
- [ ] 8.1 Unit tests for relationship type detection
- [ ] 8.2 Unit tests for relationship storage and retrieval
- [ ] 8.3 Feature tests for one-to-one relationships
- [ ] 8.4 Feature tests for one-to-many relationships
- [ ] 8.5 Feature tests for relationship querying
- [ ] 8.6 Feature tests for cascade delete
- [ ] 8.7 Edge case tests (null values, deleted models, invalid references)
- [ ] 8.8 Performance tests for relationship queries

## 9. Documentation
- [ ] 9.1 Update README with relationship field examples
- [ ] 9.2 Document relationship field configuration
- [ ] 9.3 Document querying by relationships
- [ ] 9.4 Add examples for common relationship patterns

## 10. Migration and Backward Compatibility
- [ ] 10.1 Test migration on existing installations
- [ ] 10.2 Verify backward compatibility (existing fields unaffected)
- [ ] 10.3 Update pivot view recreation to handle relationship fields
- [ ] 10.4 Test rollback of migration

