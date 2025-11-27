<?php

namespace AuroraWebSoftware\FlexyField\Tests\Concerns;

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Models\FieldSet;

trait CreatesFieldSets
{
    /**
     * Create a field set with fields for testing
     */
    protected function createFieldSetWithFields(
        string $modelClass,
        string $setCode = 'default',
        array $fields = [],
        bool $isDefault = true
    ): FieldSet {
        // Create field set
        $fieldSet = $modelClass::createFieldSet(
            setCode: $setCode,
            label: ucfirst($setCode),
            description: "Test field set {$setCode}",
            isDefault: $isDefault
        );

        // Add fields
        foreach ($fields as $fieldName => $config) {
            $modelClass::addFieldToSet(
                setCode: $setCode,
                fieldName: $fieldName,
                fieldType: $config['type'] ?? FlexyFieldType::STRING,
                sort: $config['sort'] ?? 100,
                validationRules: $config['rules'] ?? null,
                validationMessages: $config['messages'] ?? null,
                fieldMetadata: $config['metadata'] ?? null
            );
        }

        return $fieldSet;
    }

    /**
     * Create a simple default field set for quick testing
     */
    protected function createDefaultFieldSet(string $modelClass): FieldSet
    {
        return $this->createFieldSetWithFields(
            modelClass: $modelClass,
            setCode: 'default',
            fields: [
                'test_field' => ['type' => FlexyFieldType::STRING],
                'count' => ['type' => FlexyFieldType::INTEGER],
                'price' => ['type' => FlexyFieldType::DECIMAL],
                'is_active' => ['type' => FlexyFieldType::BOOLEAN],
            ],
            isDefault: true
        );
    }
}
