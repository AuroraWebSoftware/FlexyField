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
     * Add field_set_code column with foreign key to model table
     */
    public static function add(Blueprint $table): void
    {
        $table->string('field_set_code')->nullable()->index()->after('id');

        $table->foreign('field_set_code')
            ->references('set_code')
            ->on('ff_field_sets')
            ->onDelete('set null')
            ->onUpdate('cascade');
    }

    /**
     * Remove field_set_code column from model table
     */
    public static function remove(Blueprint $table): void
    {
        $table->dropForeign(['field_set_code']);
        $table->dropColumn('field_set_code');
    }
}
