<?php

namespace Apollo\Federation\Directives;

use Apollo\Federation\Enum\DirectiveEnum;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive;

/**
 * The `@external` directive is used to mark a field as owned by another service. This
 * allows service A to use fields from service B while also knowing at runtime the
 * types of that field.
 *
 * @see https://www.apollographql.com/docs/federation/federated-types/federated-directives/#external
 */
class ExternalDirective extends Directive
{
    public function __construct()
    {
        parent::__construct([
            'name' => DirectiveEnum::EXTERNAL,
            'locations' => [DirectiveLocation::FIELD_DEFINITION],
        ]);
    }
}
