<?php

use AuroraWebSoftware\FlexyField\FlexyField;
use AuroraWebSoftware\FlexyField\Models\Value;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

        Schema::create('ff_values', function (Blueprint $table) {
            $table->id();
            $table->string('model_type')->index();
            $table->integer('model_id')->index();
            $table->string('field_name')->index();
            $table->date('value_date')->nullable();
            $table->dateTime('value_datetime')->nullable();
            $table->decimal('value_decimal')->nullable();
            $table->bigInteger('value_int')->nullable();
            $table->string('value_string')->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->json('value_json')->nullable();
            $table->timestamps();

            $table->unique(['model_type', 'model_id', 'field_name']);
        });

        Schema::create('ff_view_schema', function (Blueprint $table) {
            $table->id();
            $table->string('field_name')->unique();
            $table->timestamp('added_at')->useCurrent();
        });

        FlexyField::dropAndCreatePivotView();
    }

    public function down(): void
    {
        Schema::dropIfExists('ff_shapes');
        Schema::dropIfExists('ff_values');
        Schema::dropIfExists('ff_view_schema');
        DB::statement('DROP VIEW IF EXISTS flexyfield.ff_values_pivot_view');
    }
};
