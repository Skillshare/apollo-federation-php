<?php

declare(strict_types=1);

namespace Apollo\Federation\Directives;

use Apollo\Federation\Enum\DirectiveEnum;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive;

/**
 * The `@shareable` directive indicates that an object type's field is allowed to be resolved
 * by multiple subgraphs (by default, each field can be resolved by only one subgraph).
 *
 * @see https://www.apollographql.com/docs/federation/federated-types/federated-directives/#shareable
 */
class ShareableDirective extends Directive
{
    public function __construct()
    {
        parent::__construct([
            'name' => DirectiveEnum::SHAREABLE,
            'locations' => [DirectiveLocation::FIELD_DEFINITION, DirectiveLocation::OBJECT],
        ]);
    }
}
