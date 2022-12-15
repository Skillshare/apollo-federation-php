<?php

declare(strict_types=1);

namespace Apollo\Federation;

use Apollo\Federation\Types\EntityObjectType;
use Apollo\Federation\Utils\FederatedSchemaPrinter;
use GraphQL\Type\Definition\CustomScalarType;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Schema;
use GraphQL\Utils\TypeInfo;
use GraphQL\Utils\Utils;

use function array_map;
use function array_merge;
use function array_values;
use function is_callable;
use function sprintf;

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
    /** @var EntityObjectType[] */
    protected array $entityTypes = [];

    /** @var Directive[] */
    protected array $entityDirectives = [];

    /**
     * @param array{query: Type, directives?: Directive[], resolve?: callable} $config
     */
    public function __construct(array $config)
    {
        $this->entityTypes = $this->extractEntityTypes($config);
        $this->entityDirectives = array_merge(Directives::getDirectives(), Directive::getInternalDirectives());

        $config = array_merge($config, $this->getEntityDirectivesConfig($config), $this->getQueryTypeConfig($config));

        parent::__construct($config);
    }

    /**
     * Returns all the resolved entity types in the schema
     *
     * @return EntityObjectType[]
     */
    public function getEntityTypes(): array
    {
        return $this->entityTypes;
    }

    /**
     * Indicates whether the schema has entity types resolved
     */
    public function hasEntityTypes(): bool
    {
        return $this->getEntityTypes() !== [];
    }

    /**
     * @param array{query: Type, directives?: Directive[], resolve?: callable} $config
     *
     * @return array{query: Type, directives: Directive[], resolve?: callable}
     */
    private function getEntityDirectivesConfig(array $config): array
    {
        $directives = $config['directives'] ?? [];
        $config['directives'] = array_merge($directives, $this->entityDirectives);

        return $config;
    }

    /**
     * @param array{query: Type, directives?: Directive[], resolve?: callable} $config
     *
     * @return array{query: ObjectType}
     */
    private function getQueryTypeConfig(array $config): array
    {
        /** @var array{fields: callable():array<string, mixed> | array<string, mixed>} $queryTypeConfig */
        $queryTypeConfig = $config['query']->config;

        if (is_callable($queryTypeConfig['fields'])) {
            $fields = $queryTypeConfig['fields']();
        } else {
            $fields = $queryTypeConfig['fields'];
        }

        $queryTypeConfig['fields'] = array_merge(
            $fields,
            $this->getQueryTypeServiceFieldConfig(),
            $this->getQueryTypeEntitiesFieldConfig($config),
        );

        return [
            'query' => new ObjectType($queryTypeConfig),
        ];
    }

    /**
     * @return array{_service: array{type: Type, resolve: callable}}
     */
    private function getQueryTypeServiceFieldConfig(): array
    {
        $serviceType = new ObjectType([
            'name' => '_Service',
            'fields' => [
                'sdl' => [
                    'type' => Type::string(),
                    'resolve' => fn () => FederatedSchemaPrinter::doPrint($this),
                ],
            ],
        ]);

        return [
            '_service' => [
                'type' => Type::nonNull($serviceType),
                'resolve' => fn () => [],
            ],
        ];
    }

    /**
     * @param array{query: Type, directives?: Directive[], resolve?: mixed} $config
     *
     * @return array{_entities?: array{type: ListOfType, args: array{representations: array{type: Type}}, resolve: callable}}
     */
    private function getQueryTypeEntitiesFieldConfig(array $config): array
    {
        if (!$this->hasEntityTypes()) {
            return [];
        }

        $entityType = new UnionType([
            'name' => '_Entity',
            'types' => array_values($this->getEntityTypes()),
        ]);

        $anyType = new CustomScalarType([
            'name' => '_Any',
            'serialize' =>
                /**
                 * @param scalar $value
                 *
                 * @return scalar
                 */
                fn ($value) => $value,
        ]);

        return [
            '_entities' => [
                'type' => Type::listOf($entityType),
                'args' => [
                    'representations' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull($anyType))),
                    ],
                ],
                'resolve' =>
                    /**
                     * @param mixed $root
                     * @param array{representations: array<array{__typename?: string}>} $args
                     * @param mixed $context
                     *
                     * @return mixed[]
                     */
                    function ($root, array $args, $context, ResolveInfo $info) use ($config): array {
                        if (isset($config['resolve']) && is_callable($config['resolve'])) {
                            /** @var mixed[] */
                            return $config['resolve']($root, $args, $context, $info);
                        } else {
                            /**
                             * PHPStan is weird...
                             *
                             * @var array{representations: array<array{__typename?: string}>} $argsArgument
                             * @psalm-suppress UnnecessaryVarAnnotation
                             */
                            $argsArgument = $args;

                            return $this->resolve($root, $argsArgument, $context, $info);
                        }
                    },
            ],
        ];
    }

    /**
     * @param mixed $root
     * @param array{representations: array<array{__typename?: string}>} $args
     * @param mixed $context
     *
     * @return mixed[]
     *
     * @psalm-suppress UnusedParam $root is unused here, but it might be used by another callable
     */
    private function resolve($root, array $args, $context, ResolveInfo $info): array
    {
        return array_map(
            /**
             * @param array{__typename?: string} $ref
             */
            function (array $ref) use ($context, $info) {
                Utils::invariant(isset($ref['__typename']), 'Type name must be provided in the reference.');

                $typeName = $ref['__typename'] ?? '';

                /** @var EntityObjectType $type */
                $type = $info->schema->getType($typeName);

                Utils::invariant(
                    $type instanceof EntityObjectType,
                    sprintf(
                        'The _entities resolver tried to load an entity for '
                        . 'type "%s", but no object type of that name was found in the schema',
                        $type->name,
                    ),
                );

                if (!$type->hasReferenceResolver()) {
                    return $ref;
                }

                return $type->resolveReference($ref, $context, $info);
            },
            $args['representations'],
        );
    }

    /**
     * @param array{query: Type, directives?: Directive[], resolve?: callable} $config
     *
     * @return EntityObjectType[]
     */
    private function extractEntityTypes(array $config): array
    {
        $resolvedTypes = TypeInfo::extractTypes($config['query']) ?? [];
        $entityTypes = [];

        foreach ($resolvedTypes as $type) {
            if ($type instanceof EntityObjectType) {
                $entityTypes[$type->name] = $type;
            }
        }

        return $entityTypes;
    }
}
