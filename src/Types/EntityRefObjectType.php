<?php

declare(strict_types=1);

namespace Apollo\Federation\Types;

use GraphQL\Utils\Utils;

/**
 * An entity reference is a type referencing an entity owned by another service. Usually,
 * entity references are stub types containing only the key fields necessary for the
 * Apollo Gateway {@see https://www.apollographql.com/docs/intro/platform/#gateway } to
 * resolve the entity during query execution.
 *
 * @see https://www.apollographql.com/docs/federation/v1/entities#referencing-entities
 * @see https://www.apollographql.com/docs/federation/entities#referencing-an-entity-without-contributing-fields
 */
class EntityRefObjectType extends EntityObjectType
{
    public function __construct(array $config)
    {
        parent::__construct($config);

        $keys = $this->getKeys();
        Utils::invariant(
            1 === \count($keys),
            'There is invalid config of %s. Referenced entity must have exactly one directive @key.',
            $this->name
        );

        /** @var array<string,mixed> $key */
        $key = reset($keys);
        Utils::invariant(
            \array_key_exists('resolvable', $key) && false === $key['resolvable'],
            'There is invalid config of %s. Referenced entity directive @key must have argument "resolvable" with value `false`.',
            $this->name
        );
    }
}
