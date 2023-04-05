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
     * @param array|callable $entityTypes all entity types or a callable to retrieve them
     */
    public function __construct($entityTypes)
    {
        $config = [
            'name' => self::getTypeName(),
            'types' => is_callable($entityTypes) 
                ? fn () => array_values($entityTypes())
                : array_values($entityTypes)
                
        ];
        parent::__construct($config);
    }

    public static function getTypeName(): string
    {
        return '_Entity';
    }
}
