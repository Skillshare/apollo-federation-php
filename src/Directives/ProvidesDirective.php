<?php

namespace Apollo\Federation\Directives;

use Apollo\Federation\Enum\DirectiveEnum;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\Type;

/**
 * The `@provides` directive is used to annotate the expected returned fieldset from a field
 * on a base type that is guaranteed to be selectable by the gateway.
 */
class ProvidesDirective extends Directive
{
    public function __construct()
    {
        parent::__construct([
            'name' => DirectiveEnum::PROVIDES,
            'locations' => [DirectiveLocation::FIELD_DEFINITION],
            'args' => [
                new FieldArgument([
                    'name' => 'fields',
                    'type' => Type::nonNull(Type::string()),
                ]),
            ],
        ]);
    }
}
