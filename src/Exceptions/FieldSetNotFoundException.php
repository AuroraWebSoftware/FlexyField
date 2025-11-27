<?php

namespace AuroraWebSoftware\FlexyField\Exceptions;

use Exception;

class FieldSetNotFoundException extends Exception
{
    public static function forSetCode(string $setCode, string $modelType): self
    {
        return new self("Field set '{$setCode}' not found for model type '{$modelType}'.");
    }

    public static function notAssigned(string $modelClass, int $modelId): self
    {
        return new self("No field set assigned to {$modelClass}#{$modelId}. Please assign a field set first.");
    }
}
