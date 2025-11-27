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
        // Create field sets table (replaces ff_shapes concept)
        Schema::dropIfExists('ff_field_sets');
        Schema::create('ff_field_sets', function (Blueprint $table) {
            $table->id();
            $table->string('model_type')->index();
            $table->string('set_code')->index();
            $table->string('label');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_default')->default(false)->index();
            $table->timestamps();

            $table->unique(['model_type', 'set_code']);
        });

        // Create set fields table (replaces ff_shapes)
        Schema::dropIfExists('ff_set_fields');
        Schema::create('ff_set_fields', function (Blueprint $table) {
            $table->id();
            $table->string('set_code')->index();
            $table->string('field_name')->index();
            $table->string('field_type');
            $table->integer('sort')->default(100);
            $table->text('validation_rules')->nullable();
            $table->json('validation_messages')->nullable();
            $table->json('field_metadata')->nullable();
            $table->timestamps();

            $table->unique(['set_code', 'field_name']);

            // Note: Foreign key constraint removed because set_code is not unique by itself
            // (only ['model_type', 'set_code'] is unique). Cascading is handled in
            // FieldSet model's deleting event.
        });

        // Create legacy shapes table (for migration compatibility)
        Schema::dropIfExists('ff_shapes');
        Schema::create('ff_shapes', function (Blueprint $table) {
            $table->id();
            $table->string('model_type')->index();
            $table->string('field_name')->index();
            $table->string('field_type')->index();
            $table->integer('sort')->default(100);
            $table->string('validation_rules')->nullable();
            $table->json('validation_messages')->nullable();
            $table->json('field_metadata')->nullable();
            $table->timestamps();

            $table->unique(['model_type', 'field_name']);
        });

        Schema::dropIfExists('ff_values');
        Schema::create('ff_values', function (Blueprint $table) {
            $table->id();
            $table->string('model_type')->index();
            $table->integer('model_id')->index();
            $table->string('field_name')->index();
            $table->string('field_set_code')->nullable()->index();
            $table->date('value_date')->nullable();
            $table->dateTime('value_datetime')->nullable();
            $table->decimal('value_decimal')->nullable();
            $table->bigInteger('value_int')->nullable();
            $table->string('value_string')->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->json('value_json')->nullable();
            $table->timestamps();

            $table->unique(['model_type', 'model_id', 'field_name']);

            // Note: Foreign key constraint not added because set_code is not unique by itself
            // (only ['model_type', 'set_code'] is unique in ff_field_sets table).
            // Cascading deletes/updates are handled in FieldSet model's deleting event.
            // This ensures PostgreSQL compatibility while maintaining referential integrity
            // through application-level constraints.
        });

        Schema::dropIfExists('ff_view_schema');
        Schema::create('ff_view_schema', function (Blueprint $table) {
            $table->id();
            $table->string('field_name')->unique();
            $table->timestamp('added_at')->useCurrent();
        });

        FlexyField::dropAndCreatePivotView();
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS flexyfield.ff_values_pivot_view');
        Schema::dropIfExists('ff_view_schema');
        Schema::dropIfExists('ff_values');
        Schema::dropIfExists('ff_set_fields');
        Schema::dropIfExists('ff_field_sets');
        Schema::dropIfExists('ff_shapes');
    }
};
