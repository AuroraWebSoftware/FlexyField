<?php

namespace AuroraWebSoftware\FlexyField\Database\Migrations\Concerns;

use Illuminate\Database\Schema\Blueprint;

/**
 * Helper trait for adding schema_code column to models using Flexy
 *
 * Usage in your migration:
 * ```php
 * use AuroraWebSoftware\FlexyField\Database\Migrations\Concerns\AddSchemaCodeColumn;
 *
 * return new class extends Migration
 * {
 *     use AddSchemaCodeColumn;
 *
 *     public function up(): void
 *     {
 *         $this->addSchemaCodeColumn('products');
 *     }
 *
 *     public function down(): void
 *     {
 *         $this->dropSchemaCodeColumn('products');
 *     }
 * };
 * ```
 */
trait AddSchemaCodeColumn
{
    /**
     * Add schema_code column to model table
     *
     * Note: Foreign key constraint is not added because schema_code is not unique by itself
     * in ff_schemas table (only ['model_type', 'schema_code'] is unique).
     * This ensures PostgreSQL compatibility. Referential integrity is maintained through
     * application-level constraints in the FieldSchema model's deleting event.
     */
    public function addSchemaCodeColumn(string $tableName): void
    {
        \Illuminate\Support\Facades\Schema::table($tableName, function (Blueprint $table) {
            $table->string('schema_code')->nullable()->index()->after('id');

            // Note: Foreign key removed for PostgreSQL compatibility
            // Cascading handled in FieldSchema model's deleting event
        });
    }

    /**
     * Remove schema_code column from model table
     */
    public function dropSchemaCodeColumn(string $tableName): void
    {
        \Illuminate\Support\Facades\Schema::table($tableName, function (Blueprint $table) {
            // Note: No foreign key to drop since it's not added
            $table->dropColumn('schema_code');
        });
    }
}
