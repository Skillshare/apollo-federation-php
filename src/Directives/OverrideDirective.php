<?php

declare(strict_types=1);

namespace Apollo\Federation\Directives;

use Apollo\Federation\Enum\DirectiveEnum;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\Type;

/**
 * The `@override` directive indicates that a field is now resolved by this subgraph
 * instead of another subgraph where it's also defined.
 *
 * @see https://www.apollographql.com/docs/federation/federated-types/federated-directives/#override
 */
class OverrideDirective extends Directive
{
    public function __construct()
    {
        parent::__construct([
            'name' => DirectiveEnum::OVERRIDE,
            'locations' => [DirectiveLocation::FIELD_DEFINITION],
            'args' => [
                new FieldArgument([
                    'name' => 'from',
                    'type' => Type::nonNull(Type::string()),
                ]),
            ],
        ]);
    }
}
