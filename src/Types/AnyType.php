<?php

declare(strict_types=1);

namespace Apollo\Federation\Types;

use GraphQL\Type\Definition\CustomScalarType;

/**
 * Simple representation of an agnostic scalar value.
 */
class AnyType extends CustomScalarType
{
    public function __construct()
    {
        $config = [
            'name' => self::getTypeName(),
            'serialize' => function ($value) {
                return $value;
            }
        ];
        parent::__construct($config);
    }

    public static function getTypeName(): string
    {
        return '_Any';
    }
}
