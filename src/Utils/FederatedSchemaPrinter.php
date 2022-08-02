<?php

/**
 * This source file includes modified code from webonyx/graphql-php.
 *
 * Copyright (c) 2015-present, Webonyx, LLC.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @copyright Copyright (c) Webonyx, LLC.
 * @license https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Apollo\Federation\Utils;

use Apollo\Federation\Enum\DirectiveEnum;
use Apollo\Federation\FederatedSchema;
use Apollo\Federation\Types\EntityObjectType;
use Apollo\Federation\Types\EntityRefObjectType;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\TypeWithFields;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;

use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function implode;
use function sprintf;

/**
 * Given an instance of Schema, prints it in GraphQL type language.
 */
class FederatedSchemaPrinter extends SchemaPrinter
{
    /**
     * Accepts options as a second argument:
     *    - commentDescriptions:
     *        Provide true to use preceding comments as the description.
     *
     * @param array<string, bool> $options
     *
     * @api
     */
    public static function doPrint(Schema $schema, array $options = []): string
    {
        return static::printFilteredSchema(
            $schema,
            static fn (Directive $type): bool => !Directive::isSpecifiedDirective($type) && !static::isFederatedDirective($type),
            static fn (Type $type): bool => !Type::isBuiltInType($type),
            $options
        );
    }

    public static function isFederatedDirective(Directive $type): bool
    {
        return \in_array($type->name, DirectiveEnum::getAll(), true);
    }

    /**
     * @param array<string, bool> $options
     */
    public static function printType(Type $type, array $options = []): string
    {
        if ($type instanceof EntityObjectType /* || $type instanceof EntityRefObjectType */) {
            return static::printEntityObject($type, $options);
        }

        if (($type instanceof ScalarType && FederatedSchema::RESERVED_TYPE_ANY === $type->name)
            || ($type instanceof ObjectType && FederatedSchema::RESERVED_TYPE_SERVICE === $type->name)
            || ($type instanceof UnionType && FederatedSchema::RESERVED_TYPE_ENTITY === $type->name)) {
            return '';
        }

        return parent::printType($type, $options);
    }

    /**
     * @param array<string, bool> $options
     */
    protected static function printObject(ObjectType $type, array $options): string
    {
        if (empty($type->getFields())) {
            return '';
        }

        $implementedInterfaces = static::printImplementedInterfaces($type);

        $queryExtends = \in_array($type->name, [FederatedSchema::RESERVED_TYPE_QUERY, FederatedSchema::RESERVED_TYPE_MUTATION], true)
            ? 'extend '
            : '';

        return static::printDescription($options, $type) .
            sprintf(
                "%stype %s%s {\n%s\n}",
                $queryExtends,
                $type->name,
                $implementedInterfaces,
                static::printFields($options, $type)
            );
    }

    /**
     * @param array<string, bool> $options
     */
    protected static function printEntityObject(EntityObjectType $type, array $options): string
    {
        $implementedInterfaces = static::printImplementedInterfaces($type);
        $keyDirective = static::printKeyDirective($type);

        $isEntityRef = $type instanceof EntityRefObjectType;
        $extends = $isEntityRef ? 'extend ' : '';

        return static::printDescription($options, $type) .
            sprintf(
                "%stype %s%s%s {\n%s\n}",
                $extends,
                $type->name,
                $implementedInterfaces,
                $keyDirective,
                static::printFields($options, $type)
            );
    }

    /**
     * @param array<string, bool> $options
     * @param EntityObjectType|InterfaceType|ObjectType|TypeWithFields $type
     */
    protected static function printFields(array $options, $type): string
    {
        $fields = array_values($type->getFields());

        if (FederatedSchema::RESERVED_TYPE_QUERY === $type->name) {
            $fields = array_filter($fields, static function (FieldDefinition $field): bool {
                $excludedFields = [FederatedSchema::RESERVED_FIELD_SERVICE, FederatedSchema::RESERVED_FIELD_ENTITIES];

                return !\in_array($field->name, $excludedFields, true);
            });
        }

        return implode(
            "\n",
            array_map(
                static function (FieldDefinition $f, $i) use ($options) {
                    return static::printDescription($options, $f, '  ', !$i) .
                        '  ' .
                        $f->name .
                        static::printArgs($options, $f->args, '  ') .
                        ': ' .
                        (string) $f->getType() .
                        static::printDeprecated($f) .
                        ' ' .
                        static::printFieldFederatedDirectives($f);
                },
                $fields,
                array_keys($fields)
            )
        );
    }

    protected static function printFieldFederatedDirectives(FieldDefinition $field): string
    {
        $directives = [];

        if (isset($field->config[EntityObjectType::FIELD_DIRECTIVE_IS_EXTERNAL])
            && true === $field->config[EntityObjectType::FIELD_DIRECTIVE_IS_EXTERNAL]
        ) {
            $directives[] = '@external';
        }

        if (isset($field->config[EntityObjectType::FIELD_DIRECTIVE_PROVIDES])) {
            $directives[] = sprintf('@provides(fields: "%s")', static::printKeyFields($field->config[EntityObjectType::FIELD_DIRECTIVE_PROVIDES]));
        }

        if (isset($field->config[EntityObjectType::FIELD_DIRECTIVE_REQUIRES])) {
            $directives[] = sprintf('@requires(fields: "%s")', static::printKeyFields($field->config[EntityObjectType::FIELD_DIRECTIVE_REQUIRES]));
        }

        return implode(' ', $directives);
    }

    protected static function printImplementedInterfaces(ObjectType $type): string
    {
        $interfaces = $type->getInterfaces();

        return !empty($interfaces)
            ? ' implements ' . implode(' & ', array_map(static fn (InterfaceType $i): string => $i->name, $interfaces))
            : '';
    }

    protected static function printKeyDirective(EntityObjectType $type): string
    {
        $keyDirective = '';

        foreach ($type->getKeyFields() as $keyField) {
            $keyDirective .= sprintf(' @key(fields: "%s")', static::printKeyFields($keyField));
        }

        return $keyDirective;
    }

    /**
     * Print simple and compound primary key fields
     * {@see https://www.apollographql.com/docs/federation/v1/entities#compound-primary-keys }.
     *
     * @param string|array<string|int, mixed> $keyFields
     */
    protected static function printKeyFields($keyFields): string
    {
        $parts = [];
        foreach (((array) $keyFields) as $index => $keyField) {
            if (\is_string($keyField)) {
                $parts[] = $keyField;
            } elseif (\is_array($keyField)) {
                $parts[] = sprintf('%s { %s }', $index, static::printKeyFields($keyField));
            } else {
                throw new \InvalidArgumentException('Invalid keyField config');
            }
        }

        return implode(' ', $parts);
    }
}
