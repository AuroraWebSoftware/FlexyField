# Implementation Tasks

## 1. Schema Tracking Infrastructure
- [ ] 1.1 Create migration for ff_view_schema table (id, field_name unique, added_at)
- [ ] 1.2 Test migration up/down
- [ ] 1.3 Add indexes on field_name

## 2. View Recreation Logic
- [ ] 2.1 Implement recreateViewIfNeeded() method in FlexyField class
- [ ] 2.2 Add logic to compare existing fields vs new fields
- [ ] 2.3 Insert new fields into ff_view_schema tracking table
- [ ] 2.4 Only call dropAndCreatePivotView() when new fields detected
- [ ] 2.5 Return boolean indicating if view was recreated

## 3. Force Recreation Method
- [ ] 3.1 Implement forceRecreateView() method
- [ ] 3.2 Truncate ff_view_schema table
- [ ] 3.3 Rebuild schema tracking from actual ff_values data
- [ ] 3.4 Recreate view
- [ ] 3.5 Add tests for force recreation

## 4. Update Trait Logic
- [ ] 4.1 Replace dropAndCreatePivotView() with recreateViewIfNeeded() in Flexy trait
- [ ] 4.2 Pass array of dirty field names to recreateViewIfNeeded()
- [ ] 4.3 Update deleted() event handler (don't remove from schema)
- [ ] 4.4 Test save operations don't recreate view unnecessarily
- [ ] 4.5 Test new field additions do trigger recreation

## 5. Artisan Command
- [ ] 5.1 Create RebuildFlexyViewCommand class
- [ ] 5.2 Set signature as flexyfield:rebuild-view
- [ ] 5.3 Call forceRecreateView() in handle method
- [ ] 5.4 Add user feedback messages
- [ ] 5.5 Register command in service provider
- [ ] 5.6 Test command execution

## 6. Batch Mode (Optional)
- [ ] 6.1 Implement withoutViewUpdates() static method
- [ ] 6.2 Add enable/disable toggle mechanism
- [ ] 6.3 Store state in static property
- [ ] 6.4 Modify recreateViewIfNeeded to check toggle
- [ ] 6.5 Add tests for batch mode

## 7. Performance Testing
- [ ] 7.1 Benchmark 1000 value updates (same field)
- [ ] 7.2 Benchmark 1000 value updates (new fields)
- [ ] 7.3 Verify 90%+ reduction in view recreations
- [ ] 7.4 Test query response times remain unchanged
- [ ] 7.5 Load testing under concurrent updates

## 8. Documentation
- [ ] 8.1 Update README with performance section
- [ ] 8.2 Create UPGRADE.md with migration instructions
- [ ] 8.3 Document flexyfield:rebuild-view command
- [ ] 8.4 Add best practices for bulk operations
- [ ] 8.5 Update CHANGELOG
