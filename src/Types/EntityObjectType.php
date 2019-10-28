<?php

declare(strict_types=1);

namespace Apollo\Federation\Types;

use GraphQL\Utils\Utils;
use GraphQL\Type\Definition\ObjectType;

use array_key_exists;

/**
 * An entity is a type that can be referenced by another service. Entities create
 * connection points between services and form the basic building blocks of a federated
 * graph. Entities have a primary key whose value uniquely identifies a specific instance
 * of the type, similar to the function of a primary key in a SQL table
 * (see [related docs](https://www.apollographql.com/docs/apollo-server/federation/core-concepts/#entities-and-keys)).
 *
 * The `keyFields` property is required in the configuration, indicating the fields that
 * serve as the unique keys or identifiers of the entity.
 *
 * Sample usage:
 *
 *     $userType = new Apollo\Federation\Types\EntityObjectType([
 *       'name' => 'User',
 *       'keyFields' => ['id', 'email'],
 *       'fields' => [...]
 *     ]);
 */
class EntityObjectType extends ObjectType
{
    /** @var array */
    private $keyFields;

    /**
     * @param mixed[] $config
     */
    public function __construct(array $config)
    {
        Utils::invariant(
            isset($config['keyFields']) && is_array($config['keyFields']),
            'Entity key fields must be provided and has to be an array.'
        );

        foreach ($config['keyFields'] as $keyField) {
            Utils::invariant(
                array_key_exists($keyField, $config['fields']),
                'Entity key refers to a field that does not exist in the fields array.'
            );
        }

        $this->keyFields = $config['keyFields'];

        parent::__construct($config);
    }

    /**
     * Gets the fields that serve as the unique key or identifier of the entity.
     *
     * @return array
     */
    public function getKeyFields(): array
    {
        return $this->keyFields;
    }
}
