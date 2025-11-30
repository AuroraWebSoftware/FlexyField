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
        // Get field names from ff_view_schema (tracks all fields that have been added)
        // Fallback to ff_field_values if view_schema is empty (for backward compatibility)
        $fieldNames = DB::table('ff_view_schema')
            ->pluck('name')
            ->toArray();

        // If no fields in tracking table, get from actual values (backward compatibility)
        if (empty($fieldNames)) {
            $fieldNames = DB::table('ff_field_values')
                ->select('name')
                ->distinct()
                ->pluck('name')
                ->toArray();
        }

        if (empty($fieldNames)) {
            // Create empty view structure when no fields exist
            DB::statement('DROP VIEW IF EXISTS ff_values_pivot_view');
            DB::statement('CREATE VIEW ff_values_pivot_view AS SELECT model_type, model_id FROM ff_field_values WHERE 1=0');

            return;
        }

        // Build column definitions
        $columns = [];
        foreach ($fieldNames as $fieldName) {
            $columns[] = "MAX(CASE WHEN name = '{$fieldName}' THEN COALESCE(value_date, value_datetime, value_decimal, value_int, value_string, value_boolean, value_json, NULL) END) AS `flexy_{$fieldName}`";
        }

        $columnsSql = implode(', ', $columns);

        // Drop and create view
        DB::statement('DROP VIEW IF EXISTS ff_values_pivot_view');
        DB::statement("CREATE VIEW ff_values_pivot_view AS SELECT model_type, model_id, {$columnsSql} FROM ff_field_values GROUP BY model_type, model_id");
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
            ->pluck('name')
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
                'name' => $fieldName,
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

        // Get all distinct field names from ff_field_values
        $allFields = DB::table('ff_field_values')
            ->select('name')
            ->distinct()
            ->pluck('name')
            ->toArray();

        // Insert all fields into tracking table
        if (! empty($allFields)) {
            $timestamp = now();
            $insertData = array_map(function ($fieldName) use ($timestamp) {
                return [
                    'name' => $fieldName,
                    'added_at' => $timestamp,
                ];
            }, $allFields);

            DB::table('ff_view_schema')->insert($insertData);
        }

        // Recreate the view
        self::dropAndCreatePivotView();
    }

    /**
     * @throws Exception
     */
    public static function dropAndCreatePivotViewForPostgres(): void
    {
        // Get field names from ff_view_schema (tracks all fields that have been added)
        // Fallback to ff_field_values if view_schema is empty (for backward compatibility)
        $fieldNames = DB::table('ff_view_schema')
            ->pluck('name')
            ->toArray();

        // If no fields in tracking table, get from actual values (backward compatibility)
        if (empty($fieldNames)) {
            $fieldNames = DB::table('ff_field_values')
                ->select('name')
                ->distinct()
                ->pluck('name')
                ->toArray();
        }

        if (empty($fieldNames)) {
            // Create empty view structure when no fields exist
            DB::statement('DROP VIEW IF EXISTS ff_values_pivot_view');
            DB::statement('CREATE VIEW ff_values_pivot_view AS SELECT model_type, model_id FROM ff_field_values WHERE FALSE');

            return;
        }

        // Get field types from ff_schema_fields
        $fieldTypes = DB::table('ff_schema_fields')
            ->select('name', 'type')
            ->whereIn('name', $fieldNames)
            ->pluck('type', 'name')
            ->toArray();

        // Build column definitions for each field
        $columns = [];
        foreach ($fieldNames as $fieldName) {
            $fieldType = $fieldTypes[$fieldName] ?? 'STRING';
            // type is stored as string in database, but handle enum case if needed
            if ($fieldType instanceof \BackedEnum) {
                $fieldTypeStr = (string) $fieldType->value;
            } else {
                $fieldTypeStr = (string) $fieldType;
            }

            // Build CASE statement based on field type
            switch (strtoupper($fieldTypeStr)) {
                case 'BOOLEAN':
                    // Cast boolean to integer for MAX(), then cast back to boolean
                    $columnExpr = "MAX(CASE WHEN name = '{$fieldName}' THEN value_boolean::INTEGER END)::BOOLEAN";
                    break;
                case 'DATE':
                    // DATE fields are stored in value_date column
                    $columnExpr = "MAX(CASE WHEN name = '{$fieldName}' THEN value_date END)";
                    break;
                case 'DATETIME':
                    // Keep as TIMESTAMP type for proper datetime comparisons
                    $columnExpr = "MAX(CASE WHEN name = '{$fieldName}' THEN value_datetime END)";
                    break;
                case 'DECIMAL':
                    $columnExpr = "MAX(CASE WHEN name = '{$fieldName}' THEN value_decimal::TEXT END)";
                    break;
                case 'INTEGER':
                    $columnExpr = "MAX(CASE WHEN name = '{$fieldName}' THEN value_int::TEXT END)";
                    break;
                case 'JSON':
                    $columnExpr = "MAX(CASE WHEN name = '{$fieldName}' THEN value_json::TEXT END)";
                    break;
                default:
                    // For STRING or unknown types, use COALESCE to get the first non-null value
                    $columnExpr = "MAX(CASE WHEN name = '{$fieldName}' THEN COALESCE(value_string, value_date::TEXT, value_datetime::TEXT, value_decimal::TEXT, value_int::TEXT, value_json::TEXT, CASE WHEN value_boolean IS NOT NULL THEN CASE WHEN value_boolean THEN 'true' ELSE 'false' END ELSE NULL END) END)";
                    break;
            }

            $columns[] = "{$columnExpr} AS \"flexy_{$fieldName}\"";
        }

        $columnsSql = implode(', ', $columns);

        // Drop and create view
        DB::statement('DROP VIEW IF EXISTS ff_values_pivot_view');
        DB::statement("CREATE VIEW ff_values_pivot_view AS SELECT model_type, model_id, {$columnsSql} FROM ff_field_values GROUP BY model_type, model_id");
    }
}
