<?php

namespace AuroraWebSoftware\FlexyField\Tests\Concerns;

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Models\FieldSchema;

trait CreatesSchemas
{
    /**
     * Create a schema with fields for testing
     */
    protected function createSchemaWithFields(
        string $modelClass,
        string $schemaCode = 'default',
        array $fields = [],
        bool $isDefault = true,
        ?array $metadata = null
    ): FieldSchema {
        // Create schema
        $schema = $modelClass::createSchema(
            schemaCode: $schemaCode,
            label: ucfirst($schemaCode),
            description: "Test schema {$schemaCode}",
            isDefault: $isDefault,
            metadata: $metadata ?? null, // Fix: Use null coalescing operator
        );

        // Add fields
        foreach ($fields as $fieldName => $config) {
            $modelClass::addFieldToSchema(
                schemaCode: $schemaCode,
                fieldName: $fieldName,
                fieldType: $config['type'] ?? FlexyFieldType::STRING,
                sort: $config['sort'] ?? 100,
                validationRules: $config['validationRules'] ?? $config['rules'] ?? null,
                validationMessages: $config['validationMessages'] ?? $config['messages'] ?? null,
                fieldMetadata: $config['metadata'] ?? null
            );
        }

        return $schema;
    }

    /**
     * Create a simple default schema for quick testing
     */
    protected function createDefaultSchema(string $modelClass): FieldSchema
    {
        return $this->createSchemaWithFields(
            modelClass: $modelClass,
            schemaCode: 'default',
            fields: [
                'test_field' => ['type' => FlexyFieldType::STRING],
                'count' => ['type' => FlexyFieldType::INTEGER],
                'price' => ['type' => FlexyFieldType::DECIMAL],
                'is_active' => ['type' => FlexyFieldType::BOOLEAN],
                'is_featured' => ['type' => FlexyFieldType::BOOLEAN],
                'empty_field' => ['type' => FlexyFieldType::STRING],
                'json_field' => ['type' => FlexyFieldType::JSON],
                'date_field' => ['type' => FlexyFieldType::DATE],
                'datetime_field' => ['type' => FlexyFieldType::DATETIME],
                'string_field' => ['type' => FlexyFieldType::STRING],
                'int_field' => ['type' => FlexyFieldType::INTEGER],
                'decimal_field' => ['type' => FlexyFieldType::DECIMAL],
                'boolean_field' => ['type' => FlexyFieldType::BOOLEAN],
            ],
            isDefault: true
        );
    }
}
