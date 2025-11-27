<?php

namespace AuroraWebSoftware\FlexyField\Commands;

use AuroraWebSoftware\FlexyField\FlexyField;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateShapesToFieldSetsCommand extends Command
{
    protected $signature = 'flexyfield:migrate-shapes
                            {--force : Force migration without confirmation}
                            {--drop-shapes : Drop ff_shapes table after successful migration}';

    protected $description = 'Migrate legacy ff_shapes to new field sets architecture';

    public function handle(): int
    {
        $this->info('FlexyField: Migrating shapes to field sets...');
        $this->newLine();

        // Check if ff_shapes exists
        if (! Schema::hasTable('ff_shapes')) {
            $this->warn('⚠ ff_shapes table not found. Migration not needed.');

            return self::SUCCESS;
        }

        // Check if ff_field_sets exists
        if (! Schema::hasTable('ff_field_sets')) {
            $this->error('✗ ff_field_sets table not found. Please run migrations first:');
            $this->line('  php artisan migrate');

            return self::FAILURE;
        }

        $shapesCount = DB::table('ff_shapes')->count();

        if ($shapesCount === 0) {
            $this->info('✓ No shapes to migrate.');

            if ($this->option('drop-shapes')) {
                $this->dropShapesTable();
            }

            return self::SUCCESS;
        }

        $this->info("Found {$shapesCount} shapes to migrate.");
        $this->newLine();

        // Confirm migration
        if (! $this->option('force')) {
            if (! $this->confirm('Do you want to proceed with the migration?', true)) {
                $this->info('Migration cancelled.');

                return self::SUCCESS;
            }
        }

        try {
            DB::transaction(function () {
                $this->migrateShapes();
            });

            $this->newLine();
            $this->info('✓ Migration completed successfully!');
            $this->newLine();

            $this->displaySummary();

            if ($this->option('drop-shapes')) {
                $this->newLine();
                $this->dropShapesTable();
            } else {
                $this->newLine();
                $this->warn('⚠ ff_shapes table is still present. Run with --drop-shapes to remove it.');
                $this->line('  php artisan flexyfield:migrate-shapes --drop-shapes');
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('✗ Migration failed: '.$e->getMessage());
            $this->line('  '.$e->getFile().':'.$e->getLine());

            return self::FAILURE;
        }
    }

    protected function migrateShapes(): void
    {
        // Get all distinct model types from shapes
        $modelTypes = DB::table('ff_shapes')
            ->select('model_type')
            ->distinct()
            ->pluck('model_type');

        $this->info('Step 1: Creating default field sets...');

        $fieldSetsCreated = 0;
        $timestamp = now();

        foreach ($modelTypes as $modelType) {
            // Check if default field set already exists
            $exists = DB::table('ff_field_sets')
                ->where('model_type', $modelType)
                ->where('set_code', 'default')
                ->exists();

            if ($exists) {
                $this->line("  - Skipping {$modelType} (default set already exists)");

                continue;
            }

            // Create default field set for this model type
            DB::table('ff_field_sets')->insert([
                'model_type' => $modelType,
                'set_code' => 'default',
                'label' => 'Default',
                'description' => 'Migrated from legacy shapes',
                'metadata' => json_encode(['migrated_at' => now()->toIso8601String()]),
                'is_default' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

            $fieldSetsCreated++;
            $this->line("  ✓ Created default field set for {$modelType}");
        }

        $this->info("  Created {$fieldSetsCreated} default field sets.");
        $this->newLine();

        // Migrate shapes to set fields
        $this->info('Step 2: Migrating shapes to set fields...');

        $shapes = DB::table('ff_shapes')->get();
        $setFieldsCreated = 0;

        foreach ($shapes as $shape) {
            // Check if field already exists in set
            $exists = DB::table('ff_set_fields')
                ->where('set_code', 'default')
                ->where('field_name', $shape->field_name)
                ->exists();

            if ($exists) {
                $this->line("  - Skipping {$shape->field_name} (already exists in default set)");

                continue;
            }

            DB::table('ff_set_fields')->insert([
                'set_code' => 'default',
                'field_name' => $shape->field_name,
                'field_type' => $shape->field_type,
                'sort' => $shape->sort,
                'validation_rules' => $shape->validation_rules,
                'validation_messages' => $shape->validation_messages,
                'field_metadata' => $shape->field_metadata,
                'created_at' => $shape->created_at,
                'updated_at' => $shape->updated_at,
            ]);

            $setFieldsCreated++;
        }

        $this->info("  Migrated {$setFieldsCreated} fields to default sets.");
        $this->newLine();

        // Update ff_values with field_set_code
        $this->info('Step 3: Updating ff_values with field_set_code...');

        $valuesUpdated = DB::table('ff_values')
            ->whereNull('field_set_code')
            ->update(['field_set_code' => 'default']);

        $this->info("  Updated {$valuesUpdated} value records.");
        $this->newLine();

        // Rebuild pivot view
        $this->info('Step 4: Rebuilding pivot view...');
        FlexyField::forceRecreateView();
        $this->info('  ✓ Pivot view rebuilt.');
    }

    protected function dropShapesTable(): void
    {
        if ($this->option('force') || $this->confirm('Are you sure you want to drop ff_shapes table? This cannot be undone.', false)) {
            Schema::dropIfExists('ff_shapes');
            $this->info('✓ ff_shapes table dropped successfully.');
        } else {
            $this->info('Keeping ff_shapes table.');
        }
    }

    protected function displaySummary(): void
    {
        $fieldSets = DB::table('ff_field_sets')->count();
        $setFields = DB::table('ff_set_fields')->count();
        $valuesWithSet = DB::table('ff_values')->whereNotNull('field_set_code')->count();
        $totalValues = DB::table('ff_values')->count();

        $this->info('Migration Summary:');
        $this->table(
            ['Resource', 'Count'],
            [
                ['Field Sets', $fieldSets],
                ['Set Fields', $setFields],
                ['Values with field set', $valuesWithSet.'/'.$totalValues],
            ]
        );
    }
}
