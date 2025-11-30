# Project Context

## Purpose
FlexyField is a Laravel package that enables dynamic field management for Eloquent models without requiring database schema modifications. It provides a flexible, type-safe solution for adding custom fields to models at runtime, with built-in validation and query support.

**Primary Use Cases:**
- E-commerce platforms with varying product attributes (e.g., shoes with size/color vs books with author/ISBN)
- Multi-tenant applications requiring different field configurations per tenant
- Content management systems needing flexible content structures
- Any Laravel application requiring runtime field definitions without migrations

**Key Concepts:**
- **Schemas**: Collections of field definitions that can be assigned to model instances, allowing different instances of the same model to have different field configurations
- **EAV Pattern**: Entity-Attribute-Value pattern implementation for storing dynamic fields
- **Type Safety**: Strongly-typed storage with separate columns per data type (STRING, INTEGER, DECIMAL, DATE, DATETIME, BOOLEAN, JSON)
- **Validation**: Field-level validation using Laravel's validation rules, enforced through Schemas

## Tech Stack
- PHP 8.2+
- Laravel Framework 10.x/11.x
- MySQL 8.0 (required for database views)
- Spatie Laravel Package Tools
- Pest PHP (testing framework)
- Larastan/PHPStan (static analysis)
- Laravel Pint (code formatting)
- Orchestra Testbench (package testing)
- Carbon (date/time handling)

## Project Conventions

### Code Style
- **Formatting**: Laravel Pint with default Laravel preset
- **Autoloading**: PSR-4 standard
- **Namespace**: `AuroraWebSoftware\FlexyField`
- **Naming Conventions**:
  - Classes: PascalCase
  - Methods: camelCase
  - Database tables: snake_case with `ff_` prefix (e.g., `ff_schemas`, `ff_field_values`)
  - Flexy field accessors: `flexy_` prefix (e.g., `$model->flexy_color`)
- **Documentation**: PHPDoc blocks for all public methods, especially those with exceptions
- **Type Hints**: Strict typing required for all parameters and return types

### Architecture Patterns

**Trait-Based Implementation**
- Uses `Flexy` trait (`src/Traits/Flexy.php`) to add functionality to models
- Models must implement `FlexyModelContract` interface (`src/Contracts/FlexyModelContract.php`)
- Example: `class Product extends Model implements FlexyModelContract { use Flexy; }`

**Contract-Based Design**
- All flexy-enabled models implement `FlexyModelContract` interface
- Ensures consistent API across different model types
- Defines required methods: `getSchemaCode()`, `assignToSchema()`, etc.

**EAV (Entity-Attribute-Value) Pattern**
- Implements flexible EAV pattern specifically designed for Laravel Eloquent
- Values are strongly typed (separate columns for each data type)
- Validation is enforced through Schemas (field definitions)
- Querying is optimized via database views for performance
- Seamlessly integrates with Laravel's query builder and Eloquent

**Database Structure**

*Primary Tables:*
- `ff_schemas`: Stores schema definitions per model type (replaces legacy `ff_shapes` concept)
  - Columns: `id`, `model_type`, `schema_code`, `label`, `description`, `metadata` (JSON), `is_default` (boolean), `timestamps`
  - Unique constraint: `(model_type, schema_code)`
  - Indexes: `model_type`, `schema_code`, `is_default`
  - One default schema per model type (enforced in application logic via model events)
  - Related model: `src/Models/FieldSchema.php`

- `ff_schema_fields`: Stores field definitions within schemas
  - Columns: `id`, `schema_code`, `schema_id` (FK), `name`, `type`, `sort` (integer, default 100), `validation_rules` (text), `validation_messages` (JSON), `metadata` (JSON), `timestamps`
  - Unique constraint: `(schema_code, name)`
  - Indexes: `schema_code`, `name`
  - Foreign key: `schema_id` → `ff_schemas.id` (CASCADE on delete)
  - Related model: `src/Models/SchemaField.php`

- `ff_field_values`: Stores actual field values with polymorphic relations
  - Columns: `id`, `model_type`, `model_id`, `name`, `schema_code` (nullable), `schema_id` (nullable FK), typed value columns (`value_string`, `value_int`, `value_decimal`, `value_datetime`, `value_boolean`, `value_json`), `timestamps`
  - Unique constraint: `(model_type, model_id, name)`
  - Indexes: `model_type`, `model_id`, `name`, `schema_code`
  - Foreign key: `schema_id` → `ff_schemas.id` (SET NULL on delete, nullable for backward compatibility)
  - Related model: `src/Models/FieldValue.php`

*Supporting Tables:*
- `ff_view_schema`: Tracks field definitions for optimized view recreation
  - Columns: `id`, `name` (unique), `added_at` (timestamp)
  - Used to detect when new fields are added (triggers view recreation)
  - Prevents unnecessary view recreations on every save

*Database Views:*
- `ff_values_pivot_view`: Database view that pivots all values for efficient querying
  - Dynamically recreated when new fields are added (detected via `ff_view_schema`)
  - Creates columns like `flexy_color`, `flexy_size`, etc. for each unique field name
  - Used by global scopes for automatic left joins
  - Recreation command: `php artisan flexyfield:rebuild-view`
  - Implementation: `src/FlexyField.php::dropAndCreatePivotView()`


**View Recreation Mechanism:**
- View is only recreated when new fields are detected (not on every save)
- Detection: When a new field name appears in `ff_field_values`, it's added to `ff_view_schema`
- Recreation: `FlexyField::dropAndCreatePivotView()` drops and recreates the view with all current fields
- Performance: ~98% reduction in overhead compared to recreating on every save
- Manual rebuild: `php artisan flexyfield:rebuild-view` command available

**Migration Helpers:**
- `AddSchemaCodeColumn` trait: `database/migrations/Concerns/AddSchemaCodeColumn.php`
  - Provides `addSchemaCodeColumn($tableName)` and `dropSchemaCodeColumn($tableName)` methods
  - Adds `schema_code` column with proper indexing
  - Usage: `use AddSchemaCodeColumn; $this->addSchemaCodeColumn('products');`

**Global Scopes**
- Automatic left joins to `ff_values_pivot_view` for seamless querying
- Enables `where('flexy_fieldname', 'value')` syntax
- Implemented in `src/Traits/Flexy.php`

**Event Hooks**
- Model `saving` events trigger validation and value persistence
- Validation uses Schema definitions
- Values are stored in appropriate typed columns based on field type

**Enum-Based Types**
- `FlexyFieldType` enum (`src/Enums/FlexyFieldType.php`) for type safety
- Supported types: STRING, INTEGER, DECIMAL, DATE, BOOLEAN, DATETIME, JSON
- Type determines which column in `ff_field_values` table is used

**Magic Accessors**
- Dynamic property access via `__get` and `__set` methods
- Access via `$model->flexy->fieldname` or `$model->flexy_fieldname`
- Implemented in `src/Traits/Flexy.php`

### Testing Strategy
- **Framework**: Pest PHP with architecture testing plugin
- **Coverage**: Unit tests for all core functionality
- **CI/CD Matrix Testing**:
  - PHP versions: 8.2, 8.3
  - Laravel versions: 10.x, 11.x
  - Dependencies: prefer-stable and prefer-lowest
- **Database Testing**: MySQL 8.0 service in GitHub Actions
- **Test Models**: `ExampleFlexyModel` for testing
- **Commands**:
  - Run tests: `./vendor/bin/pest`
  - Coverage: `./vendor/bin/pest --coverage`
  - Static analysis: `./vendor/bin/phpstan analyse`
  - Code style: `./vendor/bin/pint`

### Git Workflow
- **Main Branch**: `main` (primary development and release branch)
- **CI/CD Automation**:
  - Automated tests on every push (PHP files, workflows, composer files)
  - PHPStan static analysis workflow
  - Automated code style fixes via GitHub Actions
- **Commit Conventions**: Follow standard commit message practices
- **Pull Requests**: Should target `main` branch
- **Quality Gates**: All tests, PHPStan, and code style checks must pass

## Domain Context

### EAV (Entity-Attribute-Value) Pattern
FlexyField implements a flexible EAV pattern specifically designed for Laravel Eloquent. Unlike traditional EAV implementations:
- **Strongly Typed Storage**: Values are stored in separate columns for each data type (not a single generic value column)
- **Field Set Validation**: Validation is enforced through Field Sets (collections of field definitions), not individual "shapes"
- **Optimized Querying**: Querying is optimized via database views (`ff_values_pivot_view`) for performance
- **Laravel Integration**: Seamlessly integrates with Laravel's query builder and Eloquent ORM

**How It Works:**
1. Schemas define which fields are available for a model instance
2. Each field has a type (STRING, INTEGER, etc.) and optional validation rules
3. When a model instance is assigned to a Schema, only fields from that schema can be set
4. Values are stored in `ff_field_values` table with polymorphic relations (`model_type`, `model_id`)
5. The pivot view enables efficient querying without complex joins

### Schemas System (Replaces Legacy Shapes)
**Schemas** define the schema for flexy fields and replace the legacy "Shapes" concept:
- **Multiple Schemas Per Model**: Each model type can have multiple schemas (e.g., 'footwear', 'books', 'clothing')
- **Instance Assignment**: Each model instance is assigned to one schema via `schema_code` column
- **Field Definitions**: Each schema contains multiple fields with: field name, type, sort order, validation rules, and messages
- **Validation Enforcement**: When a model instance is assigned to a schema, only fields from that schema can be set, and validation rules are enforced
- **Default Schemas**: One schema per model type can be marked as default, automatically assigned to new instances


### Field Types and Storage
Each field type maps to a specific column in the `ff_values` table:
- **STRING**: Stored in `value_string` column (VARCHAR)
- **INTEGER**: Stored in `value_int` column (BIGINT)
- **DECIMAL**: Stored in `value_decimal` column (DECIMAL) - maintains precision
- **DATE**: Stored in `value_datetime` column (DATETIME) - retrieved as Carbon instance, time set to 00:00:00
- **DATETIME**: Stored in `value_datetime` column (DATETIME) - retrieved as Carbon instance with full timestamp
- **BOOLEAN**: Stored in `value_boolean` column (BOOLEAN) - true/false, not 1/0
- **JSON**: Stored in `value_json` column (JSON) - arrays/objects are JSON encoded

**Type Detection:**
- Automatic type detection based on PHP value type when no field set is assigned
- Explicit type enforcement when field is defined in a field set

### Query Integration
Flexy fields are queryable using standard Eloquent methods:
- **Direct Where**: `where('flexy_fieldname', 'value')` - works via global scope
- **Dynamic Where**: `whereFlexyFieldname('value')` - Laravel's dynamic where methods
- **Multiple Conditions**: Chain multiple flexy field conditions
- **Schema Filtering**: `whereSchema('schema_code')`, `whereInSchema(['schema1', 'schema2'])`, `whereDoesntHaveSchema()`
- **Performance**: Automatic joins via global scopes to `ff_values_pivot_view` ensure efficient querying

**Example:**
```php
Product::where('flexy_color', 'blue')
    ->where('flexy_price', '<', 100)
    ->whereSchema('footwear')
    ->get();
```

## Important Constraints

**Technical Constraints:**
- **PHP Version**: Minimum PHP 8.2 required (strict typing, enums, readonly properties)
- **Laravel Compatibility**: Only supports Laravel 10.x and 11.x (tested in CI/CD)
- **Database**: MySQL 8.0+ or PostgreSQL 16+ required (uses database views which may not be compatible with all databases)
- **Polymorphic Relations**: Uses `model_type` (string) and `model_id` (integer) for polymorphic association in `ff_field_values` table

**Performance Constraints:**
- **Pivot View Width**: Large numbers of flexy fields (100+) may impact query performance due to wide pivot view
- **View Recreation**: View is recreated when new fields are added (optimized to only recreate on field addition, not on every save)
- **Indexing**: Proper indexing on `model_type`, `model_id`, `name`, and `schema_code` is critical for performance

**Schema Constraints:**
- **Base Tables**: While no schema changes needed for new fields, the base `ff_*` tables must be migrated
- **Model Tables**: Models using FlexyField must have `schema_code` column added via migration
- **Schema Assignment**: Models must be assigned to a schema before setting flexy field values (enforced in validation)

**API Constraints:**
- **Schema Required**: Cannot set flexy fields without schema assignment (throws `SchemaNotFoundException`)
- **Field Validation**: Fields not in assigned schema cannot be set (throws `FieldNotInSchemaException`)
- **Type Safety**: Field types must match Schema definition (enforced in validation)

**Related Specifications:**
- See `openspec/specs/dynamic-field-storage/spec.md` for storage requirements
- See `openspec/specs/field-set-management/spec.md` for schema management
- See `openspec/specs/field-validation/spec.md` for validation rules
- See `openspec/specs/query-integration/spec.md` for query capabilities

## External Dependencies
- **Spatie Laravel Package Tools**: Package boilerplate and service provider utilities
- **Laravel Framework**: Core dependency for Eloquent, validation, events
- **Orchestra Testbench**: Development dependency for testing Laravel packages
- **Pest PHP Ecosystem**: Testing framework with Laravel and architecture plugins
- **Larastan**: Laravel-specific PHPStan wrapper for static analysis
- **Carbon**: Laravel's default date/time library for date field handling
