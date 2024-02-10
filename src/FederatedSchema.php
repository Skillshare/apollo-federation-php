<?php

declare(strict_types=1);

namespace Apollo\Federation;

use Apollo\Federation\Types\AnyType;
use GraphQL\Type\Schema;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\TypeInfo;
use GraphQL\Utils\Utils;

use Apollo\Federation\Types\EntityObjectType;
use Apollo\Federation\Types\EntityUnionType;
use Apollo\Federation\Types\ServiceDefinitionType;

/**
 * A federated GraphQL schema definition (see [related docs](https://www.apollographql.com/docs/apollo-server/federation/introduction))
 *
 * A federated schema defines a self-contained GraphQL service that can be merged with
 * other services by the [Apollo Gateway](https://www.apollographql.com/docs/intro/platform/#gateway)
 * to produce a single schema clients can consume without being aware of the underlying
 * service structure. It and supports defining entity types which can be referenced by
 * other services and resolved by the gateway and annotate types and fields with specialized
 * directives to hint the gateway on how entity types and references should be resolved.
 *
 * Usage example:
 *
 *     $userType = new Apollo\Federation\Types\EntityObjectType([
 *       'name' => 'User',
 *       'fields' => [
 *         'id' => [...],
 *         'email' => [...],
 *         'firstName' => [...],
 *         'lastName' => [...],
 *       ],
 *       'keyFields' => ['id', 'email']
 *     ]);
 *
 *     $queryType = new GraphQL\Type\Definition\ObjectType([
 *       'name' => 'Query',
 *       'fields' => [
 *         'viewer' => [
 *           'type' => $userType,
 *           'resolve' => function () { ... }
 *         ]
 *       ]
 *     ]);
 *
 *     $schema = new Apollo\Federation\FederatedSchema([
 *       'query' => $queryType
 *     ]);
 */
class FederatedSchema extends Schema
{
    /** @var EntityObjectType[]|callable: EntityObjectType[] */
    protected $entityTypes;

    /** @var Directive[] */
    protected array $entityDirectives;

    protected ServiceDefinitionType $serviceDefinitionType;
    protected EntityUnionType $entityUnionType;
    protected AnyType $anyType;

    /**
     *
     * We will provide the parts that we need to operate against.
     *
     * @param array{entityTypes: array<EntityObjectType>|null, typeLoader: callable|null, query: array} $config
     */
    public function __construct($config)
    {
        $this->entityTypes = $config['entityTypes'] ?? $this->lazyEntityTypeExtractor($config);
        $this->entityDirectives = array_merge(Directives::getDirectives(), Directive::getInternalDirectives());

        $this->serviceDefinitionType = new ServiceDefinitionType($this);
        $this->entityUnionType = new EntityUnionType($this->entityTypes);
        $this->anyType = new AnyType();

        $config = array_merge($config,
            $this->getEntityDirectivesConfig($config),
            $this->getQueryTypeConfig($config),
            $this->supplementTypeLoader($config)
        );

        parent::__construct($config);
    }

    /**
     * Returns all the resolved entity types in the schema
     *
     * @return EntityObjectType[]
     */
    public function getEntityTypes(): array
    {
        return is_callable($this->entityTypes)
            ? ($this->entityTypes)()
            : $this->entityTypes;
    }

    /**
     * Indicates whether the schema has entity types resolved
     *
     * @return bool
     */
    public function hasEntityTypes(): bool
    {
        return !empty($this->getEntityTypes());
    }

    /**
     * @return Directive[]
     */
    private function getEntityDirectivesConfig(array $config): array
    {
        $directives = isset($config['directives']) ? $config['directives'] : [];
        $config['directives'] = array_merge($directives, $this->entityDirectives);

        return $config;
    }

    /** @var array */
    private function getQueryTypeConfig(array $config): array
    {
        $queryTypeConfig = $config['query']->config;
        if (is_callable($queryTypeConfig['fields'])) {
            $queryTypeConfig['fields'] = $queryTypeConfig['fields']();
        }

        $queryTypeConfig['fields'] = array_merge(
            $queryTypeConfig['fields'],
            $this->getQueryTypeServiceFieldConfig(),
            $this->getQueryTypeEntitiesFieldConfig($config)
        );

        return [
            'query' => new ObjectType($queryTypeConfig)
        ];
    }

    /**
     * Add type loading functionality for the types required for the federated schema to function.
     */
    private function supplementTypeLoader(array $config): array
    {
        if (!array_key_exists('typeLoader', $config) || !is_callable($config['typeLoader'])) {
            return [];
        }

        return [
            'typeLoader' => function ($typeName) use ($config) {
                $map = $this->builtInTypeMap();
                if (array_key_exists($typeName, $map)) {
                    return $map[$typeName];
                }

                return $config['typeLoader']($typeName);
            }
        ];
    }

    private function builtInTypeMap(): array
    {
        return [
            EntityUnionType::getTypeName() => $this->entityUnionType,
            ServiceDefinitionType::getTypeName() => $this->serviceDefinitionType,
            AnyType::getTypeName() => $this->anyType
        ];
    }

    /** @var array */
    private function getQueryTypeServiceFieldConfig(): array
    {
        return [
            '_service' => [
                'type' => Type::nonNull($this->serviceDefinitionType),
                'resolve' => function () {
                    return [];
                }
            ]
        ];
    }

    /** @var array */
    private function getQueryTypeEntitiesFieldConfig(?array $config): array
    {
        return [
            '_entities' => [
                'type' => Type::listOf($this->entityUnionType),
                'args' => [
                    'representations' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull($this->anyType)))
                    ]
                ],
                'resolve' => function ($root, $args, $context, $info) use ($config) {
                    if (isset($config) && isset($config['resolve']) && is_callable($config['resolve'])) {
                        return $config['resolve']($root, $args, $context, $info);
                    } else {
                        return $this->resolve($root, $args, $context, $info);
                    }
                }
            ]
        ];
    }

    private function resolve($root, $args, $context, $info)
    {
        return array_map(function ($ref) use ($context, $info) {
            assert(isset($ref['__typename']), 'Type name must be provided in the reference.');

            $typeName = $ref['__typename'];
            $type = $info->schema->getType($typeName);

            assert(
                $type instanceof EntityObjectType,
                sprintf(
                    'The _entities resolver tried to load an entity for type "%s", but no object type of that name was found in the schema',
                    $type->name
                )
            );

            if (!$type->hasReferenceResolver()) {
                return $ref;
            }

            $r = $type->resolveReference($ref, $context, $info);
            return $r;
        }, $args['representations']);
    }

    /**
     * @param array $config
     *
     * @return callable: EntityObjectType[]
     */
    private function lazyEntityTypeExtractor(array $config): callable
    {
        return function () use ($config) {
            $resolvedTypes = [];
            TypeInfo::extractTypes($config['query'], $resolvedTypes);
            $entityTypes = [];

            foreach ($resolvedTypes as $type) {
                if ($type instanceof EntityObjectType) {
                    $entityTypes[$type->name] = $type;
                }
            }

            return $entityTypes;
        };
    }
}
