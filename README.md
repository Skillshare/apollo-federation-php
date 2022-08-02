# Apollo Federation PHP

This package provides classes and utilities for [`webonyx/graphql-php`](https://github.com/webonyx/graphql-php) for creating [federated GraphQL subgraphs](https://www.apollographql.com/docs/federation/#subgraph-schemas) in PHP to be consumed by the Apollo Gateway.

> ⚠️ **IMPORTANT:** This package is still in active development and it might introduce breaking changes.

## Usage

Via composer:

```
composer require skillshare/apollo-federation-php
```

### Entities

An entity is an object type that you define canonically in one subgraph and can then reference and extend in other subgraphs. It can be defined via the `EntityObjectType` which takes the same configuration as the default `ObjectType` plus a `keyFields` and `__resolveReference` properties. 

```php
use Apollo\Federation\Types\EntityObjectType;
use GraphQL\Type\Definition\Type;

$userType = new EntityObjectType([
    'name' => 'User',
    'keys' => [['fields' => 'id'], ['fields' => 'email']],
    'fields' => [
        'id' => ['type' => Type::int()],
        'email' => ['type' => Type::string()],
        'firstName' => ['type' => Type::string()],
        'lastName' => ['type' => Type::string()]
    ],
    '__resolveReference' => static function ($ref) {
        // .. fetch from a data source.
    }
]);
```

* `keys` — defines the entity's primary key, which consists of one or more of the fields. An entity's key cannot include fields that return a union or interface.

* `__resolveReference` — resolves the representation of the entity from the provided reference. Subgraphs use representations to reference entities from other subgraphs. A representation requires only an explicit __typename definition and values for the entity's primary key fields.

For more detail on entities, [see the official docs.](https://www.apollographql.com/docs/federation/entities)

### Entity references

A subgraph can reference entities from another subgraph by defining a stub including just enough information to know how to interact with the referenced entity. Entity references are created via the `EntityRefObjectType` which takes the same configuration as the base `EntityObjectType`.

```php
use Apollo\Federation\Types\EntityRefObjectType;

$userType = new EntityRefObjectType([
    'name' => 'User',
    'keys' => [['fields' => 'id', 'resolvable' => false]],
    'fields' => [
        'id' => ['type' => Type::int()],
        'email' => ['type' => Type::string()]
    ]
]);
```

For more detail on entity references, [see the official docs.](https://www.apollographql.com/docs/federation/entities/#referencing)

### Extending

A subgraph can add fields to an entity that's defined in another subgraph. This is called extending the entity. When a subgraph extends an entity, the entity's originating subgraph is not aware of the added fields. Only the extending subgraph (along with the gateway) knows about these fields.

```php
use Apollo\Federation\Types\EntityRefObjectType;

$userType = new EntityRefObjectType([
    'name' => 'User',
    'keys' => [['fields' => 'id', 'resolvable' => false]],
    'fields' => [
        'id' => [
            'type' => Type::int(),
            'isExternal' => true
        ],
    ]
]);
```

The subgraph can extend using the following configuration properties:

* **[`isExternal`](https://www.apollographql.com/docs/apollo-server/federation/federation-spec/#external) —** marks a field as owned by another service. This allows service A to use fields from service B while also knowing at runtime the types of that field.

* **[`provides`](https://www.apollographql.com/docs/apollo-server/federation/federation-spec/#provides) —** used to annotate the expected returned fieldset from a field on a base type that is guaranteed to be selectable by the gateway.

* **[`requires`](https://www.apollographql.com/docs/apollo-server/federation/federation-spec/#requires) —** used to annotate the required input fieldset from a base type for a resolver. It is used to develop a query plan where the required fields may not be needed by the client, but the service may need additional information from other services.

### Federated schema

The `FederatedSchema` class extends from the base `GraphQL\Schema` class and augments a schema configuration using entity types and federated field annotations with Apollo Federation metadata. [See the docs](https://www.apollographql.com/docs/apollo-server/federation/federation-spec/#federation-schema-specification) for more info.

```php
use GraphQL\GraphQL;
use Apollo\Federation\FederatedSchema;


$schema = new FederatedSchema($config);
$query = 'query GetServiceSDL { _service { sdl } }';

$result = GraphQL::executeQuery($schema, $query);
```

## Disclaimer

Documentation in this project include content quoted directly from the [Apollo official documentation](https://www.apollographql.com/docs) to reduce redundancy.