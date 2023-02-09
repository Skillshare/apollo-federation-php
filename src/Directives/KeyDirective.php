<?php

namespace Apollo\Federation\Directives;

use Apollo\Federation\Enum\DirectiveEnum;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\Type;

/**
 * The `@key` directive is used to indicate a combination of fields that can be used to uniquely
 * identify and fetch an object or interface.
 *
 * @see https://www.apollographql.com/docs/federation/federated-types/federated-directives/#key
 */
class KeyDirective extends Directive
{
    public const ARGUMENT_FIELDS = 'fields';
    public const ARGUMENT_RESOLVABLE = 'resolvable';

    public function __construct()
    {
        parent::__construct([
            'name' => DirectiveEnum::KEY,
            'locations' => [DirectiveLocation::OBJECT, DirectiveLocation::IFACE],
            'args' => [
                new FieldArgument([
                    'name' => self::ARGUMENT_FIELDS,
                    'type' => Type::nonNull(Type::string()),
                ]),
                new FieldArgument([
                    'name' => self::ARGUMENT_RESOLVABLE,
                    'type' => Type::boolean(),
                ]),
            ],
        ]);
    }
}
