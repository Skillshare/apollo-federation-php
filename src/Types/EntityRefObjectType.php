<?php

declare(strict_types=1);

namespace Apollo\Federation\Types;

/**
 * An entity reference is a type referencing an entity owned by another service. Usually,
 * entity references are stub types containing only the key fields necessary for the
 * Apollo Gateway {@see https://www.apollographql.com/docs/intro/platform/#gateway } to
 * resolve the entity during query execution.
 */
class EntityRefObjectType extends EntityObjectType
{
}
