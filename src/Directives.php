<?php

declare(strict_types=1);

namespace Apollo\Federation;

use Apollo\Federation\Directives\ExternalDirective;
use Apollo\Federation\Directives\KeyDirective;
use Apollo\Federation\Directives\ProvidesDirective;
use Apollo\Federation\Directives\RequiresDirective;

/**
 * Helper class to get directives for annotating federated entity types.
 */
class Directives
{
    /**
     * @var array{key: KeyDirective, external: ExternalDirective, requires: RequiresDirective, provides: ProvidesDirective}
     */
    private static array $directives;

    /**
     * Gets the @key directive
     */
    public static function key(): KeyDirective
    {
        return self::getDirectives()['key'];
    }

    /**
     * Gets the @external directive
     */
    public static function external(): ExternalDirective
    {
        return self::getDirectives()['external'];
    }

    /**
     * Gets the @requires directive
     */
    public static function requires(): RequiresDirective
    {
        return self::getDirectives()['requires'];
    }

    /**
     * Gets the @provides directive
     */
    public static function provides(): ProvidesDirective
    {
        return self::getDirectives()['provides'];
    }

    /**
     * Gets the directives that can be used on federated entity types
     *
     * @return array{key: KeyDirective, external: ExternalDirective, requires: RequiresDirective, provides: ProvidesDirective}
     */
    public static function getDirectives(): array
    {
        if (!self::$directives) {
            self::$directives = [
                'key' => new KeyDirective(),
                'external' => new ExternalDirective(),
                'requires' => new RequiresDirective(),
                'provides' => new ProvidesDirective(),
            ];
        }

        return self::$directives;
    }
}
