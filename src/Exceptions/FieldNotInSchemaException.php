<?php

namespace AuroraWebSoftware\FlexyField\Exceptions;

class FieldNotInSchemaException extends \Exception 
{
    public static function forField(string $field, string $schemaCode, array $availableFields): self
    {
        if (empty($availableFields)) {
            $available = 'none';
        } else {
            $available = implode(', ', $availableFields);
        }
        return new self("Field '{$field}' is not defined in schema '{$schemaCode}'. Available fields: {$available}");
    }
}
