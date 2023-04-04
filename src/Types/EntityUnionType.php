<?php

declare(strict_types=1);

namespace Apollo\Federation\Types;

use GraphQL\Type\Definition\UnionType;

/**
 * The union of all entities defined within this schema.
 */
class EntityUnionType extends UnionType
{

    /**
     * @param array $entityTypes all entity types.
     */
    public function __construct(array $entityTypes)
    {
        $config = [
            'name' => self::getTypeName(),
            'types' => array_values($entityTypes)
        ];
        parent::__construct($config);
    }

    public static function getTypeName(): string
    {
        return '_Entity';
    }
}
