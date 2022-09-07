<?php

declare(strict_types=1);

namespace Apollo\Federation\Tests;

use Apollo\Federation\Types\EntityObjectType;
use GraphQL\Error\InvariantViolation;
use PHPUnit\Framework\TestCase;

final class EntityTest extends TestCase
{
    public function testConstructorsThrowsExceptionOnDuplicatedKeysConfig(): void
    {
        $this->expectException(InvariantViolation::class);
        $config = ['keys' => [], 'keyFields' => [], 'name' => '*'];
        new EntityObjectType($config);
    }

    public function testMethodGetKeyFieldsTriggersDeprecation(): void
    {
        $isCaught = false;
        set_error_handler(static function (int $errno, string $errstr, string $errfile = '', int $errline = 0, array $errcontext = []) use (&$isCaught): bool {
            $isCaught = true;
            return true;
        });
        $config = ['name' => '*', 'keys' => [['fields' => ['id']]]];
        (new EntityObjectType($config))->getKeyFields();

        self::assertTrue($isCaught, 'It does trigger deprecation error. But it should!');

        restore_error_handler();
    }
}