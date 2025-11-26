# Project Context

## Purpose
FlexyField is a Laravel package that enables dynamic field management for Eloquent models without requiring database schema modifications. It provides a flexible, type-safe solution for adding custom fields to models at runtime, with built-in validation and query support. The package is designed for projects that need flexible content structures, such as e-commerce platforms with varying product attributes, multi-tenant applications, or content management systems.

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
  - Database tables: snake_case with `ff_` prefix (e.g., `ff_shapes`, `ff_values`)
  - Flexy field accessors: `flexy_` prefix (e.g., `$model->flexy_color`)
- **Documentation**: PHPDoc blocks for all public methods, especially those with exceptions
- **Type Hints**: Strict typing required for all parameters and return types

### Architecture Patterns
- **Trait-Based Implementation**: Uses `Flexy` trait to add functionality to models
- **Contract-Based Design**: Models implement `FlexyModelContract` interface
- **EAV Pattern**: Entity-Attribute-Value pattern for dynamic field storage
- **Database Structure**:
  - `ff_shapes`: Field definitions with validation rules
  - `ff_values`: Actual field values with polymorphic relations
  - `ff_values_pivot_view`: Database view for efficient querying
- **Global Scopes**: Automatic left joins to pivot view for seamless querying
- **Event Hooks**: Model `saving` events for validation and value persistence
- **Enum-Based Types**: `FlexyFieldType` enum for type safety (STRING, INTEGER, DECIMAL, DATE, BOOLEAN, DATETIME, JSON)
- **Magic Accessors**: Dynamic property access via `__get` and `__set` methods

### Testing Strategy
- **Framework**: Pest PHP with architecture testing plugin
- **Coverage**: Unit tests for all core functionality
- **CI/CD Matrix Testing**:
  - PHP versions: 8.2, 8.3
  - Laravel versions: 10.x, 11.x
  - Dependencies: prefer-stable and prefer-lowest
- **Database Testing**: MySQL 8.0 service in GitHub Actions
- **Test Models**: `ExampleFlexyModel` and `ExampleShapelyFlexyModel` for testing
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
- Values are strongly typed (separate columns for each data type)
- Validation is enforced through "shapes" (field definitions)
- Querying is optimized via database views for performance
- Seamlessly integrates with Laravel's query builder and Eloquent

### Field Types and Storage
- **STRING**: Stored in `value_string` column
- **INTEGER**: Stored in `value_int` column
- **DECIMAL**: Stored in `value_decimal` column
- **DATE/DATETIME**: Stored in `value_datetime` column (Carbon instances)
- **BOOLEAN**: Stored in `value_boolean` column
- **JSON**: Stored in `value_json` column (arrays/objects)

### Shapes System
"Shapes" define the schema for flexy fields:
- Each model type can have multiple shapes (one per field)
- Shapes include: field name, type, sort order, validation rules, and messages
- When a shape exists, field values are validated before saving
- Shapes are optional; fields can be set without predefined shapes

### Query Integration
Flexy fields are queryable using standard Eloquent methods:
- `where('flexy_fieldname', 'value')`
- `whereFlexyFieldname('value')` (dynamic where methods)
- Automatic joins via global scopes ensure performance

## Important Constraints
- **PHP Version**: Minimum PHP 8.2 required
- **Laravel Compatibility**: Only supports Laravel 10.x and 11.x
- **Database**: MySQL 8.0+ required (uses database views which may not be compatible with all databases)
- **Polymorphic Relations**: Uses `model_type` and `model_id` for polymorphic association
- **Performance**: Large numbers of flexy fields may impact query performance due to wide pivot view
- **Schema Flexibility**: While no schema changes needed for new fields, the base tables must be migrated

## External Dependencies
- **Spatie Laravel Package Tools**: Package boilerplate and service provider utilities
- **Laravel Framework**: Core dependency for Eloquent, validation, events
- **Orchestra Testbench**: Development dependency for testing Laravel packages
- **Pest PHP Ecosystem**: Testing framework with Laravel and architecture plugins
- **Larastan**: Laravel-specific PHPStan wrapper for static analysis
- **Carbon**: Laravel's default date/time library for date field handling
