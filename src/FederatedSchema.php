<?php

declare(strict_types=1);

namespace Apollo\Federation;

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
 * A federated GraphQL schema definition see related docs {@see https://www.apollographql.com/docs/apollo-server/federation/introduction }.
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
 *       'keys' => [['fields' => 'id'], ['fields' => 'email']]
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
    use FederatedSchemaTrait;

    public const RESERVED_TYPE_ANY = '_Any';
    public const RESERVED_TYPE_ENTITY = '_Entity';
    public const RESERVED_TYPE_SERVICE = '_Service';
    public const RESERVED_TYPE_MUTATION = 'Mutation';
    public const RESERVED_TYPE_QUERY = 'Query';

    public const RESERVED_FIELD_ENTITIES = '_entities';
    public const RESERVED_FIELD_REPRESENTATIONS = 'representations';
    public const RESERVED_FIELD_SDL = 'sdl';
    public const RESERVED_FIELD_SERVICE = '_service';
    public const RESERVED_FIELD_TYPE_NAME = '__typename';

    public static function isReservedRootType(string $name): bool
    {
        return \in_array($name, [self::RESERVED_TYPE_QUERY, self::RESERVED_TYPE_MUTATION], true);
    }

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(array $config)
    {
        $this->entityTypes = $this->extractEntityTypes($config);
        $this->entityDirectives = Directives::getDirectives();
        $this->schemaExtensionTypes = $this->extractSchemaExtensionTypes($config);

        $config = array_merge($config, $this->getEntityDirectivesConfig($config), $this->getQueryTypeConfig($config));

        parent::__construct($config);
    }
}
