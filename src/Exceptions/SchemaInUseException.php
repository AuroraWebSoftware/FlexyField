<?php

namespace AuroraWebSoftware\FlexyField\Exceptions;

use Exception;

class SchemaInUseException extends Exception
{
    public static function cannotDelete(string $schemaCode, int $count): self
    {
        return new self(
            "Cannot delete schema '{$schemaCode}' because it is currently in use by {$count} model instance(s). ".
            'Please reassign these instances to a different schema before deleting.'
        );
    }
}
