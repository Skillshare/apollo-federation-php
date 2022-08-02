<?php

declare(strict_types=1);

namespace Apollo\Federation;

use Apollo\Federation\Directives\ExternalDirective;
use Apollo\Federation\Directives\InaccessibleDirective;
use Apollo\Federation\Directives\KeyDirective;
use Apollo\Federation\Directives\OverrideDirective;
use Apollo\Federation\Directives\ProvidesDirective;
use Apollo\Federation\Directives\RequiresDirective;
use Apollo\Federation\Directives\ShareableDirective;
use Apollo\Federation\Enum\DirectiveEnum;

/**
 * Helper class to get directives for annotating federated entity types.
 */
class Directives
{
    /**
     * @var array{
     *     external: ExternalDirective,
     *     inaccessible: InaccessibleDirective,
     *     key: KeyDirective,
     *     override: OverrideDirective,
     *     requires: RequiresDirective,
     *     provides: ProvidesDirective,
     *     shareable: ShareableDirective,
     * }|null
     */
    private static ?array $directives = null;

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
     * Gets the @inaccessible directive.
     */
    public static function inaccessible(): InaccessibleDirective
    {
        return self::getDirectives()[DirectiveEnum::INACCESSIBLE];
    }

    /**
     * Gets the @override directive.
     */
    public static function override(): OverrideDirective
    {
        return self::getDirectives()[DirectiveEnum::OVERRIDE];
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
     * Gets the @shareable directive.
     */
    public static function shareable(): ShareableDirective
    {
        return self::getDirectives()[DirectiveEnum::SHAREABLE];
    }

    /**
     * Gets the directives that can be used on federated entity types.
     *
     * @return array{
     *     external: ExternalDirective,
     *     inaccessible: InaccessibleDirective,
     *     key: KeyDirective,
     *     override: OverrideDirective,
     *     requires: RequiresDirective,
     *     provides: ProvidesDirective,
     *     shareable: ShareableDirective,
     * }
     */
    public static function getDirectives(): array
    {
        if (!self::$directives) {
            self::$directives = [
                DirectiveEnum::EXTERNAL => new ExternalDirective(),
                DirectiveEnum::INACCESSIBLE => new InaccessibleDirective(),
                DirectiveEnum::KEY => new KeyDirective(),
                DirectiveEnum::OVERRIDE => new OverrideDirective(),
                DirectiveEnum::REQUIRES => new RequiresDirective(),
                DirectiveEnum::PROVIDES => new ProvidesDirective(),
                DirectiveEnum::SHAREABLE => new ShareableDirective(),
            ];
        }

        return self::$directives;
    }
}
