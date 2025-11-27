<?php

namespace AuroraWebSoftware\FlexyField\Exceptions;

use Exception;

class FieldNotInSetException extends Exception
{
    /**
     * @param  array<string>  $availableFields
     */
    public static function forField(string $fieldName, string $setCode, array $availableFields): self
    {
        $availableFieldsList = empty($availableFields)
            ? 'none'
            : implode(', ', $availableFields);

        return new self(
            "Field '{$fieldName}' is not defined in field set '{$setCode}'. ".
            "Available fields: {$availableFieldsList}"
        );
    }
}
