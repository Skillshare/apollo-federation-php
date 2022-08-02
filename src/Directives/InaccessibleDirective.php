<?php

declare(strict_types=1);

namespace Apollo\Federation\Directives;

use Apollo\Federation\Enum\DirectiveEnum;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive;

/**
 * The `@inaccessible` directive indicates that a field or type should be omitted from the gateway's API schema,
 * even if it's also defined in other subgraphs.
 *
 * @see https://www.apollographql.com/docs/federation/federated-types/federated-directives/#inaccessible
 */
class InaccessibleDirective extends Directive
{
    public function __construct()
    {
        parent::__construct([
            'name' => DirectiveEnum::INACCESSIBLE,
            'locations' => [
                DirectiveLocation::FIELD_DEFINITION,
                DirectiveLocation::IFACE,
                DirectiveLocation::OBJECT,
                DirectiveLocation::UNION,
            ],
        ]);
    }
}
