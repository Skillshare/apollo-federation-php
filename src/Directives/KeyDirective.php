<?php

namespace Apollo\Federation\Directives;

use Apollo\Federation\Enum\DirectiveEnum;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Language\DirectiveLocation;

/**
 * The `@key` directive is used to indicate a combination of fields that can be used to uniquely
 * identify and fetch an object or interface.
 */
class KeyDirective extends Directive
{
    public function __construct()
    {
        parent::__construct([
            'name' => DirectiveEnum::KEY,
            'locations' => [DirectiveLocation::OBJECT, DirectiveLocation::IFACE],
            'args' => [
                new FieldArgument([
                    'name' => 'fields',
                    'type' => Type::nonNull(Type::string())
                ])
            ]
        ]);
    }
}
