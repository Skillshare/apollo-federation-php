<?php

declare(strict_types=1);

namespace Apollo\Federation;

use GraphQL\Type\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\TypeInfo;

use Apollo\Federation\Types\EntityObjectType;
use Apollo\Federation\Utils\FederatedSchemaPrinter;

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
    protected $entityTypes;

    /** @var Directive[] */
    protected $entityDirectives;

    public function __construct($config)
    {
        $this->entityTypes = $this->extractEntityTypes($config);
        $this->entityDirectives = Directives::getDirectives();

        $config = array_merge($config, $this->getEntityDirectivesConfig(), $this->getQueryTypeConfig($config));

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
    private function getEntityDirectivesConfig(): array
    {
        $directives = isset($config['directives']) ? $config['directives'] : [];
        $config['directives'] = array_merge($directives, $this->entityDirectives);

        return $config;
    }

    /** @var array */
    private function getQueryTypeConfig(array $config): array
    {
        $queryTypeFields = array_merge($config['query']->getFields(), $this->getQueryTypeServiceFieldConfig());

        return [
            'query' => new ObjectType(
                array_merge([
                    'name' => 'Query',
                    'fields' => $queryTypeFields
                ])
            )
        ];
    }

    /** @var array */
    private function getQueryTypeServiceFieldConfig(): array
    {
        $serviceType = new ObjectType([
            'name' => '_Service',
            'fields' => [
                'sdl' => [
                    'type' => Type::string(),
                    'resolve' => function () {
                        return FederatedSchemaPrinter::doPrint($this);
                    }
                ]
            ]
        ]);

        return [
            '_service' => [
                'type' => Type::nonNull($serviceType),
                'resolve' => function () {
                    return [];
                }
            ]
        ];
    }

    /**
     * @param array $config
     *
     * @return EntityObjectType[]
     */
    private function extractEntityTypes(array $config): array
    {
        $resolvedTypes = TypeInfo::extractTypes($config['query']);
        $entityTypes = [];

        foreach ($resolvedTypes as $type) {
            if ($type instanceof EntityObjectType) {
                array_push($entityTypes, $type);
            }
        }

        return $entityTypes;
    }
}
