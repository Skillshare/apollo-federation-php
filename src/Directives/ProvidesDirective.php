<?php

namespace Apollo\Federation\Directives;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Language\DirectiveLocation;

/**
 * The `@provides` directive is used to annotate the expected returned fieldset from a field
 * on a base type that is guaranteed to be selectable by the gateway.
 */
class ProvidesDirective extends Directive
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'provides',
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
