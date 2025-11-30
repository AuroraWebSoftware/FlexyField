<?php

namespace AuroraWebSoftware\FlexyField\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $model_type
 * @property string $schema_code
 * @property string $label
 * @property string|null $description
 * @property array<string, mixed>|null $metadata
 * @property bool $is_default
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class FieldSchema extends Model
{
    protected $table = 'ff_schemas';

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'is_default' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Ensure only one default schema per model_type
        static::creating(function (FieldSchema $fieldSchema) {
            if ($fieldSchema->is_default) {
                self::ensureOnlyOneDefault($fieldSchema->model_type, null);
            }
        });

        static::updating(function (FieldSchema $fieldSchema) {
            if ($fieldSchema->is_default && $fieldSchema->isDirty('is_default')) {
                self::ensureOnlyOneDefault($fieldSchema->model_type, $fieldSchema->id);
            }
        });

        // Cascade delete schema fields when schema is deleted
        static::deleting(function (FieldSchema $fieldSchema) {
            $fieldSchema->fields()->delete();

            // Set schema_code to null in ff_field_values table (cascade null)
            // This is now handled by database foreign key constraint, but keeping for backward compatibility
            DB::table('ff_field_values')
                ->where('schema_code', $fieldSchema->schema_code)
                ->update(['schema_code' => null]);

            // Set schema_code to null in the model's table
            // Get the model class from model_type and update its table
            if (class_exists($fieldSchema->model_type)) {
                try {
                    /** @var \Illuminate\Database\Eloquent\Model $modelInstance */
                    $modelInstance = new $fieldSchema->model_type;
                    $tableName = $modelInstance->getTable();

                    // Only update if table exists
                    if (\Illuminate\Support\Facades\Schema::hasTable($tableName)) {
                        DB::table($tableName)
                            ->where('schema_code', $fieldSchema->schema_code)
                            ->update(['schema_code' => null]);
                    }
                } catch (\Exception $e) {
                    // Ignore errors (e.g., table doesn't exist, model can't be instantiated)
                    // This is expected in some test scenarios
                }
            }
        });
    }

    /**
     * Get the fields that belong to this schema
     *
     * @return HasMany<SchemaField, FieldSchema>
     *
     * @phpstan-return HasMany<SchemaField, $this>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(SchemaField::class, 'schema_id', 'id')
            ->orderBy('sort');
    }

    /**
     * Scope to filter by model type
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeForModel($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    /**
     * Scope to get default schema for model type
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeDefault($query, string $modelType)
    {
        return $query->where('model_type', $modelType)
            ->where('is_default', true);
    }

    /**
     * Ensure only one default schema per model type
     */
    protected static function ensureOnlyOneDefault(string $modelType, ?int $excludeId): void
    {
        DB::table('ff_schemas')
            ->where('model_type', $modelType)
            ->where('is_default', true)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->update(['is_default' => false]);
    }

    /**
     * Get count of models using this schema
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    public function getUsageCount(string $modelClass): int
    {
        /** @var class-string<\Illuminate\Database\Eloquent\Model> $modelClass */
        /** @var \Illuminate\Database\Eloquent\Model $modelInstance */
        $modelInstance = new $modelClass;

        return DB::table($modelInstance->getTable())
            ->where('schema_code', $this->schema_code)
            ->count();
    }

    /**
     * Check if this schema is in use by any models
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    public function isInUse(string $modelClass): bool
    {
        return $this->getUsageCount($modelClass) > 0;
    }
}
