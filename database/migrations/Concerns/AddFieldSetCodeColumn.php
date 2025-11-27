<?php

namespace AuroraWebSoftware\FlexyField\Database\Migrations\Concerns;

use Illuminate\Database\Schema\Blueprint;

/**
 * Helper trait for adding field_set_code column to models using Flexy
 *
 * Usage in your migration:
 * ```php
 * use AuroraWebSoftware\FlexyField\Database\Migrations\Concerns\AddFieldSetCodeColumn;
 *
 * Schema::table('products', function (Blueprint $table) {
 *     AddFieldSetCodeColumn::add($table);
 * });
 * ```
 */
trait AddFieldSetCodeColumn
{
    /**
     * Add field_set_code column to model table
     *
     * Note: Foreign key constraint is not added because set_code is not unique by itself
     * in ff_field_sets table (only ['model_type', 'set_code'] is unique).
     * This ensures PostgreSQL compatibility. Referential integrity is maintained through
     * application-level constraints in the FieldSet model's deleting event.
     */
    public static function add(Blueprint $table): void
    {
        $table->string('field_set_code')->nullable()->index()->after('id');

        // Note: Foreign key removed for PostgreSQL compatibility
        // Cascading handled in FieldSet model's deleting event
    }

    /**
     * Remove field_set_code column from model table
     */
    public static function remove(Blueprint $table): void
    {
        // Note: No foreign key to drop since it's not added
        $table->dropColumn('field_set_code');
    }
}
