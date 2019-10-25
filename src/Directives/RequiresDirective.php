<?php

namespace Apollo\Federation\Directives;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Language\DirectiveLocation;

class RequiresDirective extends Directive
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'requires',
            'locations' => [DirectiveLocation::FIELD_DEFINITION],
            'args' => [
                new FieldArgument([
                    'name' => 'fields',
                    'type' => Type::nonNull(Type::string())
                ])
            ]
        ]);
    }
}
