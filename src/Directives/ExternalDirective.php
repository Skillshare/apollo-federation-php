<?php

namespace Apollo\Federation\Directives;

use GraphQL\Type\Definition\Directive;
use GraphQL\Language\DirectiveLocation;

/**
 * The `@external` directive is used to mark a field as owned by another service. This
 * allows service A to use fields from service B while also knowing at runtime the
 * types of that field.
 */
class ExternalDirective extends Directive
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'external',
            'locations' => [DirectiveLocation::FIELD_DEFINITION]
        ]);
    }
}
