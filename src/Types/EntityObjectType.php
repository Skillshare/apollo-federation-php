<?php

declare(strict_types=1);

namespace Apollo\Federation\Types;

use Apollo\Federation\FederatedSchema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Utils\Utils;

/**
 * An entity is a type that can be referenced by another service. Entities create
 * connection points between services and form the basic building blocks of a federated
 * graph. Entities have a primary key whose value uniquely identifies a specific instance
 * of the type, similar to the function of a primary key in a SQL table
 * see related docs {@see https://www.apollographql.com/docs/apollo-server/federation/core-concepts/#entities-and-keys }.
 *
 * The `keyFields` property is required in the configuration, indicating the fields that
 * serve as the unique keys or identifiers of the entity.
 *
 * Sample usage:
 * <code>
 *     $userType = new Apollo\Federation\Types\EntityObjectType([
 *       'name' => 'User',
 *       'keys' => [['fields' => 'id'], ['fields' => 'email']],
 *       'fields' => [...]
 *     ]);
 * </code>
 *
 * Entity types can also set attributes to its fields to hint the gateway on how to resolve them.
 * <code>
 *     $userType = new Apollo\Federation\Types\EntityObjectType([
 *       'name' => 'User',
 *       'keys' => [['fields' => 'id', 'resolvable': false ]],
 *       'fields' => [
 *         'id' => [
 *           'type' => Types::int(),
 *           'isExternal' => true,
 *         ]
 *       ]
 *     ]);
 * </code>
 */
class EntityObjectType extends ObjectType
{
    public const FIELD_KEYS = 'keys';
    public const FIELD_REFERENCE_RESOLVER = '__resolveReference';

    public const FIELD_DIRECTIVE_IS_EXTERNAL = 'isExternal';
    public const FIELD_DIRECTIVE_PROVIDES = 'provides';
    public const FIELD_DIRECTIVE_REQUIRES = 'requires';

    /** @var callable|null */
    public $referenceResolver = null;

    /**
     * @var array<int,array{fields: array<int,string>|array<int|string,string|array<int|string,mixed>>, resolvable: bool }>
     */
    private array $keys;

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(array $config)
    {
        Utils::invariant(
            !(\array_key_exists(self::FIELD_KEYS, $config) && \array_key_exists('keyFields', $config)),
            'Use only one way to define directives @key.'
        );

        $this->keys = $config[self::FIELD_KEYS]
            ?? array_map(static fn ($x): array => ['fields' => $x], $config['keyFields']);

        if (isset($config[self::FIELD_REFERENCE_RESOLVER])) {
            self::validateResolveReference($config);
            $this->referenceResolver = $config[self::FIELD_REFERENCE_RESOLVER];
        }

        parent::__construct($config);
    }

    /**
     * Gets the fields that serve as the unique key or identifier of the entity.
     *
     * @deprecated Use {@see getKeys()}
     *
     * @return array<int,string>|array<int|string,string|array<int|string,mixed>>
     *
     * @codeCoverageIgnore
     */
    public function getKeyFields(): array
    {
        @trigger_error(
            'Since skillshare/apollo-federation-php 2.0.0: '
            . 'Method \Apollo\Federation\Types\EntityObjectType::getKeyFields() is deprecated. '
            . 'Use \Apollo\Federation\Types\EntityObjectType::getKeys() instead of it.',
            \E_USER_DEPRECATED
        );

        return array_map(static fn(array $x) => $x['fields'], $this->keys);
    }

    /**
     * Gets the fields that serve as the unique key or identifier of the entity.
     *
     * @return array<int,array{fields: array<int,string>|array<int|string,string|array<int|string,mixed>>, resolvable: bool }>
     */
    public function getKeys(): array
    {
        return $this->keys;
    }

    /**
     * Gets whether this entity has a resolver set.
     */
    public function hasReferenceResolver(): bool
    {
        return isset($this->referenceResolver);
    }

    /**
     * Resolves an entity from a reference.
     *
     * @param mixed|null $ref
     * @param mixed|null $context
     * @param mixed|null $info
     *
     * @retrun mixed|null
     */
    public function resolveReference($ref, $context = null, $info = null)
    {
        $this->validateReferenceResolver();
        $this->validateReferenceKeys($ref);

        return ($this->referenceResolver)($ref, $context, $info);
    }

    private function validateReferenceResolver(): void
    {
        Utils::invariant(isset($this->referenceResolver), 'No reference resolver was set in the configuration.');
    }

    /**
     * @param array{ __typename: mixed } $ref
     */
    private function validateReferenceKeys(array $ref): void
    {
        Utils::invariant(
            isset($ref[FederatedSchema::RESERVED_FIELD_TYPE_NAME])
            && $ref[FederatedSchema::RESERVED_FIELD_TYPE_NAME] === $this->config['name'],
            'Type name must be provided in the reference.'
        );
    }

    /**
     * @param array{ __resolveReference: mixed } $config
     */
    public static function validateResolveReference(array $config): void
    {
        Utils::invariant(\is_callable($config[self::FIELD_REFERENCE_RESOLVER]), 'Reference resolver has to be callable.');
    }
}
