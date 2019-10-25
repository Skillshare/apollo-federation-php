<?php

namespace Apollo\Federation\Directives;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Language\DirectiveLocation;

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
