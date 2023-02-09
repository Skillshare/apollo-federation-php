<?php

namespace Apollo\Federation\Directives;

use Apollo\Federation\Enum\DirectiveEnum;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\CustomScalarType;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\Type;

/**
 * The `@link` directive is used to ...
 *
 * @see https://specs.apollo.dev/link/v1.0/
 */
class LinkDirective extends Directive
{
    public function __construct()
    {
        $linkImport = new CustomScalarType([
            'name' => 'link_Import',
            'serialize' => static function ($value) {
                $data = json_encode($value);
                if (json_last_error()) {
                    throw new \RuntimeException(json_last_error_msg());
                }

                return $data;
            },
            'parseValue' => static function ($value) {
                if (\is_string($value)) {
                    return $value;
                }

                if (!\is_array($value)) {
                    throw new InvariantViolation('"link_Import" must be a string or an array');
                }
                $permittedKeys = ['name', 'as'];
                $keys = array_keys($value);
                if ($permittedKeys !== array_intersect($permittedKeys, $keys) || array_diff($keys, $permittedKeys)) {
                    throw new InvariantViolation('"link_Import" must contain only keys "name" and "as" and they are required');
                }

                if (2 !== \count(array_filter($value))) {
                    throw new InvariantViolation('The "name" and "as" part of "link_Import" be not empty');
                }

                $prefixes = array_unique([$value['name'][0], $value['as'][0]]);
                if (\in_array('@', $prefixes, true) && 2 !== \count($prefixes)) {
                    // https://specs.apollo.dev/link/v1.0/#Import
                    throw new InvariantViolation('The "name" and "as" part of "link_Import" be of the same type');
                }

                return $value;
            },
        ]);

        parent::__construct([
            'name' => DirectiveEnum::LINK,
            'locations' => [DirectiveLocation::SCHEMA],
            'args' => [
                new FieldArgument([
                    'name' => 'url',
                    'type' => Type::nonNull(Type::string()),
                ]),
                new FieldArgument([
                    'name' => 'as',
                    'type' => Type::string(),
                ]),
                new FieldArgument([
                    'name' => 'for',
                    // TODO use union type (enum & string) and declare required enum
                    'type' => Type::string(),
                ]),
                new FieldArgument([
                    'name' => 'import',
                    'type' => Type::listOf(Type::nonNull($linkImport)),
                ]),
            ],
            'isRepeatable' => true,
        ]);
    }
}
