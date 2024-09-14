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
                        ''' THEN COALESCE(value_date, value_datetime, value_decimal, value_int, value_string, value_boolean, NULL) END) AS `flexy_',
                        field_name,
                        '`'
                    )
                ) INTO @sql
            FROM ff_values;
        ";

        $createViewSql = "
            SET @create_view_sql = CONCAT(
                'CREATE VIEW ff_values_pivot_view AS ',
                'SELECT model_type, model_id, ', @sql, ' ',
                'FROM ff_values ',
                'GROUP BY model_type, model_id'
            );

            -- Drop the view if it exists
            SET @drop_view_sql = 'DROP VIEW IF EXISTS flexyfield.ff_values_pivot_view';
            PREPARE stmt FROM @drop_view_sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;

            -- Create the view
            PREPARE stmt FROM @create_view_sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ";

        // Execute SQL statements
        DB::unprepared($columnsSql);
        DB::unprepared($createViewSql);

    }

    //TODO BOOLEAN values will be checked
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
