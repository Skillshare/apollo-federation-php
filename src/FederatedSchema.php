<?php

declare(strict_types=1);

namespace Apollo\Federation;

use Apollo\Federation\Enum\TypeEnum;
use Apollo\Federation\Types\EntityObjectType;
use Apollo\Federation\Utils\FederatedSchemaPrinter;
use GraphQL\Type\Definition\CustomScalarType;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Schema;
use GraphQL\Utils\TypeInfo;
use GraphQL\Utils\Utils;

/**
 * A federated GraphQL schema definition {@see https://www.apollographql.com/docs/apollo-server/federation/introduction }.
 *
 * A federated schema defines a self-contained GraphQL service that can be merged with
 * other services by the [Apollo Gateway](https://www.apollographql.com/docs/intro/platform/#gateway)
 * to produce a single schema clients can consume without being aware of the underlying
 * service structure. It and supports defining entity types which can be referenced by
 * other services and resolved by the gateway and annotate types and fields with specialized
 * directives to hint the gateway on how entity types and references should be resolved.
 *
 * Usage example:
 * <code>
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
 * </code>
 */
class FederatedSchema extends Schema
{
    /** @var EntityObjectType[] */
    protected $entityTypes;

    /** @var Directive[] */
    protected $entityDirectives;

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(array $config)
    {
        $this->entityTypes = $this->extractEntityTypes($config);
        $this->entityDirectives = Directives::getDirectives();

        $config = array_merge($config, $this->getEntityDirectivesConfig($config), $this->getQueryTypeConfig($config));

        parent::__construct($config);
    }

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
        return !empty($this->getEntityTypes());
    }

    /**
     * @param array<string,mixed> $config
     *
     * @return array<string,mixed>
     */
    private function getEntityDirectivesConfig(array $config): array
    {
        $directives = isset($config['directives']) ? $config['directives'] : [];
        $config['directives'] = array_merge($directives, $this->entityDirectives);

        return $config;
    }

    /**
     * @param array<string,mixed> $config
     *
     * @return array{ query: ObjectType }
     */
    private function getQueryTypeConfig(array $config): array
    {
        $queryTypeConfig = $config['query']->config;
        if (\is_callable($queryTypeConfig['fields'])) {
            $queryTypeConfig['fields'] = $queryTypeConfig['fields']();
        }

        $queryTypeConfig['fields'] = array_merge(
            $queryTypeConfig['fields'],
            $this->getQueryTypeServiceFieldConfig(),
            $this->getQueryTypeEntitiesFieldConfig($config)
        );

        return [
            'query' => new ObjectType($queryTypeConfig),
        ];
    }

    /**
     * @return array{ _service: array<string,mixed> }
     */
    private function getQueryTypeServiceFieldConfig(): array
    {
        $serviceType = new ObjectType([
            'name' => TypeEnum::SERVICE,
            'fields' => [
                'sdl' => [
                    'type' => Type::string(),
                    'resolve' => function () {
                        return FederatedSchemaPrinter::doPrint($this);
                    },
                ],
            ],
        ]);

        return [
            '_service' => [
                'type' => Type::nonNull($serviceType),
                'resolve' => function () {
                    return [];
                },
            ],
        ];
    }

    /**
     * @param array<string,mixed>|null $config
     *
     * @return array<string,array{ type: ListOfType, args: array<string,array<string,Type>>, resolve: callable }>
     */
    private function getQueryTypeEntitiesFieldConfig(?array $config): array
    {
        if (!$this->hasEntityTypes()) {
            return [];
        }

        $entityType = new UnionType([
            'name' => TypeEnum::ENTITY,
            'types' => array_values($this->getEntityTypes()),
        ]);

        $anyType = new CustomScalarType([
            'name' => TypeEnum::ANY,
            'serialize' => function ($value) {
                return $value;
            },
        ]);

        return [
            '_entities' => [
                'type' => Type::listOf($entityType),
                'args' => [
                    'representations' => [
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

    private function resolve($root, $args, $context, $info): array
    {
        return array_map(static function ($ref) use ($context, $info) {
            Utils::invariant(isset($ref['__typename']), 'Type name must be provided in the reference.');

            $typeName = $ref['__typename'];
            $type = $info->schema->getType($typeName);

            Utils::invariant(
                $type && $type instanceof EntityObjectType,
                sprintf(
                    'The _entities resolver tried to load an entity for type "%s", but no object type of that name was found in the schema',
                    $type->name
                )
            );

            if (!$type->hasReferenceResolver()) {
                return $ref;
            }

            return $type->resolveReference($ref, $context, $info);
        }, $args['representations']);
    }

    /**
     * @param array<string,mixed> $config
     *
     * @return EntityObjectType[]
     */
    private function extractEntityTypes(array $config): array
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
}
