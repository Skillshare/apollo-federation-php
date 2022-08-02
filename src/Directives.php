<?php

declare(strict_types=1);

namespace Apollo\Federation;

use Apollo\Federation\Directives\ExternalDirective;
use Apollo\Federation\Directives\KeyDirective;
use Apollo\Federation\Directives\ProvidesDirective;
use Apollo\Federation\Directives\RequiresDirective;
use Apollo\Federation\Enum\DirectiveEnum;
use GraphQL\Type\Definition\Directive;

/**
 * Helper class to get directives for annotating federated entity types.
 */
class Directives
{
    /** @var array<string,Directive> */
    private static $directives = null;

    /**
     * Gets the @key directive.
     */
    public static function key(): KeyDirective
    {
        return self::getDirectives()[DirectiveEnum::KEY];
    }

    /**
     * Gets the @external directive.
     */
    public static function external(): ExternalDirective
    {
        return self::getDirectives()[DirectiveEnum::EXTERNAL];
    }

    /**
     * Gets the @requires directive.
     */
    public static function requires(): RequiresDirective
    {
        return self::getDirectives()[DirectiveEnum::REQUIRES];
    }

    /**
     * Gets the @provides directive.
     */
    public static function provides(): ProvidesDirective
    {
        return self::getDirectives()[DirectiveEnum::PROVIDES];
    }

    /**
     * Gets the directives that can be used on federated entity types.
     *
     * @return array<string,Directive>
     */
    public static function getDirectives(): array
    {
        if (!self::$directives) {
            self::$directives = [
                DirectiveEnum::EXTERNAL => new ExternalDirective(),
                DirectiveEnum::KEY => new KeyDirective(),
                DirectiveEnum::REQUIRES => new RequiresDirective(),
                DirectiveEnum::PROVIDES => new ProvidesDirective(),
            ];
        }

        return self::$directives;
    }
}
