<?php

declare(strict_types=1);

namespace Apollo\Federation\Directives;

use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\Type;

/**
 * The `@key` directive is used to indicate a combination of fields that can be used to uniquely
 * identify and fetch an object or interface.
 */
class KeyDirective extends Directive
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'key',
            'locations' => [DirectiveLocation::OBJECT, DirectiveLocation::IFACE],
            'args' => [
                new FieldArgument([
                    'name' => 'fields',
                    'type' => Type::nonNull(Type::string()),
                ]),
            ],
        ]);
    }
}
