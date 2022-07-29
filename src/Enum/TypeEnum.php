<?php

declare(strict_types=1);

namespace Apollo\Federation\Enum;

final class TypeEnum
{
    public const ANY = '_Any';
    public const ENTITY = '_Entity';
    public const SERVICE = '_Service';

    /**
     * @return string[]
     */
    public static function getAll(): array
    {
        return [self::ANY, self::ENTITY, self::SERVICE];
    }

    private function __construct()
    {
        // forbid creation of an object
    }
}
