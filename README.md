# Apollo Federation for PHP

This package contains classes and utilities for [`webonyx/graphql-php`](https://github.com/webonyx/graphql-php) for creating GraphQL schemas following the Apollo Federation specification, allowing to create GraphQL microservices that can be combined into a single endpoint by tools like the Apollo Gateway.

Please note this is still in active development and it might introduce breaking changes.

## Usage

To install latest package release:
`composer require skillshare/apollo-federation-php:dev-master#99d1c8f8554e4eb3d5c5f70bb714fcb7de2dc5f4`

### Entity types

An entity is a type that can be referenced by another service. Entities create connection points between services and form the basic building blocks of a federated graph. Entities have a primary key whose value uniquely identifies a specific instance of the type, similar to the function of a primary key in a SQL table.

Creating an entity is done via the `EntityObjectType` and it takes the same configuration as the default `ObjectType` in addition of a `keyFields` and `__resolveReference` configuration properties. 

```php
use Apollo\Federation\Types\EntityObjectType;

$userType = new EntityObjectType([
    'name' => 'User',
    'keyFields' => ['id', 'email'],
    'fields' => [
        'id' => ['type' => Type::int()],
        'email' => ['type' => Type::string()],
        'firstName' => ['type' => Type::string()],
        'lastName' => ['type' => Type::string()]
    ],
    '__resolveReference' => function ($ref) {
        // .. fetch data from the db.
    }
]);
```

* `keyFields` — determine which fields serve as the unique identifier of the entity.

* `__resolveReference` — reference resolvers are a special addition to Apollo Server that allow individual types to be resolved by a reference from another service. They are called when a query references an entity across service boundaries. To learn more about `__resolveReference`, see the [API docs](https://www.apollographql.com/docs/apollo-server/api/apollo-federation).

### Entity reference types

An entity reference is a type that refers to an entity on another service and only contain enough data so the Apollo Gateway can resolve it, typically the keys.

Creating an entity reference is done via the `EntityRefObjectType` and takes the same configuration values as the base `EntityObjectType` class.

```php
use Apollo\Federation\Types\EntityRefObjectType;

$userType = new EntityRefObjectType([
    'name' => 'User',
    'keyFields' => ['id', 'email'],
    'fields' => [
        'id' => ['type' => Type::int()],
        'email' => ['type' => Type::string()]
    ]
]);
```

### Type fields

In addition to creating entities and references, fields in object types can be configured with Apollo Federation-specific settings. [Refer to the docs for more info](https://www.apollographql.com/docs/apollo-server/federation/federation-spec).

```php
use Apollo\Federation\Types\EntityRefObjectType;

$userType = new EntityRefObjectType([
    'name' => 'User',
    'keyFields' => ['id', 'email'],
    'fields' => [
        'id' => [
            'type' => Type::int(),
            'isExternal' => true
        ],
        'email' => [
            'type' => Type::string(),
            'isExternal' => true
        ]
    ]
]);
```

The following are all the configuration settings supported (click on each one to see the docs.)

* **[`isExternal`](https://www.apollographql.com/docs/apollo-server/federation/federation-spec/#external) —** marks a field as owned by another service.

* **[`provides`](https://www.apollographql.com/docs/apollo-server/federation/federation-spec/#provides) —** annotates the expected returned fieldset from a field on a base type that is guaranteed to be selectable by the gateway.

* **[`requires`](https://www.apollographql.com/docs/apollo-server/federation/federation-spec/#requires) —** annotates the required input fieldset from a base type for a resolver. It is used to develop a query plan where the required fields may not be needed by the client, but the service may need additional information from other services.

### Federated schema

The `FederatedSchema` class extends from the base `GraphQL\Schema` class and augments a schema configuration using entity types and federated field annotations with Apollo Federation metadata. [See the docs](https://www.apollographql.com/docs/apollo-server/federation/federation-spec/#federation-schema-specification) for more info.

```php
use GraphQL\GraphQL;
use Apollo\Federation\FederatedSchema;


$schema = new FederatedSchema($config);
$query = 'query GetServiceSDL { _service { sdl } }';

$result = GraphQL::executeQuery($schema, $query);
```
