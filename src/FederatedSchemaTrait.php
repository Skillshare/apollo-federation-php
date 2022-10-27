<?php

declare(strict_types=1);

namespace Apollo\Federation;

use Apollo\Federation\Types\EntityObjectType;
use Apollo\Federation\Types\SchemaExtensionType;
use Apollo\Federation\Utils\FederatedSchemaPrinter;
use GraphQL\Type\Definition\CustomScalarType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Utils\TypeInfo;
use GraphQL\Utils\Utils;

trait FederatedSchemaTrait
{
    /**
     * @var EntityObjectType[]
     */
    protected array $entityTypes = [];

    /**
     * @var SchemaExtensionType[]
     */
    protected array $schemaExtensionTypes = [];

    /**
     * Returns all the resolved entity types in the schema.
     *
     * @return EntityObjectType[]
     */
    public function getEntityTypes(): array
    {
        return $this->entityTypes;
    }

    /**
     * Indicates whether the schema has entity types resolved.
     */
    public function hasEntityTypes(): bool
    {
        return !empty($this->entityTypes);
    }

    /**
     * @return SchemaExtensionType[]
     */
    public function getSchemaExtensionTypes(): array
    {
        return $this->schemaExtensionTypes;
    }

    /**
     * @param array{ query: ObjectType } $config
     *
     * @return array{ query: ObjectType }
     */
    protected function getQueryTypeConfig(array $config): array
    {
        $queryTypeConfig = $config['query']->config;
        $fields = $queryTypeConfig['fields'];
        $queryTypeConfig['fields'] = function () use ($config, $fields) {
            if (\is_callable($fields)) {
                $fields = $fields();
            }

            return array_merge(
                $fields,
                $this->getQueryTypeServiceFieldConfig(),
                $this->getQueryTypeEntitiesFieldConfig($config)
            );
        };

        return [
            'query' => new ObjectType($queryTypeConfig),
        ];
    }

    /**
     * @return array{ _service: array<string,mixed> }
     */
    protected function getQueryTypeServiceFieldConfig(): array
    {
        $serviceType = new ObjectType([
            'name' => FederatedSchema::RESERVED_TYPE_SERVICE,
            'fields' => [
                FederatedSchema::RESERVED_FIELD_SDL => [
                    'type' => Type::string(),
                    'resolve' => fn (): string => FederatedSchemaPrinter::doPrint($this),
                ],
            ],
        ]);

        return [
            FederatedSchema::RESERVED_FIELD_SERVICE => [
                'type' => Type::nonNull($serviceType),
                'resolve' => static fn (): array => [],
            ],
        ];
    }

    /**
     * @param array<string,mixed>|null $config
     *
     * @return array<string,array{ type: ListOfType, args: array<string,array<string,Type>>, resolve: callable }>
     */
    protected function getQueryTypeEntitiesFieldConfig(?array $config): array
    {
        if (!$this->entityTypes) {
            return [];
        }

        $entityType = new UnionType([
            'name' => FederatedSchema::RESERVED_TYPE_ENTITY,
            'types' => array_values($this->getEntityTypes()),
        ]);

        $anyType = new CustomScalarType([
            'name' => FederatedSchema::RESERVED_TYPE_ANY,
            'serialize' => static fn ($value) => $value,
        ]);

        return [
            FederatedSchema::RESERVED_FIELD_ENTITIES => [
                'type' => Type::listOf($entityType),
                'args' => [
                    FederatedSchema::RESERVED_FIELD_REPRESENTATIONS => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull($anyType))),
                    ],
                ],
                'resolve' => function ($root, $args, $context, $info) use ($config) {
                    if ($config && isset($config['resolve']) && \is_callable($config['resolve'])) {
                        return $config['resolve']($root, $args, $context, $info);
                    }

                    return $this->resolve($root, $args, $context, $info);
                },
            ],
        ];
    }

    protected function resolve($root, $args, $context, $info): array
    {
        return array_map(static function ($ref) use ($context, $info) {
            Utils::invariant(isset($ref[FederatedSchema::RESERVED_FIELD_TYPE_NAME]), 'Type name must be provided in the reference.');

            $typeName = $ref[FederatedSchema::RESERVED_FIELD_TYPE_NAME];
            $type = $info->schema->getType($typeName);

            Utils::invariant(
                $type instanceof EntityObjectType,
                'The _entities resolver tried to load an entity for type "%s", but no object type of that name was found in the schema',
                $type->name
            );

            /** @var EntityObjectType $type */
            if (!$type->hasReferenceResolver()) {
                return $ref;
            }

            return $type->resolveReference($ref, $context, $info);
        }, $args[FederatedSchema::RESERVED_FIELD_REPRESENTATIONS]);
    }

    /**
     * @param array<string,mixed> $config
     *
     * @return array<Type>
     */
    protected function extractExtraTypes(array $config): array
    {
        $typeMap = [];
        $configTypes = $config['types'] ?? [];
        if (\is_array($configTypes)) {
            $typeMap = $configTypes;
        } elseif (\is_callable($configTypes)) {
            $typeMap = $configTypes();
        }

        return $typeMap;
    }

    /**
     * @param array<string,mixed> $config
     *
     * @return EntityObjectType[]
     */
    protected function extractEntityTypes(array $config): array
    {
        $resolvedTypes = TypeInfo::extractTypes($config['query']);
        $entityTypes = [];

        foreach ($resolvedTypes as $type) {
            if ($type instanceof EntityObjectType) {
                $entityTypes[$type->name] = $type;
            }
        }

        return $entityTypes;
    }

    /**
     * @param array<string,mixed> $config
     *
     * @return SchemaExtensionType[]
     */
    protected function extractSchemaExtensionTypes(array $config): array
    {
        $types = [];
        foreach ($this->extractExtraTypes($config) as $type) {
            if ($type instanceof SchemaExtensionType) {
                $types[$type->name] = $type;
            }
        }

        return $types;
    }
}
