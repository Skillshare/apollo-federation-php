<?php

/**
 *
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

use GraphQL\Error\Error;
use GraphQL\Error\SerializationError;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;
use Apollo\Federation\Types\EntityObjectType;
use Apollo\Federation\Types\EntityRefObjectType;

use function implode;
use function ksort;
use function sprintf;

/**
 * Given an instance of Schema, prints it in GraphQL type language.
 */
class FederatedSchemaPrinter extends SchemaPrinter
{
    /**
     * Accepts options as a second argument:
     *
     *    - commentDescriptions:
     *        Provide true to use preceding comments as the description.
     *
     * @param Schema $schema
     * @param bool[] $options
     *
     * @return string
     * @throws Error
     * @throws SerializationError
     * @throws \JsonException
     * @api
     */
    public static function doPrint(Schema $schema, array $options = []): string
    {
        return self::printFilteredSchema(
            $schema,
            static fn (Directive $type) => !Directive::isSpecifiedDirective($type) && !self::isFederatedDirective($type),
            static fn (NamedType $type) => !$type->isBuiltInType(),
            $options
        );
    }

    public static function isFederatedDirective($type): bool
    {
        return in_array($type->name, ['key', 'provides', 'requires', 'external']);
    }

    /**
     * @param Type $type
     * @param bool[] $options
     * @return string
     * @throws Error
     * @throws SerializationError
     * @throws \JsonException
     */
    public static function printType(Type $type, array $options = []): string
    {
        if ($type instanceof EntityObjectType || $type instanceof EntityRefObjectType) {
            return self::printEntityObject($type, $options);
        }

        return parent::printType($type, $options);
    }

    /**
     * @param ObjectType $type
     * @param bool[] $options
     * @return string
     * @throws \JsonException
     */
    protected static function printObject(ObjectType $type, array $options): string
    {
        $queryExtends = $type->name === 'Query' || $type->name === 'Mutation' ? 'extend ' : '';

        return static::printDescription($options, $type)
            . "{$queryExtends}type {$type->name}"
            . static::printImplementedInterfaces($type)
            . static::printFields($options, $type);
    }

    /**
     * @param EntityObjectType $type
     * @param bool[] $options
     * @return string
     * @throws \JsonException
     */
    protected static function printEntityObject(EntityObjectType $type, array $options): string
    {
        $keyDirective = '';

        foreach ($type->getKeyFields() as $keyField) {
            $keyDirective = $keyDirective . sprintf(' @key(fields: "%s")', $keyField);
        }

        $isEntityRef = $type instanceof EntityRefObjectType;
        $extends = $isEntityRef ? 'extend ' : '';

        return static::printDescription($options, $type)
            . "{$extends}type {$type->name}"
            . static::printImplementedInterfaces($type)
            . $keyDirective
            . static::printFields($options, $type);
    }

    /**
     * @param bool[] $options
     * @param $type
     * @return string
     * @throws SerializationError
     * @throws \JsonException
     */
    protected static function printFields($options, $type): string
    {
        $fields = [];
        $firstInBlock = true;
        $previousHasDescription = false;
        $fieldDefinitions = $type->getFields();

        if (isset($options['sortFields']) && $options['sortFields']) {
            ksort($fieldDefinitions);
        }

        foreach ($fieldDefinitions as $f) {
            $hasDescription = $f->description !== null;
            if ($previousHasDescription && ! $hasDescription) {
                $fields[] = '';
            }

            $fields[] = static::printDescription($options, $f, '  ', $firstInBlock)
                . '  '
                . $f->name
                . static::printArgs($options, $f->args, '  ')
                . ': '
                . $f->getType()->toString()
                . static::printDeprecated($f)
                . ' '
                . self::printFieldFederatedDirectives($f);
            $firstInBlock = false;
            $previousHasDescription = $hasDescription;
        }

        return static::printBlock($fields);
    }

    protected static function printFieldFederatedDirectives($field): string
    {
        $directives = [];

        if (isset($field->config['isExternal']) && $field->config['isExternal'] === true) {
            $directives[] = '@external';
        }

        if (isset($field->config['provides'])) {
            $directives[] = "@provides(fields: \"{$field->config['provides']}\")";
        }

        if (isset($field->config['requires'])) {
            $directives[] = "@requires(fields: \"{$field->config['requires']}\")";
        }

        return implode(' ', $directives);
    }
}
