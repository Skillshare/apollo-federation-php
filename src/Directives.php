<?php

declare(strict_types=1);

namespace Apollo\Federation;

use Apollo\Federation\Directives\KeyDirective;
use Apollo\Federation\Directives\ExtendsDirective;
use Apollo\Federation\Directives\ExternalDirective;
use Apollo\Federation\Directives\ProvidesDirective;
use Apollo\Federation\Directives\RequiresDirective;

class Directives
{
    private static $directives;

    public static function key(): KeyDirective
    {
        return self::getDirectives()['key'];
    }

    public static function extends(): ExtendsDirective
    {
        return self::getDirectives()['extends'];
    }

    public static function external(): ExternalDirective
    {
        return self::getDirectives()['external'];
    }

    public static function requires(): RequiresDirective
    {
        return self::getDirectives()['requires'];
    }

    public static function provides(): ProvidesDirective
    {
        return self::getDirectives()['provides'];
    }

    public static function getDirectives()
    {
        if (!self::$directives) {
            self::$directives = [
                'key' => new KeyDirective(),
                'extends' => new ExtendsDirective(),
                'external' => new ExternalDirective(),
                'requires' => new RequiresDirective(),
                'provides' => new ProvidesDirective()
            ];
        }

        return self::$directives;
    }
}
