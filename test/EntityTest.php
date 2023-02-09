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

    /**
     * @dataProvider getDataForTestMethodGetKeyFields
     *
     * @param string[] $expected
     * @param array<string,mixed> $config
     */
    public function testMethodGetKeyFields(array $expected, array $config): void
    {
        self::assertSame($expected, (new EntityObjectType($config))->getKeyFields());
    }

    public function testMethodGetKeyFieldsTriggersDeprecation(): void
    {
        $isCaught = false;
        set_error_handler(static function (int $n, string $s, string $f = '', int $l = 0, array $c = [])
        use (&$isCaught): bool {
            $isCaught = true;

            return true;
        });
        $config = ['name' => '*', 'keys' => [['fields' => ['id']]]];
        (new EntityObjectType($config))->getKeyFields();

        self::assertTrue($isCaught, 'It does not trigger deprecation error. But it should!');

        restore_error_handler();
    }

    public function getDataForTestMethodGetKeyFields(): \Generator
    {
        yield [
            ['id'],
            ['name' => '*', 'keyFields' => ['id']],
        ];
        yield [
            ['id', 'email'],
            ['name' => '*', 'keyFields' => ['id', 'email']],
        ];
        yield [
            ['id'],
            ['name' => '*', 'keys' => [['fields' => 'id']]],
        ];
        yield [
            ['id'],
            ['name' => '*', 'keys' => [['fields' => ['id']]]],
        ];
        yield [
            ['id', 'email'],
            ['name' => '*', 'keys' => [['fields' => ['id', 'email']]]],
        ];
        yield [
            ['id', 'email'],
            ['name' => '*', 'keys' => [['fields' => ['id']], ['fields' => ['email']]]],
        ];
        yield [
            ['id', 'email'],
            ['name' => '*', 'keys' => [['fields' => 'id'], ['fields' => 'email']]],
        ];
    }
}