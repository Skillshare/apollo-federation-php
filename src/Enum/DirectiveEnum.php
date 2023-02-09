<?php

declare(strict_types=1);

namespace Apollo\Federation\Enum;

class DirectiveEnum
{
    public const EXTERNAL = 'external';
    public const INACCESSIBLE = 'inaccessible';
    public const KEY = 'key';
    public const LINK = 'link';
    public const OVERRIDE = 'override';
    public const PROVIDES = 'provides';
    public const REQUIRES = 'requires';
    public const SHAREABLE = 'shareable';

    /**
     * @var string[]|null
     */
    protected static $constants;

    /**
     * @return string[]
     */
    public static function getAll(): array
    {
        if (null === static::$constants) {
            static::$constants = (new \ReflectionClass(static::class))->getConstants();
        }

        return static::$constants;
    }

    protected function __construct()
    {
        // forbid creation of an object
    }
}
