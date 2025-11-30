<?php

use AuroraWebSoftware\FlexyField\FlexyField;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create schemas table (renamed from field_sets)
        DB::statement('DROP VIEW IF EXISTS ff_values_pivot_view');
        Schema::dropIfExists('ff_field_values');
        Schema::dropIfExists('ff_schema_fields');
        Schema::dropIfExists('ff_schemas');
        Schema::create('ff_schemas', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->string('schema_code'); // renamed from set_code
            $table->string('label');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['model_type', 'schema_code']);
            $table->index(['model_type']);
            $table->index(['schema_code']);
            $table->index(['is_default']);
        });

        // Create schema fields table (renamed from set_fields)
        Schema::create('ff_schema_fields', function (Blueprint $table) {
            $table->id();
            $table->string('schema_code'); // renamed from set_code
            $table->unsignedBigInteger('schema_id')->nullable(); // NEW: Foreign key column
            $table->string('name'); // renamed from field_name
            $table->string('type'); // renamed from field_type
            $table->integer('sort')->default(100);
            $table->text('validation_rules')->nullable();
            $table->json('validation_messages')->nullable();
            $table->json('metadata')->nullable(); // renamed from field_metadata
            $table->timestamps();

            $table->unique(['schema_code', 'name']);
            $table->index(['schema_code']);
            $table->index(['name']);
        });

        // Create field values table (renamed from values)
        Schema::create('ff_field_values', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->integer('model_id');
            $table->string('name'); // renamed from field_name
            $table->string('schema_code')->nullable(); // renamed from field_set_code
            $table->unsignedBigInteger('schema_id')->nullable(); // NEW: Foreign key column
            $table->date('value_date')->nullable();
            $table->dateTime('value_datetime')->nullable();
            $table->decimal('value_decimal')->nullable();
            $table->bigInteger('value_int')->nullable();
            $table->string('value_string')->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->json('value_json')->nullable();
            $table->timestamps();

            $table->unique(['model_type', 'model_id', 'name']);
            $table->index(['model_type']);
            $table->index(['model_id']);
            $table->index(['name']);
            $table->index(['schema_code']);
        });

        Schema::dropIfExists('ff_view_schema');
        Schema::create('ff_view_schema', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // renamed from field_name
            $table->timestamp('added_at')->useCurrent();
        });

        // Add foreign key constraints after creating all tables
        Schema::table('ff_schema_fields', function (Blueprint $table) {
            $table->foreign('schema_id')
                ->references('id')
                ->on('ff_schemas')
                ->onDelete('cascade');
        });

        Schema::table('ff_field_values', function (Blueprint $table) {
            $table->foreign('schema_id')
                ->references('id')
                ->on('ff_schemas')
                ->onDelete('set null');
        });

        // Populate schema_id columns after creating tables
        $this->populateSchemaIds();

        FlexyField::dropAndCreatePivotView();
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS ff_values_pivot_view');
        Schema::dropIfExists('ff_view_schema');
        Schema::dropIfExists('ff_field_values');
        Schema::dropIfExists('ff_schema_fields');
        Schema::dropIfExists('ff_schemas');
    }

    /**
     * Populate schema_id columns based on schema_code
     */
    private function populateSchemaIds(): void
    {
        // For SQLite, we need to use a different approach
        if (DB::getDriverName() === 'sqlite') {
            // Populate schema_id in ff_schema_fields
            $schemaFields = DB::table('ff_schema_fields')->get();
            foreach ($schemaFields as $field) {
                /** @var object{id: int, schema_code: string} $field */
                $schema = DB::table('ff_schemas')->where('schema_code', $field->schema_code)->first();
                if ($schema) {
                    /** @var object{id: int} $schema */
                    DB::table('ff_schema_fields')
                        ->where('id', $field->id)
                        ->update(['schema_id' => $schema->id]);
                }
            }

            // Populate schema_id in ff_field_values
            $fieldValues = DB::table('ff_field_values')->get();
            foreach ($fieldValues as $value) {
                /** @var object{id: int, schema_code: string|null} $value */
                if ($value->schema_code) {
                    $schema = DB::table('ff_schemas')->where('schema_code', $value->schema_code)->first();
                    if ($schema) {
                        /** @var object{id: int} $schema */
                        DB::table('ff_field_values')
                            ->where('id', $value->id)
                            ->update(['schema_id' => $schema->id]);
                    }
                }
            }
        } else {
            // For MySQL/PostgreSQL, use database-specific syntax
            $driver = DB::getDriverName();

            if ($driver === 'pgsql') {
                // PostgreSQL uses UPDATE ... FROM syntax
                DB::statement('
                    UPDATE ff_schema_fields AS sf
                    SET schema_id = s.id
                    FROM ff_schemas AS s
                    WHERE sf.schema_code = s.schema_code
                ');

                DB::statement('
                    UPDATE ff_field_values AS fv
                    SET schema_id = s.id
                    FROM ff_schemas AS s
                    WHERE fv.schema_code = s.schema_code
                ');
            } else {
                // MySQL uses UPDATE ... JOIN syntax
                DB::table('ff_schema_fields as sf')
                    ->join('ff_schemas as s', 'sf.schema_code', '=', 's.schema_code')
                    ->update(['sf.schema_id' => DB::raw('s.id')]);

                DB::table('ff_field_values as fv')
                    ->join('ff_schemas as s', 'fv.schema_code', '=', 's.schema_code')
                    ->update(['fv.schema_id' => DB::raw('s.id')]);
            }
        }
    }
};
