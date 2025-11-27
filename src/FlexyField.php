<?php

namespace AuroraWebSoftware\FlexyField;

use Exception;
use Illuminate\Support\Facades\DB;

class FlexyField
{
    /**
     * @throws Exception
     */
    public static function dropAndCreatePivotView(): void
    {
        if (config('database.default') == 'mysql') {
            self::dropAndCreatePivotViewForMysql();
        } elseif (config('database.default') == 'pgsql') {
            self::dropAndCreatePivotViewForPostgres();
        }

    }

    public static function dropAndCreatePivotViewForMysql(): void
    {
        $columnsSql = "
            SET @sql = NULL;
            SELECT
                GROUP_CONCAT(
                    DISTINCT
                    CONCAT(
                        'MAX(CASE WHEN field_name = ''',
                        field_name,
                        ''' THEN COALESCE(value_date, value_datetime, value_decimal, value_int, value_string, value_boolean, value_json,  NULL) END) AS `flexy_',
                        field_name,
                        '`'
                    )
                ) INTO @sql
            FROM ff_values;
        ";

        $createViewSql = "
            -- Drop the view if it exists
            DROP VIEW IF EXISTS ff_values_pivot_view;

            -- Create the view with dynamic columns or empty view if no columns
            SET @create_view_sql = IF(
                @sql IS NOT NULL,
                CONCAT(
                    'CREATE VIEW ff_values_pivot_view AS ',
                    'SELECT model_type, model_id, ', @sql, ' ',
                    'FROM ff_values ',
                    'GROUP BY model_type, model_id'
                ),
                'CREATE VIEW ff_values_pivot_view AS SELECT model_type, model_id FROM ff_values WHERE 1=0'
            );

            PREPARE stmt FROM @create_view_sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ";

        // Execute SQL statements
        DB::unprepared($columnsSql);
        DB::unprepared($createViewSql);

    }

    /**
     * Recreate view only if new fields are detected
     *
     * @param  array<string>  $fieldNames  Array of field names to check
     * @return bool True if view was recreated, false if no recreation needed
     */
    public static function recreateViewIfNeeded(array $fieldNames): bool
    {
        if (empty($fieldNames)) {
            return false;
        }

        // Get existing fields from schema tracking table
        $existingFields = DB::table('ff_view_schema')
            ->pluck('field_name')
            ->toArray();

        // Find new fields that don't exist in schema
        $newFields = array_diff($fieldNames, $existingFields);

        if (empty($newFields)) {
            // No new fields, skip recreation
            return false;
        }

        // Insert new fields into tracking table (ignore duplicates)
        $timestamp = now();
        foreach ($newFields as $fieldName) {
            DB::table('ff_view_schema')->insertOrIgnore([
                'field_name' => $fieldName,
                'added_at' => $timestamp,
            ]);
        }

        // Recreate the view
        self::dropAndCreatePivotView();

        return true;
    }

    /**
     * Force recreation of the pivot view and rebuild schema tracking
     */
    public static function forceRecreateView(): void
    {
        // Truncate schema tracking table
        DB::table('ff_view_schema')->truncate();

        // Get all distinct field names from ff_values
        $allFields = DB::table('ff_values')
            ->select('field_name')
            ->distinct()
            ->pluck('field_name')
            ->toArray();

        // Insert all fields into tracking table
        if (! empty($allFields)) {
            $timestamp = now();
            $insertData = array_map(function ($fieldName) use ($timestamp) {
                return [
                    'field_name' => $fieldName,
                    'added_at' => $timestamp,
                ];
            }, $allFields);

            DB::table('ff_view_schema')->insert($insertData);
        }

        // Recreate the view
        self::dropAndCreatePivotView();
    }

    // TODO BOOLEAN values will be checked
    /**
     * @throws Exception
     */
    public static function dropAndCreatePivotViewForPostgres(): void
    {
        $columnsSql = "
DO $$
DECLARE
    sql TEXT;
BEGIN
    -- Concatenate column names using STRING_AGG for dynamic pivot column generation
SELECT STRING_AGG(
        'MAX(CASE WHEN field_name = ''' || field_name || ''' THEN ' ||
        'CASE ' ||
        'WHEN value_date IS NOT NULL THEN value_date::TEXT ' ||
        'WHEN value_datetime IS NOT NULL THEN value_datetime::TEXT ' ||
        'WHEN value_decimal IS NOT NULL THEN value_decimal::TEXT ' ||
        'WHEN value_int IS NOT NULL THEN value_int::TEXT ' ||
        'WHEN value_json IS NOT NULL THEN value_json::TEXT ' ||
        'WHEN value_boolean IS NOT NULL THEN CASE WHEN value_boolean THEN ''true'' ELSE ''false'' END ' ||
        'ELSE value_string END ' ||
        'END) AS \"flexy_' || field_name || '\"', ', ')
    INTO sql
    FROM (SELECT DISTINCT field_name FROM ff_values) AS distinct_fields;

    -- Prepare the view creation SQL statement
    EXECUTE 'DROP VIEW IF EXISTS ff_values_pivot_view';
    EXECUTE 'CREATE VIEW ff_values_pivot_view AS ' ||
            'SELECT model_type, model_id, ' || sql || ' ' ||
            'FROM ff_values ' ||
            'GROUP BY model_type, model_id';
END $$;
";

        // Execute SQL statements
        DB::unprepared($columnsSql);
    }
}
