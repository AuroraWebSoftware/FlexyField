<?php

namespace AuroraWebSoftware\FlexyField\Enums;

enum FlexyFieldType: string
{
    case DATE = 'date';
    case DATETIME = 'datetime';
    case DECIMAL = 'decimal';
    case INTEGER = 'integer';
    case STRING = 'string';
    case BOOLEAN = 'boolean';
    case JSON = 'json';
}
