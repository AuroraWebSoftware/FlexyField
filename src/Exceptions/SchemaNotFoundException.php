<?php

namespace AuroraWebSoftware\FlexyField\Exceptions;

use Exception;

class SchemaNotFoundException extends Exception
{
    public static function forSchemaCode(string $schemaCode, string $modelType): self
    {
        return new self("Schema '{$schemaCode}' not found for model type '{$modelType}'.");
    }

    public static function notAssigned(string $modelClass, int $modelId): self
    {
        return new self("No schema assigned to {$modelClass}#{$modelId}. Please assign a schema first.");
    }
}
