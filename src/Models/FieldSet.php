<?php

namespace AuroraWebSoftware\FlexyField\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $model_type
 * @property string $set_code
 * @property string $label
 * @property string|null $description
 * @property array<string, mixed>|null $metadata
 * @property bool $is_default
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class FieldSet extends Model
{
    protected $table = 'ff_field_sets';

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'is_default' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Ensure only one default field set per model_type
        static::creating(function (FieldSet $fieldSet) {
            if ($fieldSet->is_default) {
                self::ensureOnlyOneDefault($fieldSet->model_type, null);
            }
        });

        static::updating(function (FieldSet $fieldSet) {
            if ($fieldSet->is_default && $fieldSet->isDirty('is_default')) {
                self::ensureOnlyOneDefault($fieldSet->model_type, $fieldSet->id);
            }
        });

        // Cascade delete set fields when field set is deleted
        // (replaces database foreign key constraint that was removed due to set_code not being unique)
        static::deleting(function (FieldSet $fieldSet) {
            $fieldSet->fields()->delete();
        });
    }

    /**
     * Get the fields that belong to this field set
     *
     * @return HasMany<SetField, FieldSet>
     *
     * @phpstan-return HasMany<SetField, $this>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(SetField::class, 'set_code', 'set_code')
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
     * Scope to get default field set for model type
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
     * Ensure only one default field set per model type
     */
    protected static function ensureOnlyOneDefault(string $modelType, ?int $excludeId): void
    {
        DB::table('ff_field_sets')
            ->where('model_type', $modelType)
            ->where('is_default', true)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->update(['is_default' => false]);
    }

    /**
     * Get count of models using this field set
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    public function getUsageCount(string $modelClass): int
    {
        /** @var class-string<\Illuminate\Database\Eloquent\Model> $modelClass */
        /** @var \Illuminate\Database\Eloquent\Model $modelInstance */
        $modelInstance = new $modelClass;

        return DB::table($modelInstance->getTable())
            ->where('field_set_code', $this->set_code)
            ->count();
    }

    /**
     * Check if this field set is in use by any models
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    public function isInUse(string $modelClass): bool
    {
        return $this->getUsageCount($modelClass) > 0;
    }
}
