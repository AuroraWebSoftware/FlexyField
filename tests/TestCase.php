<?php

namespace AuroraWebSoftware\FlexyField\Tests;

use AuroraWebSoftware\FlexyField\FlexyField;
use AuroraWebSoftware\FlexyField\FlexyFieldServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected static $migrated = false;

    protected static $pgMigrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'AuroraWebSoftware\\FlexyField\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        // Drop the view before each test to avoid conflicts
        \Illuminate\Support\Facades\DB::statement('DROP VIEW IF EXISTS ff_values_pivot_view');

        // Check if we're using PostgreSQL
        $isPostgreSQL = config('database.default') === 'pgsql';

        // Run migrations only once per database type
        if ($isPostgreSQL && ! static::$pgMigrated) {
            $this->runMigrations();
            static::$pgMigrated = true;
        } elseif (! $isPostgreSQL && ! static::$migrated) {
            $this->runMigrations();
            static::$migrated = true;
        }

        // Clean up data before each test
        $this->cleanupTestData();

        // Recreate the view after cleaning up
        FlexyField::dropAndCreatePivotView();
    }

    protected function tearDown(): void
    {
        \Illuminate\Support\Facades\DB::disconnect();
        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [
            FlexyFieldServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        // Check if we're using PostgreSQL
        $isPostgreSQL = env('DB_CONNECTION') === 'pgsql';

        if ($isPostgreSQL) {
            // Setup PostgreSQL database
            $app['config']->set('database.default', 'pgsql');
            $app['config']->set('database.connections.pgsql', [
                'driver' => 'pgsql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '54321'),
                'database' => env('DB_DATABASE', 'flexyfield'),
                'username' => env('DB_USERNAME', 'flexyfield'),
                'password' => env('DB_PASSWORD', 'flexyfield'),
                'charset' => 'utf8',
                'prefix' => '',
                'search_path' => 'public',
                'sslmode' => 'prefer',
            ]);
        } else {
            // Setup default database to use mysql
            $app['config']->set('database.default', 'mysql');
            $app['config']->set('database.connections.mysql', [
                'driver' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '33063'),
                'database' => env('DB_DATABASE', 'flexyfield'),
                'username' => env('DB_USERNAME', 'flexyfield'),
                'password' => env('DB_PASSWORD', 'flexyfield'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ]);
        }
    }

    /**
     * Clean up test data before each test
     */
    protected function cleanupTestData()
    {
        // Clean up in the right order to avoid foreign key constraints
        // Values first, then fields, then schemas
        if (Schema::hasTable('ff_field_values')) {
            \Illuminate\Support\Facades\DB::table('ff_field_values')->delete();
        }
        if (Schema::hasTable('ff_schema_fields')) {
            \Illuminate\Support\Facades\DB::table('ff_schema_fields')->delete();
        }
        if (Schema::hasTable('ff_schemas')) {
            \Illuminate\Support\Facades\DB::table('ff_schemas')->delete();
        }
    }

    /**
     * Run migrations for the test database
     */
    protected function runMigrations()
    {
        // Include our migrations
        $migration = include __DIR__.'/../database/migrations/create_flexyfield_table.php';

        // Run migration
        $migration->up();

        // Run label column migration
        $labelMigration = include __DIR__.'/../database/migrations/add_label_column_to_schema_fields.php';
        $labelMigration->up();
    }

    /**
     * Get view columns in a database-agnostic way
     *
     * @return array Array of objects with 'Field' or 'column_name' property
     */
    protected function getViewColumns(string $viewName): array
    {
        $driver = \Illuminate\Support\Facades\DB::getDriverName();

        if ($driver === 'pgsql') {
            $columns = \Illuminate\Support\Facades\DB::select('
                SELECT column_name as "Field"
                FROM information_schema.columns
                WHERE table_name = ?
                ORDER BY ordinal_position
            ', [$viewName]);
        } else {
            // MySQL, SQLite, etc.
            $columns = \Illuminate\Support\Facades\DB::select("DESCRIBE {$viewName}");
        }

        return $columns;
    }
}
