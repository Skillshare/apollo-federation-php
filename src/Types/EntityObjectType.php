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
 *
 * Entity types can also set attributes to its fields to hint the gateway on how to resolve them.
 *
 *     $userType = new Apollo\Federation\Types\EntityObjectType([
 *       'name' => 'User',
 *       'keyFields' => ['id', 'email'],
 *       'fields' => [
 *         'id' => [
 *           'type' => Types::int(),
 *           'isExternal' => true,
 *         ]
 *       ]
 *     ]);
 *
 */
class EntityObjectType extends ObjectType
{
    /** @var array */
    private $keyFields;

    /** @var callable */
    private $referenceResolver;

    /**
     * @param mixed[] $config
     */
    public function __construct(array $config)
    {
        self::validateFields($config);
        self::validateKeyFields($config);

        $this->keyFields = $config['keyFields'];

        if (isset($config['__resolveReference'])) {
            self::validateResolveReference($config);
            $this->referenceResolver = $config['__resolveReference'];
        }

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

    /**
     * Gets whether this entity has a resolver set
     *
     * @return bool
     */
    public function hasReferenceResolver(): bool
    {
        return isset($this->referenceResolver);
    }

    /**
     * Resolves an entity from a reference
     *
     * @param mixed $ref
     * @param mixed $context
     * @param mixed $info
     */
    public function resolveReference($ref, $context = null, $info = null)
    {
        $this->validateReferenceResolver();
        $this->validateReferenceKeys($ref);

        $entity = ($this->referenceResolver)($ref, $context, $info);

        $entity['__typename'] = $ref['__typename'];

        return $entity;
    }

    private function validateReferenceResolver()
    {
        Utils::invariant(isset($this->referenceResolver), 'No reference resolver was set in the configuration.');
    }

    private function validateReferenceKeys($ref)
    {
        Utils::invariant(isset($ref['__typename']), 'Type name must be provided in the reference.');

        $refKeys = array_keys($ref);
        $refContainsKeys = count(array_intersect($this->getKeyFields(), $refKeys)) === count($this->getKeyFields());

        Utils::invariant($refContainsKeys, 'Key fields are missing from the entity reference.');
    }

    private static function validateFields(array $config)
    {
        if (is_callable($config['fields'])) {
            $fields = $config['fields']();
        } else {
            $fields = $config['fields'];
        }

        Utils::invariant(isset($fields) && is_array($fields), 'Fields must be specified.');

        foreach ($fields as $field) {
            if (isset($field['isExternal'])) {
                Utils::invariant(is_bool($field['isExternal']), "Config property 'isExternal' should be a boolean.");
            }

            if (isset($field['provides'])) {
                Utils::invariant(is_string($field['provides']), "Config property 'provides' should be a string.");
            }

            if (isset($field['requires'])) {
                Utils::invariant(is_string($field['requires']), "Config property 'requires' should be a string.");
            }
        }
    }

    public static function validateKeyFields(array $config)
    {
        if (is_callable($config['fields'])) {
            $fields = $config['fields']();
        } else {
            $fields = $config['fields'];
        }

        Utils::invariant(
            isset($config['keyFields']) && is_array($config['keyFields']),
            'Entity key fields must be provided and has to be an array.'
        );

        foreach ($config['keyFields'] as $keyField) {
            Utils::invariant(
                array_key_exists($keyField, $fields),
                'Entity key refers to a field that does not exist in the fields array.'
            );
        }
    }

    public static function validateResolveReference(array $config)
    {
        Utils::invariant(is_callable($config['__resolveReference']), 'Reference resolver has to be callable.');
    }
}
