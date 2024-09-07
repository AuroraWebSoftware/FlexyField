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
                        ''' THEN COALESCE(value_date, value_datetime, value_decimal, value_int, value_string, NULL) END) AS `flexy_',
                        field_name,
                        '`'
                    )
                ) INTO @sql
            FROM flexyfield.ff_values;
        ";

        $createViewSql = "
            SET @create_view_sql = CONCAT(
                'CREATE VIEW flexyfield.ff_values_pivot_view AS ',
                'SELECT model_type, model_id, ', @sql, ' ',
                'FROM flexyfield.ff_values ',
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

    /**
     * @throws Exception
     */
    public static function dropAndCreatePivotViewForPostgres(): void
    {
        throw new Exception('not implemented');
    }
}
