<?php

namespace Apollo\Federation\Directives;

use Apollo\Federation\Enum\DirectiveEnum;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Language\DirectiveLocation;

/**
 * The `@requires` directive is used to annotate the required input fieldset from a base type
 * for a resolver. It is used to develop a query plan where the required fields may not be
 * needed by the client, but the service may need additional information from other services.
 */
class RequiresDirective extends Directive
{
    public function __construct()
    {
        parent::__construct([
            'name' => DirectiveEnum::REQUIRES,
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
