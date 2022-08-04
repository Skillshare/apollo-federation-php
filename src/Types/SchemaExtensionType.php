<?php

declare(strict_types=1);

namespace Apollo\Federation\Types;

use GraphQL\Type\Definition\CompositeType;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\OutputType;
use GraphQL\Type\Definition\Type;

class SchemaExtensionType extends Type implements OutputType, CompositeType, NamedType
{
    public const FIELD_KEY_LINKS = 'links';

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }
}
