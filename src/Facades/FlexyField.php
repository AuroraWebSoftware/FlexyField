<?php

namespace AuroraWebSoftware\FlexyField\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \AuroraWebSoftware\FlexyField\FlexyField
 */
class FlexyField extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \AuroraWebSoftware\FlexyField\FlexyField::class;
    }
}
