<?php

declare(strict_types=1);

namespace Apollo\Federation\Enum;

class DirectiveEnum
{
    public const EXTERNAL = 'external';
    public const KEY = 'key';
    public const PROVIDES = 'provides';
    public const REQUIRES = 'requires';

    /**
     * @return string[]
     */
    public static function getAll(): array
    {
        return [self::EXTERNAL, self::KEY, self::PROVIDES, self::REQUIRES];
    }

    private function __construct()
    {
        // forbid creation of an object
    }
}
