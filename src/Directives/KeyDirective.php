<?php

namespace Apollo\Federation\Directives;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\Directive;
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
            'name' => 'key',
            'locations' => [DirectiveLocation::OBJECT, DirectiveLocation::IFACE],
            'args' => [
                'fields' => Type::nonNull(Type::string())
            ]
        ]);
    }
}
