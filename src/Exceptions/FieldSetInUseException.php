<?php

namespace AuroraWebSoftware\FlexyField\Exceptions;

use Exception;

class FieldSetInUseException extends Exception
{
    public static function cannotDelete(string $setCode, int $count): self
    {
        return new self(
            "Cannot delete field set '{$setCode}' because it is currently in use by {$count} model instance(s). ".
            'Please reassign these instances to a different field set before deleting.'
        );
    }
}
